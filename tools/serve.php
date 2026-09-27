<?php
declare(strict_types=1);

// Router for the PHP development server. Its document root must be public/.
$public = realpath(dirname(__DIR__) . '/public');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$candidate = realpath($public . $path);
if ($path !== '/' && $candidate !== false && str_starts_with($candidate, $public . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $public . '/index.php';

