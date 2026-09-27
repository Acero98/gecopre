<?php
declare(strict_types=1);

$base = $argv[1] ?? 'http://127.0.0.1:8088';
$checks = [
    ['/index.php?route=%2F', 200, 'Estado del servicio'],
    ['/index.php?route=%2Fregistros', 200, 'Validar ejemplo'],
    ['/index.php?route=%2Fresultados', 200, 'Resultados mensuales'],
    ['/index.php?route=%2Fconexiones', 200, 'Conexiones activas'],
    ['/index.php?route=%2Frevision', 200, 'Revisión y cierre'],
    ['/index.php?route=%2Fpredicciones', 200, 'Predicciones'],
    ['/index.php?route=%2Fconfiguracion', 200, 'Configuración'],
    ['/resultados', 200, 'Resultados mensuales'],
    ['/missing', 404, 'No encontramos'],
    ['/index.php?route[]=oops', 400, 'Ruta inválida'],
    ['/app/Models/ResumenReferencia.php', 404, 'No encontramos'],
    ['/package.json', 404, 'No encontramos'],
    ['/assets/css/app.css', 200, 'tailwindcss'],
    ['/assets/js/app.js', 200, 'use strict'],
    ['/assets/icons/bootstrap-icons.css', 200, 'bootstrap-icons'],
];
$failed = 0;
foreach ($checks as [$path, $status, $needle]) {
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
    $body = file_get_contents($base . $path, false, $context);
    $header = $http_response_header[0] ?? '';
    $ok = str_contains($header, ' ' . $status . ' ') && is_string($body) && str_contains($body, $needle);
    echo ($ok ? 'PASS ' : 'FAIL ') . $path . PHP_EOL;
    $failed += $ok ? 0 : 1;
}
$context = stream_context_create(['http' => ['method' => 'POST', 'ignore_errors' => true, 'timeout' => 5]]);
file_get_contents($base . '/index.php?route=%2Fregistros', false, $context);
$ok = str_contains($http_response_header[0] ?? '', ' 405 ');
echo ($ok ? 'PASS' : 'FAIL') . ' unsupported POST' . PHP_EOL;
exit($failed + ($ok ? 0 : 1) > 0 ? 1 : 0);
