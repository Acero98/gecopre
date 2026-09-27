<?php
declare(strict_types=1);

namespace App\Servicios;

use App\Core\Sesion;
use App\Repositorios\RepositorioUsuario;

final class ServicioAutenticacion
{
    public static function usuario(): ?array
    {
        $usuario = Sesion::obtener('usuario');
        return is_array($usuario) ? $usuario : null;
    }

    public static function hayUsuarios(): bool
    {
        return (new RepositorioUsuario())->contar() > 0;
    }

    public static function iniciar(string $correo, string $contrasena): bool
    {
        $registro = (new RepositorioUsuario())->buscarPorCorreo($correo);
        if ($registro === null || !$registro['activo'] || !password_verify($contrasena, $registro['contrasena_hash'])) {
            return false;
        }

        Sesion::regenerar();
        Sesion::guardar('usuario', [
            'id' => (int) $registro['id'],
            'nombres' => $registro['nombres'],
            'apellidos' => $registro['apellidos'],
            'correo' => $registro['correo'],
            'roles' => $registro['roles'],
        ]);
        (new RepositorioUsuario())->registrarAcceso((int) $registro['id']);
        return true;
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
        Sesion::iniciar();
    }
}
