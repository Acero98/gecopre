<?php
declare(strict_types=1);

namespace App\Core;

final class ProteccionFormulario
{
    public static function token(): string
    {
        $token = Sesion::obtener('token_formulario');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Sesion::guardar('token_formulario', $token);
        }
        return $token;
    }

    public static function validar(?string $token): bool
    {
        $actual = Sesion::obtener('token_formulario');
        return is_string($token) && is_string($actual) && hash_equals($actual, $token);
    }
}
