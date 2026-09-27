<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Repositorios\RepositorioMediciones;
use App\Servicios\ServicioAutenticacion;
use App\Servicios\ServicioRegistroMensual;

final class RegistroController
{
    public function guardar(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) {
            Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.');
            $this->redirigir();
        }
        $mes = filter_var($_POST['mes'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]);
        $puntoId = filter_var($_POST['punto_control_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $indicador = (string) ($_POST['indicador'] ?? '');
        $observacion = trim((string) ($_POST['observacion'] ?? ''));
        if ($mes === false || $puntoId === false || strlen($observacion) > 500) {
            Sesion::mensaje('error', 'Revisa el período, punto de control y observación antes de guardar.');
            $this->redirigir();
        }
        try {
            $valor = ServicioRegistroMensual::normalizarValor($indicador, (string) ($_POST['valor'] ?? ''));
            $usuario = ServicioAutenticacion::usuario();
            (new RepositorioMediciones())->guardarManual((int) $usuario['id'], $mes, $puntoId, $indicador, $valor, $observacion === '' ? null : $observacion);
            Sesion::mensaje('exito', 'La medición se guardó correctamente en borrador.');
        } catch (\DomainException $error) {
            Sesion::mensaje('error', $error->getMessage());
        } catch (\Throwable) {
            Sesion::mensaje('error', 'No se pudo guardar la medición. Inténtalo nuevamente.');
        }
        $this->redirigir();
    }

    private function redirigir(): never
    {
        header('Location: ' . url('/registros'));
        exit;
    }
}
