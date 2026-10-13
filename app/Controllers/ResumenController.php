<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositorios\RepositorioMediciones;
use App\Repositorios\RepositorioResultados;
use App\Repositorios\RepositorioConfiguracion;

final class ResumenController
{
    public function index(): void
    {
        $mediciones = new RepositorioMediciones();
        $resultados = new RepositorioResultados();
        View::render('dashboard/index', [
            'title' => 'Resumen', 'active' => '/',
            'operacion' => $mediciones->resumenOperativo(),
            'mediciones' => $mediciones->resumen(),
            'serie' => $resultados->serieOficial(),
        ]);
    }

    public function results(): void
    {
        $repositorio = new RepositorioResultados();
        $indicadorSeleccionado = $_GET['indicador'] ?? null;
        if (!is_string($indicadorSeleccionado) || !in_array($indicadorSeleccionado, ['continuidad', 'presion'], true)) {
            $indicadorSeleccionado = null;
        }
        View::render('results/index', [
            'title' => 'Resultados', 'active' => '/resultados',
            'indicadorSeleccionado' => $indicadorSeleccionado,
            'periodosDisponibles' => $repositorio->periodosDisponibles(),
            'continuidadLocalidad' => $repositorio->evolucionLocalidad('continuidad'),
            'presionLocalidad' => $repositorio->evolucionLocalidad('presion'),
            'continuidadZonasGeograficas' => $repositorio->evolucionZonasGeograficas('continuidad'),
            'presionZonasGeograficas' => $repositorio->evolucionZonasGeograficas('presion'),
            'presionSectoresFuentes' => $repositorio->evolucionSectoresConFuentes('presion'),
            'metasContinuidad' => (new RepositorioConfiguracion())->metasContinuidad(),
            'metasPresion' => (new RepositorioConfiguracion())->metasPresion(),
            'recursos' => ['js' => ['sweetalert2.all.min.js']],
        ]);
    }
}
