<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Repositorios\RepositorioMediciones;
use App\Servicios\ServicioAutenticacion;

final class ConexionController
{
    public function guardar(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.');
            $this->redirigir();
        }
        $mes = filter_var($_POST['mes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $zonaPresionId = filter_var($_POST['zona_presion_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $observacion = trim((string) ($_POST['observacion'] ?? ''));
        if ($mes === false || $zonaPresionId === false || $cantidad === false || strlen($observacion) > 500) {
            Sesion::mensaje('error', 'Revisa el período, zona de presión, cantidad y observación antes de guardar.');
            $this->redirigir();
        }
        try {
            $usuario = ServicioAutenticacion::usuario();
            (new RepositorioMediciones())->guardarConexionManual((int) $usuario['id'], $mes, $zonaPresionId, $cantidad, $observacion === '' ? null : $observacion);
            Sesion::mensaje('exito', 'La conexión activa se guardó correctamente en borrador.');
        } catch (\DomainException $error) {
            Sesion::mensaje('error', $error->getMessage());
        } catch (\Throwable $error) {
            Sesion::mensaje('error', 'No se pudo guardar la conexión activa. Inténtalo nuevamente.');
        }
        $this->redirigir();
    }

    private function redirigir(): never
    {
        header('Location: ' . url('/conexiones'));
        exit;
    }
}
