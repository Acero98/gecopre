<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Core\View;
use App\Repositorios\RepositorioMapaPresion;
use App\Servicios\ServicioAutenticacion;

final class MapaPresionController
{
    public function index(): void
    {
        header('Cache-Control: no-store, max-age=0');
        $repositorio = new RepositorioMapaPresion();
        $periodos = $repositorio->periodosDisponibles();
        $periodoId = filter_input(INPUT_GET, 'periodo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: (int) ($periodos[0]['id'] ?? 0);
        $puntoId = filter_input(INPUT_GET, 'punto', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $indicador = ($_GET['indicador'] ?? 'presion') === 'continuidad' ? 'continuidad' : 'presion';
        View::render('mapa_presion/index', ['title' => 'Mapa de presión', 'active' => '/mapa-presion', 'periodos' => $periodos, 'periodoId' => $periodoId, 'puntoId' => $puntoId, 'indicador' => $indicador, 'puntos' => $periodoId > 0 ? $repositorio->puntosParaMapa($periodoId, $indicador) : [], 'recursos' => ['css_externos' => ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'], 'js_externos' => ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'], 'js' => ['mapa-presion.js']]]);
    }

    public function ubicaciones(): void
    {
        View::render('mapa_presion/ubicaciones', ['title' => 'Ubicaciones de puntos', 'active' => '/mapa-presion', 'puntos' => (new RepositorioMapaPresion())->puntosUbicacion()]);
    }

    public function guardarUbicacion(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) { Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.'); $this->redirigirUbicaciones(); }
        $puntoId = filter_var($_POST['punto_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $latitud = filter_var($_POST['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitud = filter_var($_POST['longitud'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($puntoId === false || $latitud === false || $longitud === false || $latitud < -18 || $latitud > -17 || $longitud < -72 || $longitud > -70) { Sesion::mensaje('error', 'Ingresa coordenadas válidas para Ilo.'); $this->redirigirUbicaciones(); }
        try { (new RepositorioMapaPresion())->actualizarCoordenadas((int) $puntoId, (float) $latitud, (float) $longitud, (int) ServicioAutenticacion::usuario()['id']); Sesion::mensaje('exito', 'Ubicación actualizada.'); } catch (\Throwable) { Sesion::mensaje('error', 'No se pudo actualizar la ubicación.'); }
        header('Location: ' . url('/mapa-presion') . '&punto=' . (int) $puntoId . '&actualizado=' . time());
        exit;
    }

    private function redirigirUbicaciones(): never { header('Location: ' . url('/mapa-presion/ubicaciones')); exit; }
}
