<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\ConexionBaseDatos;

final class RepositorioCatalogo
{
    public function resumen(): array
    {
        $consulta = ConexionBaseDatos::obtener()->query(
            'SELECT
                (SELECT COUNT(*) FROM localidades WHERE activo = 1) AS localidades,
                (SELECT COUNT(*) FROM zonas_geograficas WHERE activo = 1) AS zonas_geograficas,
                (SELECT COUNT(*) FROM sectores_operacionales WHERE activo = 1) AS sectores_operacionales,
                (SELECT COUNT(*) FROM puntos_control WHERE activo = 1) AS puntos_control'
        );

        return $consulta->fetch() ?: [
            'localidades' => 0,
            'zonas_geograficas' => 0,
            'sectores_operacionales' => 0,
            'puntos_control' => 0,
        ];
    }
}
