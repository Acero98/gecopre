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
        $mes = ConexionBaseDatos::obtener()->query(
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
}
