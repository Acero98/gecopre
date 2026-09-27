<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

App\Core\CargadorEntorno::cargar(BASE_PATH . '/.env');
App\Core\Sesion::iniciar();
require BASE_PATH . '/app/helpers.php';
$config = require BASE_PATH . '/config/preferencias.php';
date_default_timezone_set($config['timezone']);
return $config;
