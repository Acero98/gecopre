<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>GECOPRE necesita PHP 8.1 o superior</h1><p>Selecciona PHP 8.1.10 en Laragon y reinicia sus servicios. También puedes usar el servidor local indicado en el README.</p>';
    exit;
}

$config = require dirname(__DIR__) . '/app/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

try {
    $route = $_GET['route'] ?? null;
    if ($route !== null && !is_string($route)) {
        http_response_code(400);
        echo 'Ruta inválida.';
        exit;
    }
    if ($route === null) {
        $route = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_url();
        if ($base !== '' && str_starts_with($route, $base . '/')) {
            $route = substr($route, strlen($base));
        }
        if ($route === '/index.php') {
            $route = '/';
        }
    }
    $route = '/' . trim($route, '/');
    $router = new App\Core\Router();
    require BASE_PATH . '/routes/web.php';
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $route);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    echo 'No se pudo cargar la página. Revisa el registro del servidor.';
}

