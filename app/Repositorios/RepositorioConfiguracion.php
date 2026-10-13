<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\ConexionBaseDatos;

final class RepositorioConfiguracion
{
    public function metasContinuidad(): array
    {
        return $this->metas('continuidad');
    }

    public function metasPresion(): array
    {
        return $this->metas('presion');
    }

    public function guardarMetasContinuidad(int $usuarioId, float $sunass, float $eps): void
    {
        $this->guardarMetas($usuarioId, 'continuidad', $sunass, $eps);
    }

    public function guardarMetasPresion(int $usuarioId, float $sunass, float $eps): void
    {
        $this->guardarMetas($usuarioId, 'presion', $sunass, $eps);
    }

    private function metas(string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT ambito, valor FROM metas_indicador mi INNER JOIN indicadores i ON i.id = mi.indicador_id WHERE i.codigo = :indicador AND mi.activo = 1 AND mi.vigente_desde <= '2026-12-31' AND (mi.vigente_hasta IS NULL OR mi.vigente_hasta >= '2026-01-01') ORDER BY mi.vigente_desde DESC"
        );
        $consulta->execute(['indicador' => $indicador]);
        $metas = $indicador === 'presion' ? ['sunass' => 28.0, 'eps' => 28.0] : ['sunass' => 17.0, 'eps' => 19.2];
        foreach ($consulta->fetchAll(\PDO::FETCH_ASSOC) as $fila) {
            if ($fila['ambito'] === 'localidad') { $metas['sunass'] = (float) $fila['valor']; }
            if ($fila['ambito'] === 'eps') { $metas['eps'] = (float) $fila['valor']; }
        }
        return $metas;
    }

    private function guardarMetas(int $usuarioId, string $indicador, float $sunass, float $eps): void
    {
        $conexion = ConexionBaseDatos::obtener();
        try {
            $conexion->beginTransaction();
            $buscarIndicador = $conexion->prepare('SELECT id FROM indicadores WHERE codigo = :codigo LIMIT 1');
            $buscarIndicador->execute(['codigo' => $indicador]);
            $indicadorId = (int) $buscarIndicador->fetchColumn();
            if ($indicadorId === 0) { throw new \RuntimeException('No se encontró el indicador solicitado.'); }
            $guardar = $conexion->prepare("INSERT INTO metas_indicador (indicador_id, ambito, referencia, valor, vigente_desde, activo) VALUES (:indicador_id, :ambito, :referencia, :valor, '2026-01-01', 1) ON DUPLICATE KEY UPDATE valor = VALUES(valor), activo = 1");
            $guardar->execute(['indicador_id' => $indicadorId, 'ambito' => 'localidad', 'referencia' => 'SUNASS 2026', 'valor' => $sunass]);
            $guardar->execute(['indicador_id' => $indicadorId, 'ambito' => 'eps', 'referencia' => 'EPS ILO 2026', 'valor' => $eps]);
            $auditoria = $conexion->prepare("INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_nuevo) VALUES (:usuario_id, 'metas_indicador', :entidad_id, :accion, :detalle)");
            $auditoria->execute(['usuario_id' => $usuarioId, 'entidad_id' => $indicadorId, 'accion' => 'metas_' . $indicador . '_actualizadas', 'detalle' => json_encode(['indicador' => $indicador, 'sunass' => $sunass, 'eps_ilo' => $eps, 'anio' => 2026], JSON_UNESCAPED_UNICODE)]);
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            throw $error;
        }
    }
}
