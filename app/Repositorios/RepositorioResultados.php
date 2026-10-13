<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\ConexionBaseDatos;

final class RepositorioResultados
{
    public function periodosDisponibles(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query("SELECT p.id, p.anio, p.mes, p.estado, (SELECT COUNT(*) FROM versiones_calculo vc WHERE vc.periodo_id = p.id) AS versiones FROM periodos p WHERE p.estado = 'aprobado' ORDER BY p.anio DESC, p.mes DESC");
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function resultadosLocales(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query("SELECT p.id AS periodo_id, p.anio, p.mes, p.estado, i.codigo AS indicador, rc.valor, rc.conexiones_utilizadas, rc.cobertura_esperada, rc.cobertura_obtenida, rc.estado AS estado_resultado, vc.numero_version, vc.ejecutado_en FROM versiones_calculo vc INNER JOIN periodos p ON p.id = vc.periodo_id INNER JOIN indicadores i ON i.id = vc.indicador_id INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id WHERE p.estado IN ('aprobado', 'cerrado') AND rc.tipo_ambito = 'localidad' AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id) ORDER BY p.anio DESC, p.mes DESC, i.codigo ASC");
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function evolucionLocalidad(string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT base.*,
                    SUM(base.valor * base.conexiones_utilizadas) OVER (PARTITION BY base.anio ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                    / NULLIF(SUM(base.conexiones_utilizadas) OVER (PARTITION BY base.anio ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW), 0) AS promedio_acumulado,
                    COUNT(*) OVER (PARTITION BY base.anio ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS meses_acumulados
             FROM (
                 SELECT p.id AS periodo_id, p.anio, p.mes, p.estado, rc.valor, rc.conexiones_utilizadas,
                        rc.cobertura_esperada, rc.cobertura_obtenida, rc.estado AS estado_resultado, vc.numero_version
                 FROM versiones_calculo vc
                 INNER JOIN periodos p ON p.id = vc.periodo_id
                 INNER JOIN indicadores i ON i.id = vc.indicador_id
                 INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
                 WHERE p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
                   AND rc.tipo_ambito = 'localidad' AND rc.valor IS NOT NULL
                   AND vc.numero_version = (
                       SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2
                       WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id
                   )
             ) base
             ORDER BY base.anio DESC, base.mes DESC"
        );
        $consulta->execute(['indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function evolucionZonasGeograficas(string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT base.*,
                    SUM(base.valor * base.conexiones_utilizadas) OVER (PARTITION BY base.anio, base.zona_geografica_id ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                    / NULLIF(SUM(base.conexiones_utilizadas) OVER (PARTITION BY base.anio, base.zona_geografica_id ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW), 0) AS promedio_acumulado,
                    COUNT(*) OVER (PARTITION BY base.anio, base.zona_geografica_id ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS meses_acumulados
             FROM (
                 SELECT p.id AS periodo_id, p.anio, p.mes, p.estado, zg.id AS zona_geografica_id,
                        zg.nombre AS zona_geografica, rc.valor, rc.conexiones_utilizadas,
                        rc.cobertura_esperada, rc.cobertura_obtenida, rc.estado AS estado_resultado, vc.numero_version
                 FROM versiones_calculo vc
                 INNER JOIN periodos p ON p.id = vc.periodo_id
                 INNER JOIN indicadores i ON i.id = vc.indicador_id
                 INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
                 INNER JOIN zonas_geograficas zg ON zg.id = rc.ambito_id
                 WHERE p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
                   AND rc.tipo_ambito = 'zona_geografica' AND rc.valor IS NOT NULL
                   AND vc.numero_version = (
                       SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2
                       WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id
                   )
             ) base
             ORDER BY base.anio DESC, base.mes DESC, base.zona_geografica"
        );
        $consulta->execute(['indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function evolucionSectoresConFuentes(string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "WITH base AS (
                SELECT p.id AS periodo_id, p.anio, p.mes, p.estado, so.id AS sector_id, so.nombre AS sector_operacional,
                       fa.id AS fuente_id, fa.nombre AS fuente_abastecimiento, rc.valor, rc.conexiones_utilizadas,
                       rc.cobertura_esperada, rc.cobertura_obtenida, rc.estado AS estado_resultado
                FROM versiones_calculo vc
                INNER JOIN periodos p ON p.id = vc.periodo_id
                INNER JOIN indicadores i ON i.id = vc.indicador_id
                INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
                INNER JOIN sectores_operacionales so ON so.id = rc.ambito_id
                INNER JOIN fuentes_sectores fs ON fs.sector_operacional_id = so.id
                    AND fs.vigente_desde <= LAST_DAY(CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
                    AND (fs.vigente_hasta IS NULL OR fs.vigente_hasta >= CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
                INNER JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id
                WHERE p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
                  AND rc.tipo_ambito = 'sector_operacional' AND rc.valor IS NOT NULL
                  AND vc.numero_version = (
                      SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2
                      WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id
                  )
            ), fuente_periodo AS (
                SELECT periodo_id, anio, mes, fuente_id,
                       SUM(valor * conexiones_utilizadas) AS valor_ponderado,
                       SUM(conexiones_utilizadas) AS conexiones
                FROM base
                GROUP BY periodo_id, anio, mes, fuente_id
            ), fuente_evolucion AS (
                SELECT fuente_periodo.*,
                       SUM(valor_ponderado) OVER (PARTITION BY anio, fuente_id ORDER BY mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                       / NULLIF(SUM(conexiones) OVER (PARTITION BY anio, fuente_id ORDER BY mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW), 0) AS promedio_fuente_acumulado
                FROM fuente_periodo
            )
            SELECT base.*,
                   SUM(base.valor * base.conexiones_utilizadas) OVER (PARTITION BY base.anio, base.sector_id ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW)
                   / NULLIF(SUM(base.conexiones_utilizadas) OVER (PARTITION BY base.anio, base.sector_id ORDER BY base.mes ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW), 0) AS promedio_sector_acumulado,
                   fuente_evolucion.promedio_fuente_acumulado
            FROM base
            INNER JOIN fuente_evolucion ON fuente_evolucion.periodo_id = base.periodo_id AND fuente_evolucion.fuente_id = base.fuente_id
            ORDER BY base.anio DESC, base.mes DESC, base.fuente_abastecimiento, base.sector_operacional"
        );
        $consulta->execute(['indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function evolucionZonasOperativas(string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.id AS periodo_id, p.anio, p.mes, p.estado, fa.nombre AS fuente_abastecimiento, so.id AS sector_id,
                    so.nombre AS sector_operacional, zp.id AS zona_id, zp.nombre AS zona_presion,
                    rc.valor, rc.conexiones_utilizadas, rc.cobertura_esperada, rc.cobertura_obtenida
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
             INNER JOIN zonas_presion zp ON zp.id = rc.ambito_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             INNER JOIN fuentes_sectores fs ON fs.sector_operacional_id = so.id
                AND fs.vigente_desde <= LAST_DAY(CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
                AND (fs.vigente_hasta IS NULL OR fs.vigente_hasta >= CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
             INNER JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id
             WHERE p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
               AND rc.tipo_ambito = 'zona_presion' AND rc.valor IS NOT NULL
               AND vc.numero_version = (
                   SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2
                   WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id
               )
             ORDER BY p.anio DESC, p.mes DESC, fa.nombre, so.codigo, FIELD(zp.nombre, 'Alta', 'Media', 'Baja')"
        );
        $consulta->execute(['indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function evolucionPuntosControl(string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.id AS periodo_id, p.anio, p.mes, p.estado AS estado_periodo,
                    pc.codigo AS punto_control, pc.direccion, rm.valor_decimal AS valor, rm.estado_dato
             FROM registros_mensuales rm
             INNER JOIN periodos p ON p.id = rm.periodo_id
             INNER JOIN indicadores i ON i.id = rm.indicador_id
             INNER JOIN puntos_control pc ON pc.id = rm.punto_control_id
             WHERE p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
               AND rm.valor_decimal IS NOT NULL AND rm.estado_dato = 'registrado'
             ORDER BY p.anio DESC, p.mes DESC, pc.codigo"
        );
        $consulta->execute(['indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function serieOficial(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query("SELECT p.anio, p.mes, i.codigo AS indicador, rc.valor FROM versiones_calculo vc INNER JOIN periodos p ON p.id = vc.periodo_id INNER JOIN indicadores i ON i.id = vc.indicador_id INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id WHERE p.estado IN ('aprobado', 'cerrado') AND rc.tipo_ambito = 'localidad' AND rc.valor IS NOT NULL AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id) ORDER BY p.anio ASC, p.mes ASC, i.codigo ASC");
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function detallePeriodo(int $periodoId): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.id, p.anio, p.mes, p.estado, i.codigo AS indicador, rc.tipo_ambito, rc.valor,
                    rc.conexiones_utilizadas, rc.cobertura_esperada, rc.cobertura_obtenida, rc.estado AS estado_resultado,
                    vc.numero_version,
                    CASE rc.tipo_ambito
                        WHEN 'zona_presion' THEN CONCAT(so_zona.codigo, ' · ', zp.nombre)
                        WHEN 'sector_operacional' THEN so.nombre
                        WHEN 'zona_geografica' THEN zg.nombre
                        WHEN 'fuente_abastecimiento' THEN fa.nombre
                        WHEN 'localidad' THEN l.nombre
                    END AS ambito_nombre
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
             LEFT JOIN zonas_presion zp ON zp.id = rc.ambito_id AND rc.tipo_ambito = 'zona_presion'
             LEFT JOIN sectores_operacionales so_zona ON so_zona.id = zp.sector_operacional_id
             LEFT JOIN sectores_operacionales so ON so.id = rc.ambito_id AND rc.tipo_ambito = 'sector_operacional'
             LEFT JOIN zonas_geograficas zg ON zg.id = rc.ambito_id AND rc.tipo_ambito = 'zona_geografica'
             LEFT JOIN fuentes_abastecimiento fa ON fa.id = rc.ambito_id AND rc.tipo_ambito = 'fuente_abastecimiento'
             LEFT JOIN localidades l ON l.id = rc.ambito_id AND rc.tipo_ambito = 'localidad'
             WHERE p.id = :periodo_id
               AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id)
             ORDER BY i.codigo, FIELD(rc.tipo_ambito, 'localidad', 'zona_geografica', 'fuente_abastecimiento', 'sector_operacional', 'zona_presion'), ambito_nombre"
        );
        $consulta->execute(['periodo_id' => $periodoId]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function medicionesPorPunto(int $periodoId, string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT pc.codigo, pc.direccion, zp.nombre AS zona_presion, so.nombre AS sector_operacional,
                    rm.valor_decimal AS valor, rm.estado_dato
             FROM registros_mensuales rm
             INNER JOIN indicadores i ON i.id = rm.indicador_id
             INNER JOIN puntos_control pc ON pc.id = rm.punto_control_id
             INNER JOIN zonas_presion zp ON zp.id = pc.zona_presion_id
             INNER JOIN sectores_operacionales so ON so.id = zp.sector_operacional_id
             WHERE rm.periodo_id = :periodo_id AND i.codigo = :indicador
             ORDER BY so.codigo, zp.nombre, pc.codigo"
        );
        $consulta->execute(['periodo_id' => $periodoId, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fuentesPeriodo(int $periodoId, string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT p.id, p.anio, p.mes, p.estado, i.codigo AS indicador,
                    'fuente_abastecimiento' AS tipo_ambito, fa.id AS ambito_id, fa.nombre AS ambito_nombre,
                    SUM(rc.valor * rc.conexiones_utilizadas) / NULLIF(SUM(rc.conexiones_utilizadas), 0) AS valor,
                    SUM(rc.conexiones_utilizadas) AS conexiones_utilizadas,
                    SUM(rc.cobertura_esperada) AS cobertura_esperada,
                    SUM(rc.cobertura_obtenida) AS cobertura_obtenida,
                    CASE WHEN SUM(rc.cobertura_obtenida) = 0 THEN 'no_disponible'
                         WHEN SUM(rc.cobertura_obtenida) = SUM(rc.cobertura_esperada) THEN 'completo'
                         ELSE 'parcial' END AS estado_resultado,
                    vc.numero_version
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id AND rc.tipo_ambito = 'sector_operacional'
             INNER JOIN fuentes_sectores fs ON fs.sector_operacional_id = rc.ambito_id
                AND fs.vigente_desde <= LAST_DAY(CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
                AND (fs.vigente_hasta IS NULL OR fs.vigente_hasta >= CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
             INNER JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id
             WHERE p.id = :periodo_id AND i.codigo = :indicador AND rc.valor IS NOT NULL
               AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id)
             GROUP BY p.id, p.anio, p.mes, p.estado, i.codigo, fa.id, fa.nombre, vc.numero_version
             ORDER BY fa.nombre"
        );
        $consulta->execute(['periodo_id' => $periodoId, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function resumenAnualLocalidad(int $anio, string $indicador): ?array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT SUM(rc.valor * rc.conexiones_utilizadas) / NULLIF(SUM(rc.conexiones_utilizadas), 0) AS valor,
                    SUM(rc.conexiones_utilizadas) AS conexiones, COUNT(*) AS meses
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
             WHERE p.anio = :anio AND p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
               AND rc.tipo_ambito = 'localidad' AND rc.valor IS NOT NULL
               AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id)"
        );
        $consulta->execute(['anio' => $anio, 'indicador' => $indicador]);
        $resultado = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $resultado === false ? null : $resultado;
    }

    public function resumenAnualZonasGeograficas(int $anio, string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT zg.nombre AS zona_geografica,
                    SUM(rc.valor * rc.conexiones_utilizadas) / NULLIF(SUM(rc.conexiones_utilizadas), 0) AS valor,
                    SUM(rc.conexiones_utilizadas) AS conexiones, COUNT(*) AS meses
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
             INNER JOIN zonas_geograficas zg ON zg.id = rc.ambito_id
             WHERE p.anio = :anio AND p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
               AND rc.tipo_ambito = 'zona_geografica' AND rc.valor IS NOT NULL
               AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id)
             GROUP BY zg.id, zg.nombre ORDER BY zg.nombre"
        );
        $consulta->execute(['anio' => $anio, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function resumenAnualSectores(int $anio, string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT so.nombre AS ambito,
                    SUM(rc.valor * rc.conexiones_utilizadas) / NULLIF(SUM(rc.conexiones_utilizadas), 0) AS valor,
                    SUM(rc.conexiones_utilizadas) AS conexiones, COUNT(*) AS meses
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id
             INNER JOIN sectores_operacionales so ON so.id = rc.ambito_id
             WHERE p.anio = :anio AND p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
               AND rc.tipo_ambito = 'sector_operacional' AND rc.valor IS NOT NULL
               AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id)
             GROUP BY so.id, so.nombre ORDER BY so.codigo"
        );
        $consulta->execute(['anio' => $anio, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function resumenAnualFuentes(int $anio, string $indicador): array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            "SELECT fa.nombre AS ambito,
                    SUM(rc.valor * rc.conexiones_utilizadas) / NULLIF(SUM(rc.conexiones_utilizadas), 0) AS valor,
                    SUM(rc.conexiones_utilizadas) AS conexiones, COUNT(DISTINCT p.id) AS meses
             FROM versiones_calculo vc
             INNER JOIN periodos p ON p.id = vc.periodo_id
             INNER JOIN indicadores i ON i.id = vc.indicador_id
             INNER JOIN resultados_calculo rc ON rc.version_calculo_id = vc.id AND rc.tipo_ambito = 'sector_operacional'
             INNER JOIN fuentes_sectores fs ON fs.sector_operacional_id = rc.ambito_id
                AND fs.vigente_desde <= LAST_DAY(CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
                AND (fs.vigente_hasta IS NULL OR fs.vigente_hasta >= CONCAT(p.anio, '-', LPAD(p.mes, 2, '0'), '-01'))
             INNER JOIN fuentes_abastecimiento fa ON fa.id = fs.fuente_abastecimiento_id
             WHERE p.anio = :anio AND p.estado IN ('aprobado', 'cerrado') AND i.codigo = :indicador
               AND rc.valor IS NOT NULL
               AND vc.numero_version = (SELECT MAX(vc2.numero_version) FROM versiones_calculo vc2 WHERE vc2.periodo_id = vc.periodo_id AND vc2.indicador_id = vc.indicador_id)
             GROUP BY fa.id, fa.nombre ORDER BY fa.nombre"
        );
        $consulta->execute(['anio' => $anio, 'indicador' => $indicador]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    }
}
