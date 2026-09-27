<?php
declare(strict_types=1);

namespace App\Core;

final class CargadorEntorno
{
    public static function cargar(string $archivo): void
    {
        if (!is_file($archivo)) {
            return;
        }

        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            if ($clave === '' || getenv($clave) !== false) {
                continue;
            }

            if ((str_starts_with($valor, '"') && str_ends_with($valor, '"')) || (str_starts_with($valor, "'") && str_ends_with($valor, "'"))) {
                $valor = substr($valor, 1, -1);
            }

            putenv($clave . '=' . $valor);
            $_ENV[$clave] = $valor;
        }
    }
}
