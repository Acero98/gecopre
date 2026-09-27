<?php
declare(strict_types=1);

namespace App\Controladores;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Core\View;
use App\Repositorios\RepositorioUsuario;
use App\Servicios\ServicioAutenticacion;

final class AutenticacionController
{
    public function instalacion(): void
    {
        if (ServicioAutenticacion::hayUsuarios()) {
            $this->redirigir('/ingresar');
        }
        View::render('autenticacion/instalacion', ['title' => 'Crear administrador', 'layout' => 'layouts/autenticacion', 'active' => '']);
    }

    public function crearAdministrador(): void
    {
        if (ServicioAutenticacion::hayUsuarios()) {
            $this->redirigir('/ingresar');
        }
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            Sesion::mensaje('error', 'La sesión del formulario venció. Inténtalo nuevamente.');
            $this->redirigir('/instalacion');
        }

        $nombres = trim((string) ($_POST['nombres'] ?? ''));
        $apellidos = trim((string) ($_POST['apellidos'] ?? ''));
        $correo = trim((string) ($_POST['correo'] ?? ''));
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion_contrasena'] ?? '');

        if ($nombres === '' || $apellidos === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($contrasena) < 10 || $contrasena !== $confirmacion) {
            Sesion::mensaje('error', 'Completa los datos. La contraseña debe tener al menos 10 caracteres y coincidir en ambos campos.');
            $this->redirigir('/instalacion');
        }

        try {
            (new RepositorioUsuario())->crearAdministrador($nombres, $apellidos, $correo, $contrasena);
            ServicioAutenticacion::iniciar($correo, $contrasena);
            Sesion::mensaje('exito', 'Administrador creado correctamente.');
            $this->redirigir('/');
        } catch (\Throwable) {
            Sesion::mensaje('error', 'No se pudo crear el administrador. Verifica que el correo no esté registrado.');
            $this->redirigir('/instalacion');
        }
    }

    public function ingresar(): void
    {
        if (!ServicioAutenticacion::hayUsuarios()) {
            $this->redirigir('/instalacion');
        }
        if (ServicioAutenticacion::usuario() !== null) {
            $this->redirigir('/');
        }
        View::render('autenticacion/ingresar', ['title' => 'Ingresar', 'layout' => 'layouts/autenticacion', 'active' => '']);
    }

    public function autenticar(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            Sesion::mensaje('error', 'La sesión del formulario venció. Inténtalo nuevamente.');
            $this->redirigir('/ingresar');
        }
        $correo = (string) ($_POST['correo'] ?? '');
        $contrasena = (string) ($_POST['contrasena'] ?? '');
        if (!ServicioAutenticacion::iniciar($correo, $contrasena)) {
            Sesion::mensaje('error', 'Credenciales incorrectas o usuario inactivo.');
            $this->redirigir('/ingresar');
        }
        $this->redirigir('/');
    }

    public function salir(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            http_response_code(419);
            echo 'La sesión del formulario venció.';
            return;
        }
        ServicioAutenticacion::cerrar();
        Sesion::mensaje('exito', 'La sesión se cerró correctamente.');
        $this->redirigir('/ingresar');
    }

    private function redirigir(string $ruta): never
    {
        header('Location: ' . url($ruta));
        exit;
    }
}
