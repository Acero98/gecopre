<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\ConexionBaseDatos;

final class RepositorioMediciones
{
    public function resumen(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT COUNT(*) AS total, SUM(i.codigo = 'continuidad') AS continuidad,
                    SUM(i.codigo = 'presion') AS presion,
                    MAX(CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'))) AS ultimo_periodo
             FROM registros_mensuales rm
             INNER JOIN indicadores i ON i.id = rm.indicador_id
             INNER JOIN periodos p ON p.id = rm.periodo_id"
        );
        return $consulta->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0, 'continuidad' => 0, 'presion' => 0, 'ultimo_periodo' => null];
    }

    public function resumenOperativo(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT COUNT(*) AS periodos, SUM(estado = 'borrador') AS borradores,
                    SUM(estado = 'en_revision') AS en_revision, SUM(estado = 'aprobado') AS aprobados,
                    SUM(estado = 'cerrado') AS cerrados,
                    MAX(CONCAT(anio, '-', LPAD(mes, 2, '0'))) AS ultimo_periodo
             FROM periodos"
        );
        return $consulta->fetch(\PDO::FETCH_ASSOC) ?: ['periodos' => 0, 'borradores' => 0, 'en_revision' => 0, 'aprobados' => 0, 'cerrados' => 0, 'ultimo_periodo' => null];
    }

    public function recientes(int $limite = 600): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.anio, p.mes, pc.codigo AS punto, so.codigo AS sector, zp.nombre AS zona_presion,
                    i.nombre AS indicador, i.unidad, rm.valor_decimal, rm.estado_dato
             FROM registros_mensuales rm
             INNER JOIN periodos p ON p.id = rm.periodo_id
             INNER JOIN puntos_control pc ON pc.id = rm.punto_control_id
             INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             INNER JOIN indicadores i ON i.id = rm.indicador_id
             ORDER BY p.anio DESC, p.mes DESC, pc.codigo ASC, i.codigo ASC LIMIT :limite"
        );
        $consulta->bindValue('limite', $limite, \PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function conexionesPorPeriodo(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT p.anio, p.mes, SUM(cm.cantidad) AS conexiones, COUNT(*) AS zonas
             FROM conexiones_mensuales cm INNER JOIN periodos p ON p.id = cm.periodo_id
             GROUP BY p.id, p.anio, p.mes ORDER BY p.anio ASC, p.mes ASC"
        );
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function resumenConexiones(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT COUNT(*) AS registros, COUNT(DISTINCT cm.zona_presion_id) AS zonas,
                    MAX(CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'))) AS ultimo_periodo,
                    COALESCE(SUM(CASE WHEN CONCAT(p.anio, '-', LPAD(p.mes, 2, '0')) = (SELECT MAX(CONCAT(p2.anio, '-', LPAD(p2.mes, 2, '0'))) FROM conexiones_mensuales cm2 INNER JOIN periodos p2 ON p2.id = cm2.periodo_id) THEN cm.cantidad ELSE 0 END), 0) AS conexiones_ultimo_periodo
             FROM conexiones_mensuales cm INNER JOIN periodos p ON p.id = cm.periodo_id"
        );
        return $consulta->fetch(\PDO::FETCH_ASSOC) ?: ['registros' => 0, 'zonas' => 0, 'ultimo_periodo' => null, 'conexiones_ultimo_periodo' => 0];
    }

    public function zonasPresionActivas(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT zp.id, zp.nombre AS zona_presion, so.codigo AS sector, so.nombre AS nombre_sector
             FROM zonas_presion zp INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             WHERE zp.activo = 1 AND so.activo = 1 ORDER BY so.codigo, FIELD(zp.nombre, 'Alta', 'Media', 'Baja')"
        );
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function conexionesRecientes(int $limite = 600): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.anio, p.mes, so.codigo AS sector, zp.nombre AS zona_presion, cm.cantidad, cm.estado_dato, cm.observacion
             FROM conexiones_mensuales cm
             INNER JOIN periodos p ON p.id = cm.periodo_id
             INNER JOIN zonas_presion zp ON zp.id = cm.zona_presion_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             ORDER BY p.anio DESC, p.mes DESC, so.codigo ASC, FIELD(zp.nombre, 'Alta', 'Media', 'Baja') ASC LIMIT :limite"
        );
        $consulta->bindValue('limite', $limite, \PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function puntosActivos(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT pc.id, pc.codigo, pc.direccion, so.codigo AS sector, zp.nombre AS zona_presion
             FROM puntos_control pc
             INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             WHERE pc.activo = 1 ORDER BY pc.codigo"
        );
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function siguienteMesDisponible(): int
    {
        $conexion = ConexionBaseDatos::obtener();
        $borrador = $conexion->query(
            "SELECT mes FROM periodos WHERE localidad_id = (SELECT id FROM localidades WHERE codigo = 'ILO' LIMIT 1) AND anio = 2026 AND estado = 'borrador' ORDER BY mes DESC LIMIT 1"
        )->fetchColumn();
        if ($borrador !== false) {
            return (int) $borrador;
        }
        $mes = $conexion->query(
            "SELECT COALESCE(MAX(mes), 0) FROM periodos WHERE localidad_id = (SELECT id FROM localidades WHERE codigo = 'ILO' LIMIT 1) AND anio = 2026"
        )->fetchColumn();
        return min(12, max(1, (int) $mes + 1));
    }

    public function guardarManual(int $usuarioId, int $mes, int $puntoId, string $indicador, string $valor, ?string $observacion): void
    {
        $conexion = ConexionBaseDatos::obtener();
        try {
            $conexion->beginTransaction();
            $localidadId = (int) $conexion->query("SELECT id FROM localidades WHERE codigo = 'ILO' LIMIT 1")->fetchColumn();
            if ($localidadId === 0) {
                throw new \RuntimeException('No se encontró la localidad de trabajo.');
            }
            $periodo = $conexion->prepare('SELECT id, estado FROM periodos WHERE localidad_id = :localidad_id AND anio = 2026 AND mes = :mes LIMIT 1 FOR UPDATE');
            $periodo->execute(['localidad_id' => $localidadId, 'mes' => $mes]);
            $periodoActual = $periodo->fetch(\PDO::FETCH_ASSOC);
            if ($periodoActual === false) {
                $crearPeriodo = $conexion->prepare("INSERT INTO periodos (localidad_id, anio, mes, estado) VALUES (:localidad_id, 2026, :mes, 'borrador')");
                $crearPeriodo->execute(['localidad_id' => $localidadId, 'mes' => $mes]);
                $periodoId = (int) $conexion->lastInsertId();
            } else {
                if ($periodoActual['estado'] !== 'borrador') {
                    throw new \DomainException('El período seleccionado está en revisión o cerrado y no admite cambios.');
                }
                $periodoId = (int) $periodoActual['id'];
            }

            $punto = $conexion->prepare('SELECT id FROM puntos_control WHERE id = :id AND activo = 1 LIMIT 1');
            $punto->execute(['id' => $puntoId]);
            if ($punto->fetchColumn() === false) {
                throw new \DomainException('El punto de control seleccionado no está disponible.');
            }
            $indicadorId = $conexion->prepare('SELECT id FROM indicadores WHERE codigo = :codigo AND activo = 1 LIMIT 1');
            $indicadorId->execute(['codigo' => $indicador]);
            $indicadorId = $indicadorId->fetchColumn();
            if ($indicadorId === false) {
                throw new \DomainException('El indicador seleccionado no está disponible.');
            }

            $existente = $conexion->prepare('SELECT id, valor_decimal, observacion FROM registros_mensuales WHERE periodo_id = :periodo_id AND punto_control_id = :punto_control_id AND indicador_id = :indicador_id LIMIT 1 FOR UPDATE');
            $existente->execute(['periodo_id' => $periodoId, 'punto_control_id' => $puntoId, 'indicador_id' => $indicadorId]);
            $anterior = $existente->fetch(\PDO::FETCH_ASSOC);
            if ($anterior === false) {
                $crear = $conexion->prepare("INSERT INTO registros_mensuales (periodo_id, punto_control_id, indicador_id, valor_decimal, estado_dato, observacion, registrado_por) VALUES (:periodo_id, :punto_control_id, :indicador_id, :valor_decimal, 'registrado', :observacion, :usuario_id)");
                $crear->execute(['periodo_id' => $periodoId, 'punto_control_id' => $puntoId, 'indicador_id' => $indicadorId, 'valor_decimal' => $valor, 'observacion' => $observacion, 'usuario_id' => $usuarioId]);
                $registroId = (int) $conexion->lastInsertId();
                $accion = 'registro_creado';
            } else {
                $registroId = (int) $anterior['id'];
                $actualizar = $conexion->prepare("UPDATE registros_mensuales SET valor_decimal = :valor_decimal, estado_dato = 'registrado', observacion = :observacion, registrado_por = :usuario_id WHERE id = :id");
                $actualizar->execute(['valor_decimal' => $valor, 'observacion' => $observacion, 'usuario_id' => $usuarioId, 'id' => $registroId]);
                $accion = 'registro_actualizado';
            }
            $auditoria = $conexion->prepare('INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_anterior, detalle_nuevo) VALUES (:usuario_id, :entidad, :entidad_id, :accion, :detalle_anterior, :detalle_nuevo)');
            $auditoria->execute([
                'usuario_id' => $usuarioId,
                'entidad' => 'registros_mensuales',
                'entidad_id' => $registroId,
                'accion' => $accion,
                'detalle_anterior' => $anterior === false ? null : json_encode($anterior, JSON_UNESCAPED_UNICODE),
                'detalle_nuevo' => json_encode(['mes' => $mes, 'punto_control_id' => $puntoId, 'indicador' => $indicador, 'valor' => $valor, 'observacion' => $observacion], JSON_UNESCAPED_UNICODE),
            ]);
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public function guardarConexionManual(int $usuarioId, int $mes, int $zonaPresionId, int $cantidad, ?string $observacion): void
    {
        $conexion = ConexionBaseDatos::obtener();
        try {
            $conexion->beginTransaction();
            $localidadId = (int) $conexion->query("SELECT id FROM localidades WHERE codigo = 'ILO' LIMIT 1")->fetchColumn();
            if ($localidadId === 0) {
                throw new \RuntimeException('No se encontró la localidad de trabajo.');
            }
            $periodo = $conexion->prepare('SELECT id, estado FROM periodos WHERE localidad_id = :localidad_id AND anio = 2026 AND mes = :mes LIMIT 1 FOR UPDATE');
            $periodo->execute(['localidad_id' => $localidadId, 'mes' => $mes]);
            $periodoActual = $periodo->fetch(\PDO::FETCH_ASSOC);
            if ($periodoActual === false) {
                $crearPeriodo = $conexion->prepare("INSERT INTO periodos (localidad_id, anio, mes, estado) VALUES (:localidad_id, 2026, :mes, 'borrador')");
                $crearPeriodo->execute(['localidad_id' => $localidadId, 'mes' => $mes]);
                $periodoId = (int) $conexion->lastInsertId();
            } elseif ($periodoActual['estado'] === 'borrador') {
                $periodoId = (int) $periodoActual['id'];
            } else {
                throw new \DomainException('El período seleccionado está en revisión o cerrado y no admite cambios.');
            }

            $zona = $conexion->prepare('SELECT id FROM zonas_presion WHERE id = :id AND activo = 1 LIMIT 1');
            $zona->execute(['id' => $zonaPresionId]);
            if ($zona->fetchColumn() === false) {
                throw new \DomainException('La zona de presión seleccionada no está disponible.');
            }
            $existente = $conexion->prepare('SELECT id, cantidad, observacion FROM conexiones_mensuales WHERE periodo_id = :periodo_id AND zona_presion_id = :zona_presion_id LIMIT 1 FOR UPDATE');
            $existente->execute(['periodo_id' => $periodoId, 'zona_presion_id' => $zonaPresionId]);
            $anterior = $existente->fetch(\PDO::FETCH_ASSOC);
            if ($anterior === false) {
                $crear = $conexion->prepare("INSERT INTO conexiones_mensuales (periodo_id, zona_presion_id, cantidad, estado_dato, observacion, registrado_por) VALUES (:periodo_id, :zona_presion_id, :cantidad, 'registrado', :observacion, :usuario_id)");
                $crear->execute(['periodo_id' => $periodoId, 'zona_presion_id' => $zonaPresionId, 'cantidad' => $cantidad, 'observacion' => $observacion, 'usuario_id' => $usuarioId]);
                $conexionId = (int) $conexion->lastInsertId();
                $accion = 'conexion_creada';
            } else {
                $conexionId = (int) $anterior['id'];
                $actualizar = $conexion->prepare("UPDATE conexiones_mensuales SET cantidad = :cantidad, estado_dato = 'registrado', observacion = :observacion, registrado_por = :usuario_id WHERE id = :id");
                $actualizar->execute(['cantidad' => $cantidad, 'observacion' => $observacion, 'usuario_id' => $usuarioId, 'id' => $conexionId]);
                $accion = 'conexion_actualizada';
            }
            $auditoria = $conexion->prepare('INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_anterior, detalle_nuevo) VALUES (:usuario_id, :entidad, :entidad_id, :accion, :detalle_anterior, :detalle_nuevo)');
            $auditoria->execute([
                'usuario_id' => $usuarioId,
                'entidad' => 'conexiones_mensuales',
                'entidad_id' => $conexionId,
                'accion' => $accion,
                'detalle_anterior' => $anterior === false ? null : json_encode($anterior, JSON_UNESCAPED_UNICODE),
                'detalle_nuevo' => json_encode(['mes' => $mes, 'zona_presion_id' => $zonaPresionId, 'cantidad' => $cantidad, 'observacion' => $observacion], JSON_UNESCAPED_UNICODE),
            ]);
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public function periodosParaRevision(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            "SELECT p.id, p.anio, p.mes, p.estado,
                    (SELECT COUNT(*) FROM registros_mensuales rm WHERE rm.periodo_id = p.id) AS mediciones,
                    (SELECT COUNT(*) FROM registros_mensuales rm INNER JOIN indicadores i ON i.id = rm.indicador_id WHERE rm.periodo_id = p.id AND i.codigo = 'continuidad') AS continuidad,
                    (SELECT COUNT(*) FROM registros_mensuales rm INNER JOIN indicadores i ON i.id = rm.indicador_id WHERE rm.periodo_id = p.id AND i.codigo = 'presion') AS presion,
                    (SELECT COUNT(*) FROM conexiones_mensuales cm WHERE cm.periodo_id = p.id) AS zonas_con_conexiones,
                    (SELECT COALESCE(SUM(cm.cantidad), 0) FROM conexiones_mensuales cm WHERE cm.periodo_id = p.id) AS conexiones,
                    (SELECT rc.valor FROM versiones_calculo vc INNER JOIN indicadores i ON i.id = vc.indicador_id INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id WHERE vc.periodo_id = p.id AND i.codigo = 'continuidad' AND rc.tipo_ambito = 'localidad' ORDER BY vc.numero_version DESC LIMIT 1) AS resultado_continuidad,
                    (SELECT rc.valor FROM versiones_calculo vc INNER JOIN indicadores i ON i.id = vc.indicador_id INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id WHERE vc.periodo_id = p.id AND i.codigo = 'presion' AND rc.tipo_ambito = 'localidad' ORDER BY vc.numero_version DESC LIMIT 1) AS resultado_presion
             FROM periodos p
             WHERE p.localidad_id = (SELECT id FROM localidades WHERE codigo = 'ILO' LIMIT 1)
             ORDER BY p.anio DESC, p.mes DESC"
        );
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function detallePeriodoRevision(int $periodoId): ?array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.id, p.anio, p.mes, p.estado,
                    (SELECT COUNT(*) FROM registros_mensuales rm WHERE rm.periodo_id = p.id) AS mediciones,
                    (SELECT COUNT(*) FROM registros_mensuales rm INNER JOIN indicadores i ON i.id = rm.indicador_id WHERE rm.periodo_id = p.id AND i.codigo = 'continuidad') AS continuidad,
                    (SELECT COUNT(*) FROM registros_mensuales rm INNER JOIN indicadores i ON i.id = rm.indicador_id WHERE rm.periodo_id = p.id AND i.codigo = 'presion') AS presion,
                    (SELECT COUNT(*) FROM conexiones_mensuales cm WHERE cm.periodo_id = p.id) AS zonas_con_conexiones,
                    (SELECT COALESCE(SUM(cm.cantidad), 0) FROM conexiones_mensuales cm WHERE cm.periodo_id = p.id) AS conexiones
             FROM periodos p WHERE p.id = :id LIMIT 1"
        );
        $consulta->execute(['id' => $periodoId]);
        $resultado = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $resultado === false ? null : $resultado;
    }

    public function medicionesDePeriodo(int $periodoId): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT pc.codigo AS punto, pc.direccion, so.codigo AS sector, zp.nombre AS zona_presion,
                    i.nombre AS indicador, i.unidad, rm.valor_decimal, rm.estado_dato, rm.observacion
             FROM registros_mensuales rm
             INNER JOIN puntos_control pc ON pc.id = rm.punto_control_id
             INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             INNER JOIN indicadores i ON i.id = rm.indicador_id
             WHERE rm.periodo_id = :periodo_id
             ORDER BY i.codigo, so.codigo, FIELD(zp.nombre, 'Alta', 'Media', 'Baja'), pc.codigo"
        );
        $consulta->execute(['periodo_id' => $periodoId]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function conexionesDePeriodo(int $periodoId): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT so.codigo AS sector, zp.nombre AS zona_presion, cm.cantidad, cm.estado_dato, cm.observacion
             FROM conexiones_mensuales cm
             INNER JOIN zonas_presion zp ON zp.id = cm.zona_presion_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             WHERE cm.periodo_id = :periodo_id
             ORDER BY so.codigo, FIELD(zp.nombre, 'Alta', 'Media', 'Baja')"
        );
        $consulta->execute(['periodo_id' => $periodoId]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function cambiarEstadoPeriodo(int $usuarioId, int $periodoId, string $estadoDestino, ?string $motivo): void
    {
        $transiciones = [
            'borrador' => ['en_revision'],
            'en_revision' => ['borrador', 'aprobado'],
            'aprobado' => ['borrador', 'cerrado'],
            'cerrado' => [],
        ];
        $conexion = ConexionBaseDatos::obtener();
        try {
            $conexion->beginTransaction();
            $periodo = $conexion->prepare('SELECT id, estado FROM periodos WHERE id = :id LIMIT 1 FOR UPDATE');
            $periodo->execute(['id' => $periodoId]);
            $actual = $periodo->fetch(\PDO::FETCH_ASSOC);
            if ($actual === false) {
                throw new \DomainException('El período seleccionado no existe.');
            }
            if (!in_array($estadoDestino, $transiciones[$actual['estado']] ?? [], true)) {
                throw new \DomainException('La transición de estado solicitada no está permitida.');
            }
            if (in_array($estadoDestino, ['en_revision', 'aprobado', 'cerrado'], true)) {
                $cobertura = $conexion->prepare(
                    "SELECT COUNT(DISTINCT rm.id) AS mediciones, COUNT(DISTINCT cm.id) AS conexiones
                     FROM periodos p
                     LEFT JOIN registros_mensuales rm ON rm.periodo_id = p.id
                     LEFT JOIN conexiones_mensuales cm ON cm.periodo_id = p.id
                     WHERE p.id = :id GROUP BY p.id"
                );
                $cobertura->execute(['id' => $periodoId]);
                $datos = $cobertura->fetch(\PDO::FETCH_ASSOC);
                if ((int) ($datos['mediciones'] ?? 0) === 0 || (int) ($datos['conexiones'] ?? 0) === 0) {
                    throw new \DomainException('El período necesita al menos una medición y una conexión antes de avanzar.');
                }
            }
            if ($estadoDestino === 'cerrado') {
                $resultados = $conexion->prepare(
                    "SELECT COUNT(DISTINCT i.codigo) FROM versiones_calculo vc
                     INNER JOIN indicadores i ON i.id = vc.indicador_id
                     INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
                     WHERE vc.periodo_id = :id AND i.codigo IN ('continuidad', 'presion')
                       AND rc.tipo_ambito = 'localidad' AND rc.valor IS NOT NULL"
                );
                $resultados->execute(['id' => $periodoId]);
                if ((int) $resultados->fetchColumn() < 2) {
                    throw new \DomainException('Genera y revisa los resultados de continuidad y presión antes de cerrar el período.');
                }
            }
            if ($estadoDestino === 'borrador' && ($motivo === null || trim($motivo) === '')) {
                throw new \DomainException('Indica el motivo de la devolución o reapertura.');
            }
            $actualizar = $conexion->prepare('UPDATE periodos SET estado = :estado WHERE id = :id');
            $actualizar->execute(['estado' => $estadoDestino, 'id' => $periodoId]);
            $auditoria = $conexion->prepare('INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_anterior, detalle_nuevo, motivo) VALUES (:usuario_id, :entidad, :entidad_id, :accion, :detalle_anterior, :detalle_nuevo, :motivo)');
            $auditoria->execute([
                'usuario_id' => $usuarioId,
                'entidad' => 'periodos',
                'entidad_id' => $periodoId,
                'accion' => 'estado_actualizado',
                'detalle_anterior' => json_encode(['estado' => $actual['estado']], JSON_UNESCAPED_UNICODE),
                'detalle_nuevo' => json_encode(['estado' => $estadoDestino], JSON_UNESCAPED_UNICODE),
                'motivo' => $motivo,
            ]);
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }
}
