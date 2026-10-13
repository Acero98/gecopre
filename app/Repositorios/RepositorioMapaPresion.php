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
        $consulta = ConexionBaseDatos::obtener()->prepare("SELECT pc.id, pc.codigo, pc.direccion, pc.latitud, pc.longitud, pc.coordenadas_referenciales, rm.valor_decimal AS valor, zp.nombre AS zona_presion, so.codigo AS sector, zg.nombre AS zona_geografica, COALESCE(fa.nombre, 'Sin fuente asignada') AS fuente, (SELECT rm2.valor_decimal FROM registros_mensuales rm2 INNER JOIN indicadores i2 ON i2.id = rm2.indicador_id WHERE rm2.periodo_id = rm.periodo_id AND rm2.punto_control_id = pc.id AND i2.codigo = 'continuidad' AND rm2.estado_dato = 'registrado' LIMIT 1) AS continuidad, (SELECT rm3.valor_decimal FROM registros_mensuales rm3 INNER JOIN indicadores i3 ON i3.id = rm3.indicador_id WHERE rm3.periodo_id = rm.periodo_id AND rm3.punto_control_id = pc.id AND i3.codigo = 'presion' AND rm3.estado_dato = 'registrado' LIMIT 1) AS presion FROM registros_mensuales rm INNER JOIN indicadores i ON i.id = rm.indicador_id INNER JOIN puntos_control pc ON pc.id = rm.punto_control_id INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id LEFT JOIN fuentes_sectores fs ON fs.sector_operacional_id = so.id AND fs.vigente_hasta IS NULL LEFT JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id WHERE rm.periodo_id = :periodo AND i.codigo = :indicador AND rm.estado_dato = 'registrado' AND pc.latitud IS NOT NULL AND pc.longitud IS NOT NULL ORDER BY pc.codigo");
        $consulta->execute(['periodo' => $periodoId, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function puntosUbicacion(): array
    {
        return ConexionBaseDatos::obtener()->query("SELECT pc.id, pc.codigo, pc.direccion, pc.latitud, pc.longitud, pc.coordenadas_referenciales, zp.nombre AS zona_presion, so.codigo AS sector, zg.nombre AS zona_geografica FROM puntos_control pc INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id INNER JOIN zonas_geograficas zg ON zg.id = so.zona_geografica_id WHERE pc.activo = 1 ORDER BY pc.codigo")->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function actualizarCoordenadas(int $puntoId, float $latitud, float $longitud, int $usuarioId): void
    {
        $conexion = ConexionBaseDatos::obtener();
        $actualizar = $conexion->prepare('UPDATE puntos_control SET latitud = :latitud, longitud = :longitud, coordenadas_referenciales = 0 WHERE id = :id AND activo = 1');
        $actualizar->execute(['latitud' => $latitud, 'longitud' => $longitud, 'id' => $puntoId]);
        if ($actualizar->rowCount() !== 1) { throw new \DomainException('No se encontró el punto de control.'); }
        $auditoria = $conexion->prepare("INSERT INTO auditoria (usuario_id, entidad, entidad_id, accion, detalle_nuevo) VALUES (:usuario_id, 'puntos_control', :punto_id, 'coordenadas_actualizadas', :detalle)");
        $auditoria->execute(['usuario_id' => $usuarioId, 'punto_id' => $puntoId, 'detalle' => json_encode(['latitud' => $latitud, 'longitud' => $longitud], JSON_UNESCAPED_UNICODE)]);
    }
}
