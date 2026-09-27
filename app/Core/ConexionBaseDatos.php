<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class ConexionBaseDatos
{
    private static ?PDO $conexion = null;

    public static function obtener(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $configuracion = require BASE_PATH . '/config/base_datos.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $configuracion['host'],
            $configuracion['puerto'],
            $configuracion['nombre'],
            $configuracion['charset']
        );

        self::$conexion = new PDO($dsn, $configuracion['usuario'], $configuracion['contrasena'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$conexion;
    }
}
