<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositorios\RepositorioCatalogo;
use App\Repositorios\RepositorioMediciones;
use App\Repositorios\RepositorioResultados;
use App\Servicios\ServicioPrediccion;

final class ModuloController
{
    public function records(): void
    {
        $repositorio = new RepositorioMediciones();
        View::render('records/index', [
            'title' => 'Registro mensual',
            'active' => '/registros',
            'resumen' => $repositorio->resumen(),
            'registros' => $repositorio->recientes(),
            'conexionesPorPeriodo' => $repositorio->conexionesPorPeriodo(),
            'puntosControl' => $repositorio->puntosActivos(),
            'mesPredeterminado' => $repositorio->siguienteMesDisponible(),
            'recursos' => [
                'css' => ['simple-datatables.css', 'tom-select.css'],
                'js' => ['simple-datatables.js', 'tom-select.complete.min.js', 'sweetalert2.all.min.js'],
            ],
        ]);
    }

    public function connections(): void
    {
        $repositorio = new RepositorioMediciones();
        View::render('connections/index', [
            'title' => 'Conexiones activas',
            'active' => '/conexiones',
            'resumen' => $repositorio->resumenConexiones(),
            'zonasPresion' => $repositorio->zonasPresionActivas(),
            'conexiones' => $repositorio->conexionesRecientes(),
            'mesPredeterminado' => $repositorio->siguienteMesDisponible(),
            'recursos' => [
                'css' => ['simple-datatables.css', 'tom-select.css'],
                'js' => ['simple-datatables.js', 'tom-select.complete.min.js', 'sweetalert2.all.min.js'],
            ],
        ]);
    }

    public function review(): void
    {
        View::render('review/index', [
            'title' => 'Revisión y cierre',
            'active' => '/revision',
            'periodos' => (new RepositorioMediciones())->periodosParaRevision(),
            'recursos' => [
                'js' => ['sweetalert2.all.min.js'],
            ],
        ]);
    }

    public function reviewDetail(): void
    {
        $periodoId = filter_input(INPUT_GET, 'periodo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodoId === false || $periodoId === null) {
            header('Location: ' . url('/revision'));
            exit;
        }
        $repositorio = new RepositorioMediciones();
        $periodo = $repositorio->detallePeriodoRevision($periodoId);
        if ($periodo === null) {
            header('Location: ' . url('/revision'));
            exit;
        }
        View::render('review/detail', [
            'title' => 'Detalle de revisión',
            'active' => '/revision',
            'periodo' => $periodo,
            'mediciones' => $repositorio->medicionesDePeriodo($periodoId),
            'conexiones' => $repositorio->conexionesDePeriodo($periodoId),
        ]);
    }

    public function forecasts(): void
    {
        $serie = (new RepositorioResultados())->serieOficial();
        View::render('forecasts/index', [
            'title' => 'Predicciones',
            'active' => '/predicciones',
            'serie' => $serie,
            'predicciones' => (new ServicioPrediccion())->generar($serie),
        ]);
    }

    public function settings(): void
    {
        View::render('modules/index', [
            'title' => 'Configuración',
            'active' => '/configuracion',
            'icon' => 'sliders',
            'description' => 'La estructura de la red y las reglas compartidas del sistema.',
            'features' => [
                'Localidades, zonas, reservorios, sectores y puntos de control',
                'Metas vigentes y plan de monitoreo',
                'Usuarios, roles y ámbitos de acceso',
            ],
            'catalogo' => (new RepositorioCatalogo())->resumen(),
        ]);
    }

    private function module(string $path, string $title, string $icon, string $description, array $features): void
    {
        View::render('modules/index', compact('title', 'icon', 'description', 'features') + ['active' => $path]);
    }
}
