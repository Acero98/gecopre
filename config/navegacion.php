<?php
declare(strict_types=1);

return [
    ['path' => '/', 'label' => 'Resumen', 'icon' => 'grid-1x2'],
    ['path' => '/registros', 'label' => 'Registro mensual', 'icon' => 'pencil-square'],
    ['path' => '/conexiones', 'label' => 'Conexiones', 'icon' => 'diagram-3'],
    ['path' => '/revision', 'label' => 'Revisión y cierre', 'icon' => 'clipboard-check'],
    ['path' => '/resultados', 'label' => 'Resultados', 'icon' => 'bar-chart-line', 'subsecciones' => [
        ['indicador' => 'continuidad', 'label' => 'Continuidad', 'icon' => 'clock-history'],
        ['indicador' => 'presion', 'label' => 'Presión', 'icon' => 'speedometer2'],
    ]],
    ['path' => '/predicciones', 'label' => 'Predicciones', 'icon' => 'graph-up-arrow'],
    ['path' => '/configuracion', 'label' => 'Configuración', 'icon' => 'sliders'],
];
