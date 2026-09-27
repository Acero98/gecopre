<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositorios\RepositorioCatalogo;
use App\Repositorios\RepositorioMediciones;

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
        ]);
    }

    public function connections(): void
    {
        $this->module('/conexiones', 'Conexiones activas', 'diagram-3', 'Una única base de conexiones para continuidad y presión.', [
            'Registro por zona de presión y período', 'Validación de cantidades y datos faltantes', 'Historial de cambios y responsable',
        ]);
    }

    public function review(): void
    {
        $this->module('/revision', 'Revisión y cierre', 'clipboard-check', 'Un flujo claro antes de publicar los resultados del mes.', [
            'Borrador → en revisión → aprobado → cerrado', 'Devolución con observaciones al operador', 'Reaperturas justificadas y resultados versionados',
        ]);
    }

    public function forecasts(): void
    {
        $this->module('/predicciones', 'Predicciones', 'graph-up-arrow', 'Estimaciones de continuidad y presión a uno o dos meses.', [
            'Fecha de corte y horizonte explícitos', 'Comparación de métodos con pruebas históricas', 'Estimación, error y supuestos separados de los datos reales',
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
