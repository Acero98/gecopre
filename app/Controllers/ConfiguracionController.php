<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Core\View;
use App\Repositorios\RepositorioCatalogo;
use App\Repositorios\RepositorioConfiguracion;
use App\Servicios\ServicioAutenticacion;

final class ConfiguracionController
{
    public function index(): void
    {
        View::render('settings/index', [
            'title' => 'Configuración',
            'active' => '/configuracion',
            'metasContinuidad' => (new RepositorioConfiguracion())->metasContinuidad(),
            'metasPresion' => (new RepositorioConfiguracion())->metasPresion(),
            'catalogo' => (new RepositorioCatalogo())->resumen(),
        ]);
    }

    public function guardarMetas(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) { Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.'); $this->redirigir(); }
        $indicador = (string) ($_POST['indicador'] ?? '');
        $sunass = filter_var($_POST['sunass'] ?? null, FILTER_VALIDATE_FLOAT);
        $eps = filter_var($_POST['eps'] ?? null, FILTER_VALIDATE_FLOAT);
        if (!in_array($indicador, ['continuidad', 'presion'], true)) { Sesion::mensaje('error', 'Selecciona un indicador válido.'); $this->redirigir(); }
        if ($sunass === false || $eps === false || $sunass <= 0 || $eps <= 0) { Sesion::mensaje('error', 'Ingresa metas numéricas mayores que cero.'); $this->redirigir(); }
        try {
            $usuario = ServicioAutenticacion::usuario();
            $repositorio = new RepositorioConfiguracion();
            if ($indicador === 'continuidad') { $repositorio->guardarMetasContinuidad((int) $usuario['id'], (float) $sunass, (float) $eps); }
            else { $repositorio->guardarMetasPresion((int) $usuario['id'], (float) $sunass, (float) $eps); }
            Sesion::mensaje('exito', 'Las metas de ' . ($indicador === 'continuidad' ? 'continuidad' : 'presión') . ' se actualizaron correctamente.');
        } catch (\Throwable $error) { Sesion::mensaje('error', 'No se pudieron actualizar las metas. Inténtalo nuevamente.'); }
        $this->redirigir();
    }

    private function redirigir(): never { header('Location: ' . url('/configuracion')); exit; }
}
