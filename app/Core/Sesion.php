<?php
declare(strict_types=1);

namespace App\Core;

final class Sesion
{
    public static function iniciar(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('GECOPRESESSID');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }

    public static function guardar(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function obtener(string $clave, mixed $predeterminado = null): mixed
    {
        return $_SESSION[$clave] ?? $predeterminado;
    }

    public static function eliminar(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    public static function mensaje(string $tipo, string $texto): void
    {
        $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    public static function tomarMensaje(): ?array
    {
        $mensaje = $_SESSION['mensaje'] ?? null;
        unset($_SESSION['mensaje']);
        return $mensaje;
    }
}
