<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $conexion = App\Core\ConexionBaseDatos::obtener();
    $version = $conexion->query('SELECT VERSION() AS version')->fetchColumn();
    $tablas = $conexion->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    echo 'Conexión establecida con MySQL ' . $version . PHP_EOL;
    echo 'Tablas disponibles: ' . count($tablas) . PHP_EOL;
    echo implode(', ', $tablas) . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo conectar a la base de datos: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
