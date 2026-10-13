<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Repositorios\RepositorioMediciones;
use App\Servicios\ServicioAutenticacion;

final class RevisionController
{
    public function cambiarEstado(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.');
            $this->redirigir();
        }
        $periodoId = filter_var($_POST['periodo_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $estado = (string) ($_POST['estado'] ?? '');
        $motivo = trim((string) ($_POST['motivo'] ?? ''));
        if ($periodoId === false || !in_array($estado, ['borrador', 'en_revision', 'aprobado', 'cerrado'], true) || strlen($motivo) > 500) {
            Sesion::mensaje('error', 'Revisa los datos del cambio de estado.');
            $this->redirigir();
        }
        try {
            $usuario = ServicioAutenticacion::usuario();
            (new RepositorioMediciones())->cambiarEstadoPeriodo((int) $usuario['id'], $periodoId, $estado, $motivo === '' ? null : $motivo);
            Sesion::mensaje('exito', 'El estado del período se actualizó correctamente.');
        } catch (\DomainException $error) {
            Sesion::mensaje('error', $error->getMessage());
        } catch (\Throwable $error) {
            Sesion::mensaje('error', 'No se pudo actualizar el estado del período. Inténtalo nuevamente.');
        }
        $this->redirigir();
    }

    private function redirigir(): never
    {
        header('Location: ' . url('/revision'));
        exit;
    }
}
