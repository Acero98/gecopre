<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\ConexionBaseDatos;

final class RepositorioMapaPresion
{
    public function periodosDisponibles(): array
    {
        return ConexionBaseDatos::obtener()->query("SELECT p.id, p.anio, p.mes FROM periodos p INNER JOIN registros_mensuales rm ON rm.periodo_id = p.id INNER JOIN indicadores i ON i.id = rm.indicador_id WHERE p.estado IN ('aprobado', 'cerrado') AND i.codigo = 'presion' AND rm.estado_dato = 'registrado' GROUP BY p.id, p.anio, p.mes ORDER BY p.anio DESC, p.mes DESC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function puntosParaMapa(int $periodoId, string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare("SELECT pc.id, pc.codigo, pc.direccion, pc.latitud, pc.longitud, pc.coordenadas_referenciales, pc.imagen_referencia, rm.valor_decimal AS valor, zp.nombre AS zona_presion, so.codigo AS sector, zg.nombre AS zona_geografica, COALESCE(fa.nombre, 'Sin fuente asignada') AS fuente, (SELECT rm2.valor_decimal FROM registros_mensuales rm2 INNER JOIN indicadores i2 ON i2.id = rm2.indicador_id WHERE rm2.periodo_id = rm.periodo_id AND rm2.punto_control_id = pc.id AND i2.codigo = 'continuidad' AND rm2.estado_dato = 'registrado' LIMIT 1) AS continuidad, (SELECT rm3.valor_decimal FROM registros_mensuales rm3 INNER JOIN indicadores i3 ON i3.id = rm3.indicador_id WHERE rm3.periodo_id = rm.periodo_id AND rm3.punto_control_id = pc.id AND i3.codigo = 'presion' AND rm3.estado_dato = 'registrado' LIMIT 1) AS presion FROM registros_mensuales rm INNER JOIN indicadores i ON i.id = rm.indicador_id INNER JOIN puntos_control pc ON pc.id = rm.punto_control_id INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id LEFT JOIN fuentes_sectores fs ON fs.sector_operacional_id = so.id AND fs.vigente_hasta IS NULL LEFT JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id WHERE rm.periodo_id = :periodo AND i.codigo = :indicador AND rm.estado_dato = 'registrado' AND pc.latitud IS NOT NULL AND pc.longitud IS NOT NULL ORDER BY pc.codigo");
        $consulta->execute(['periodo' => $periodoId, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function puntosUbicacion(): array
    {
        return ConexionBaseDatos::obtener()->query("SELECT pc.id, pc.codigo, pc.direccion, pc.latitud, pc.longitud, pc.coordenadas_referenciales, pc.imagen_referencia, zp.nombre AS zona_presion, so.codigo AS sector, so.codigo AS sector_operacional, zg.nombre AS zona_geografica FROM puntos_control pc INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id WHERE pc.activo = 1 ORDER BY pc.codigo")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function actualizarCoordenadas(int $puntoId, float $latitud, float $longitud, int $usuarioId): void
    {
        $conexion = ConexionBaseDatos::obtener();
        $actualizar = $conexion->prepare('UPDATE puntos_control SET latitud = :latitud, longitud = :longitud, coordenadas_referenciales = 0 WHERE id = :id AND activo = 1');
        $actualizar->execute(['latitud' => $latitud, 'longitud' => $longitud, 'id' => $puntoId]);
        if ($actualizar->rowCount() === 0) {
            $existe = $conexion->prepare('SELECT 1 FROM puntos_control WHERE id = :id AND activo = 1');
            $existe->execute(['id' => $puntoId]);
            if ($existe->fetchColumn() === false) throw new \DomainException('No se encontró el punto de control.');
            return;
        }
        $auditoria = $conexion->prepare("INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_nuevo) VALUES (:usuario_id, 'puntos_control', :punto_id, 'coordenadas_actualizadas', :detalle)");
        $auditoria->execute(['usuario_id' => $usuarioId, 'punto_id' => $puntoId, 'detalle' => json_encode(['latitud' => $latitud, 'longitud' => $longitud], JSON_UNESCAPED_UNICODE)]);
    }

    public function actualizarImagenReferencia(int $puntoId, string $imagen, int $usuarioId): ?string
    {
        $conexion = ConexionBaseDatos::obtener();
        $consulta = $conexion->prepare('SELECT imagen_referencia FROM puntos_control WHERE id = :id AND activo = 1 FOR UPDATE');
        $consulta->execute(['id' => $puntoId]);
        $registro = $consulta->fetch(\PDO::FETCH_ASSOC);
        if ($registro === false) throw new \DomainException('No se encontró el punto de control.');
        $anterior = $registro['imagen_referencia'];
        $actualizar = $conexion->prepare('UPDATE puntos_control SET imagen_referencia = :imagen WHERE id = :id');
        $actualizar->execute(['imagen' => $imagen, 'id' => $puntoId]);
        $auditoria = $conexion->prepare("INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_anterior, detalle_nuevo) VALUES (:usuario_id, 'puntos_control', :punto_id, 'imagen_referencia_actualizada', :anterior, :nuevo)");
        $auditoria->execute([
            'usuario_id' => $usuarioId,
            'punto_id' => $puntoId,
            'anterior' => $anterior ? json_encode(['imagen' => $anterior], JSON_UNESCAPED_UNICODE) : null,
            'nuevo' => json_encode(['imagen' => $imagen], JSON_UNESCAPED_UNICODE),
        ]);
        return is_string($anterior) && $anterior !== '' ? $anterior : null;
    }

    /** @return array{creados:int,actualizados:int,omitidos:int,errores:list<string>} */
    public function importarUbicaciones(array $filas, int $usuarioId): array
    {
        $conexion = ConexionBaseDatos::obtener();
        $resultado = ['creados' => 0, 'actualizados' => 0, 'omitidos' => 0, 'errores' => []];
        $conexion->beginTransaction();
        try {
            $buscarPunto = $conexion->prepare('SELECT id, codigo, direccion, zona_presion_id, latitud, longitud, activo FROM puntos_control WHERE codigo = :codigo LIMIT 1');
            $buscarZona = $conexion->prepare('SELECT zp.id FROM zonas_presion zp INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id WHERE UPPER(zg.nombre) = UPPER(:zona_geografica) AND UPPER(so.codigo) = UPPER(:sector) AND UPPER(zp.nombre) = UPPER(:zona_presion) AND zg.activo = 1 AND so.activo = 1 AND zp.activo = 1 LIMIT 1');
            $crear = $conexion->prepare('INSERT INTO puntos_control (codigo, direccion, zona_presion_id, latitud, longitud, coordenadas_referenciales, activo) VALUES (:codigo, :direccion, :zona_presion_id, :latitud, :longitud, 0, 1)');
            $actualizar = $conexion->prepare('UPDATE puntos_control SET direccion = :direccion, zona_presion_id = :zona_presion_id, latitud = :latitud, longitud = :longitud, coordenadas_referenciales = 0, activo = 1 WHERE id = :id');
            $auditoria = $conexion->prepare("INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_anterior, detalle_nuevo) VALUES (:usuario_id, 'puntos_control', :entidad_id, :accion, :anterior, :nuevo)");

            foreach ($filas as $fila) {
                $linea = (int) ($fila['_fila'] ?? 0);
                $codigo = strtoupper(trim((string) ($fila['codigo'] ?? '')));
                $direccion = trim((string) ($fila['direccion'] ?? ''));
                $zonaGeografica = trim((string) ($fila['zona_geografica'] ?? ''));
                $sector = trim((string) ($fila['sector_operacional'] ?? ''));
                $zonaPresion = ucfirst(mb_strtolower(trim((string) ($fila['zona_presion'] ?? '')), 'UTF-8'));
                $latitud = $this->coordenada($fila['latitud'] ?? null);
                $longitud = $this->coordenada($fila['longitud'] ?? null);

                if ($codigo === '' || $latitud === null || $longitud === null || $latitud < -18 || $latitud > -17 || $longitud < -72 || $longitud > -70) {
                    $resultado['errores'][] = "Fila {$linea}: completa código y coordenadas válidas de Ilo.";
                    continue;
                }
                $buscarPunto->execute(['codigo' => $codigo]);
                $existente = $buscarPunto->fetch(\PDO::FETCH_ASSOC);
                $datosRelacion = [$zonaGeografica, $sector, $zonaPresion];
                $tieneRelacion = !in_array('', $datosRelacion, true);
                if (!$tieneRelacion && array_filter($datosRelacion, static fn (string $valor): bool => $valor !== '') !== []) {
                    $resultado['errores'][] = "Fila {$linea}: completa zona geográfica, sector y zona de presión, o deja los tres campos vacíos para conservar la relación existente.";
                    continue;
                }

                if ($existente === false && ($direccion === '' || !$tieneRelacion)) {
                    $resultado['errores'][] = "Fila {$linea}: para agregar un punto nuevo completa dirección, zona geográfica, sector y zona de presión.";
                    continue;
                }
                $zonaId = $existente !== false ? (int) $existente['zona_presion_id'] : 0;
                if ($tieneRelacion) {
                    if (!in_array($zonaPresion, ['Alta', 'Media', 'Baja'], true)) {
                        $resultado['errores'][] = "Fila {$linea}: la zona de presión debe ser Alta, Media o Baja.";
                        continue;
                    }
                    $buscarZona->execute(['zona_geografica' => $zonaGeografica, 'sector' => $sector, 'zona_presion' => $zonaPresion]);
                    $zonaId = $buscarZona->fetchColumn();
                    if ($zonaId === false) {
                        $resultado['errores'][] = "Fila {$linea}: no existe la combinación {$zonaGeografica} · {$sector} · {$zonaPresion}.";
                        continue;
                    }
                }
                $nuevosDatos = ['codigo' => $codigo, 'direccion' => $direccion !== '' ? $direccion : (string) ($existente['direccion'] ?? ''), 'zona_presion_id' => (int) $zonaId, 'latitud' => $latitud, 'longitud' => $longitud, 'activo' => 1];
                if ($existente === false) {
                    $crear->execute(array_intersect_key($nuevosDatos, array_flip(['codigo', 'direccion', 'zona_presion_id', 'latitud', 'longitud'])));
                    $id = (int) $conexion->lastInsertId();
                    $auditoria->execute(['usuario_id' => $usuarioId, 'entidad_id' => $id, 'accion' => 'ubicacion_importada_creada', 'anterior' => null, 'nuevo' => json_encode($nuevosDatos, JSON_UNESCAPED_UNICODE)]);
                    $resultado['creados']++;
                    continue;
                }
                $comparar = ['direccion' => (string) $existente['direccion'], 'zona_presion_id' => (int) $existente['zona_presion_id'], 'latitud' => round((float) $existente['latitud'], 7), 'longitud' => round((float) $existente['longitud'], 7), 'activo' => (int) $existente['activo']];
                if ($comparar === ['direccion' => $nuevosDatos['direccion'], 'zona_presion_id' => (int) $zonaId, 'latitud' => round($latitud, 7), 'longitud' => round($longitud, 7), 'activo' => 1]) {
                    $resultado['omitidos']++;
                    continue;
                }
                $actualizar->execute(array_intersect_key($nuevosDatos, array_flip(['direccion', 'zona_presion_id', 'latitud', 'longitud'])) + ['id' => (int) $existente['id']]);
                $auditoria->execute(['usuario_id' => $usuarioId, 'entidad_id' => (int) $existente['id'], 'accion' => 'ubicacion_importada_actualizada', 'anterior' => json_encode($comparar, JSON_UNESCAPED_UNICODE), 'nuevo' => json_encode($nuevosDatos, JSON_UNESCAPED_UNICODE)]);
                $resultado['actualizados']++;
            }
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            throw $error;
        }
        return $resultado;
    }

    private function coordenada(mixed $valor): ?float
    {
        $valor = str_replace(',', '.', trim((string) $valor));
        return is_numeric($valor) ? (float) $valor : null;
    }
}
