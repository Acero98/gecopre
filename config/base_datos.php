<?php
declare(strict_types=1);

return [
    'host' => getenv('BASE_DATOS_HOST') ?: '127.0.0.1',
    'puerto' => (int) (getenv('BASE_DATOS_PUERTO') ?: 3306),
    'nombre' => getenv('BASE_DATOS_NOMBRE') ?: 'gecopre',
    'usuario' => getenv('BASE_DATOS_USUARIO') ?: 'root',
    'contrasena' => getenv('BASE_DATOS_CONTRASENA') ?: '',
    'charset' => 'utf8mb4',
];
