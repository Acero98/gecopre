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

    public function recientes(int $limite = 24): array
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
             GROUP BY p.id, p.anio, p.mes ORDER BY p.anio DESC, p.mes DESC"
        );
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }
}
