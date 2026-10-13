<?php
declare(strict_types=1);

namespace App\Servicios;

use App\Core\ConexionBaseDatos;

final class ServicioCalculoResultados
{
    public function calcularPeriodo(int $usuarioId, int $periodoId): void
    {
        $conexion = ConexionBaseDatos::obtener();
        try {
            $conexion->beginTransaction();
            $periodo = $conexion->prepare("SELECT id, localidad_id FROM periodos WHERE id = :id AND estado = 'aprobado' FOR UPDATE");
            $periodo->execute(['id' => $periodoId]);
            $actual = $periodo->fetch(\PDO::FETCH_ASSOC);
            if ($actual === false) { throw new \DomainException('Solo se pueden calcular períodos aprobados. Los períodos cerrados son definitivos.'); }
            $indicadores = $conexion->query('SELECT id FROM indicadores WHERE activo = 1 ORDER BY id')->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($indicadores as $indicador) {
                $siguiente = $conexion->prepare('SELECT COALESCE(MAX(numero_version), 0) + 1 FROM versiones_calculo WHERE periodo_id = :periodo_id AND indicador_id = :indicador_id');
                $siguiente->execute(['periodo_id' => $periodoId, 'indicador_id' => $indicador['id']]);
                $numeroVersion = (int) $siguiente->fetchColumn();
                $crearVersion = $conexion->prepare("INSERT INTO versiones_calculo (periodo_id, indicador_id, numero_version, regla_version, ejecutado_por) VALUES (:periodo_id, :indicador_id, :numero_version, 'ponderacion_conexiones_v1', :usuario_id)");
                $crearVersion->execute(['periodo_id' => $periodoId, 'indicador_id' => $indicador['id'], 'numero_version' => $numeroVersion, 'usuario_id' => $usuarioId]);
                $versionId = (int) $conexion->lastInsertId();
                $zonas = $this->zonasDelPeriodo($periodoId, (int) $indicador['id'], (int) $actual['localidad_id']);
                $insertar = $conexion->prepare('INSERT INTO resultados_calculo (version_calculo_id, tipo_ambito, ambito_id, valor, conexiones_utilizadas, cobertura_esperada, cobertura_obtenida, estado) VALUES (:version_calculo_id, :tipo_ambito, :ambito_id, :valor, :conexiones_utilizadas, :cobertura_esperada, :cobertura_obtenida, :estado)');
                $acumulados = ['sector_operacional' => [], 'zona_geografica' => [], 'fuente_abastecimiento' => [], 'localidad' => []];
                foreach ($zonas as $zona) {
                    $valida = $zona['valor'] !== null && $zona['conexiones'] !== null;
                    $this->insertarResultado($insertar, $versionId, 'zona_presion', (int) $zona['zona_id'], $valida ? (float) $zona['valor'] : null, $zona['conexiones'] === null ? null : (int) $zona['conexiones'], 1, $valida ? 1 : 0, $valida ? 'completo' : 'no_disponible');
                    $this->acumular($acumulados['sector_operacional'], (int) $zona['sector_id'], $zona, $valida);
                    $this->acumular($acumulados['zona_geografica'], (int) $zona['zona_geografica_id'], $zona, $valida);
                    $this->acumular($acumulados['localidad'], (int) $actual['localidad_id'], $zona, $valida);
                }
                foreach ($this->fuentesPorSector((int) $actual['localidad_id']) as $relacion) {
                    $sector = $acumulados['sector_operacional'][(int) $relacion['sector_id']] ?? null;
                    if ($sector === null) { continue; }
                    $fuenteId = (int) $relacion['fuente_id'];
                    $acumulados['fuente_abastecimiento'][$fuenteId] ??= ['esperada' => 0, 'obtenida' => 0, 'ponderado' => 0.0, 'conexiones' => 0];
                    foreach (['esperada', 'obtenida', 'ponderado', 'conexiones'] as $campo) { $acumulados['fuente_abastecimiento'][$fuenteId][$campo] += $sector[$campo]; }
                }
                foreach ($acumulados as $ambito => $grupos) {
                    foreach ($grupos as $ambitoId => $grupo) {
                        $resultado = $this->resultadoPonderado($grupo);
                        $this->insertarResultado($insertar, $versionId, $ambito, (int) $ambitoId, $resultado['valor'], $resultado['conexiones'], $grupo['esperada'], $grupo['obtenida'], $resultado['estado']);
                    }
                }
            }
            $auditoria = $conexion->prepare("INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_nuevo) VALUES (:usuario_id, 'periodos', :periodo_id, 'calculo_ejecutado', :detalle)");
            $auditoria->execute(['usuario_id' => $usuarioId, 'periodo_id' => $periodoId, 'detalle' => json_encode(['regla' => 'ponderacion_conexiones_v1'], JSON_UNESCAPED_UNICODE)]);
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            throw $error;
        }
    }

    private function zonasDelPeriodo(int $periodoId, int $indicadorId, int $localidadId): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare("SELECT zp.id AS zona_id, so.id AS sector_id, zg.id AS zona_geografica_id, AVG(CASE WHEN rm.estado_dato = 'registrado' THEN rm.valor_decimal END) AS valor, MAX(CASE WHEN cm.estado_dato = 'registrado' THEN cm.cantidad END) AS conexiones FROM zonas_presion zp INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id LEFT JOIN puntos_control pc ON pc.zona_presion_id = zp.id AND pc.activo = 1 LEFT JOIN registros_mensuales rm ON rm.punto_control_id = pc.id AND rm.periodo_id = :periodo_id AND rm.indicador_id = :indicador_id LEFT JOIN conexiones_mensuales cm ON cm.zona_presion_id = zp.id AND cm.periodo_id = :periodo_conexiones WHERE zg.localidad_id = :localidad_id AND zp.activo = 1 AND so.activo = 1 GROUP BY zp.id, so.id, zg.id");
        $consulta->execute(['periodo_id' => $periodoId, 'indicador_id' => $indicadorId, 'periodo_conexiones' => $periodoId, 'localidad_id' => $localidadId]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function acumular(array &$grupos, int $id, array $zona, bool $valida): void
    {
        $grupos[$id] ??= ['esperada' => 0, 'obtenida' => 0, 'ponderado' => 0.0, 'conexiones' => 0];
        $grupos[$id]['esperada']++;
        if (!$valida) { return; }
        $grupos[$id]['obtenida']++;
        $conexiones = (int) $zona['conexiones'];
        $grupos[$id]['ponderado'] += (float) $zona['valor'] * $conexiones;
        $grupos[$id]['conexiones'] += $conexiones;
    }

    private function fuentesPorSector(int $localidadId): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            'SELECT fs.fuente_abastecimiento_id AS fuente_id, fs.sector_operacional_id AS sector_id
             FROM fuentes_sectores fs
             INNER JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id
             INNER JOIN sectores_operacionales so ON so.id = fs.sector_operacional_id
             INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id
             WHERE zg.localidad_id = :localidad_id AND fs.vigente_hasta IS NULL'
        );
        $consulta->execute(['localidad_id' => $localidadId]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function resultadoPonderado(array $grupo): array
    {
        if ($grupo['obtenida'] === 0 || $grupo['conexiones'] === 0) { return ['valor' => null, 'conexiones' => $grupo['conexiones'] ?: null, 'estado' => 'no_disponible']; }
        return ['valor' => $grupo['ponderado'] / $grupo['conexiones'], 'conexiones' => $grupo['conexiones'], 'estado' => $grupo['obtenida'] === $grupo['esperada'] ? 'completo' : 'parcial'];
    }

    private function insertarResultado(\PDOStatement $insertar, int $versionId, string $tipo, int $ambitoId, ?float $valor, ?int $conexiones, int $esperada, int $obtenida, string $estado): void
    {
        $insertar->execute(['version_calculo_id' => $versionId, 'tipo_ambito' => $tipo, 'ambito_id' => $ambitoId, 'valor' => $valor, 'conexiones_utilizadas' => $conexiones, 'cobertura_esperada' => $esperada, 'cobertura_obtenida' => $obtenida, 'estado' => $estado]);
    }
}
