<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\ProteccionFormulario;
use App\Core\Sesion;
use App\Core\View;
use App\Repositorios\RepositorioResultados;
use App\Servicios\ServicioAutenticacion;
use App\Servicios\ServicioCalculoResultados;

final class ResultadoController
{
    public function detalle(): void
    {
        $periodoId = filter_input(INPUT_GET, 'periodo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodoId === false || $periodoId === null) { $this->redirigir(); }
        $repositorio = new RepositorioResultados();
        $detalle = $repositorio->detallePeriodo($periodoId);
        if ($detalle === []) { $this->redirigir(); }
        $referencia = $detalle[0];
        foreach (['continuidad', 'presion'] as $indicador) {
            $tieneFuentes = false;
            foreach ($detalle as $fila) {
                if ($fila['indicador'] === $indicador && $fila['tipo_ambito'] === 'fuente_abastecimiento') { $tieneFuentes = true; break; }
            }
            if (!$tieneFuentes) { $detalle = array_merge($detalle, $repositorio->fuentesPeriodo($periodoId, $indicador)); }
        }
        View::render('results/detail', [
            'title' => 'Detalle de resultados',
            'active' => '/resultados',
            'detalle' => $detalle,
            'anio' => (int) $referencia['anio'],
            'mes' => (int) $referencia['mes'],
            'puntosContinuidad' => $repositorio->medicionesPorPunto($periodoId, 'continuidad'),
            'puntosPresion' => $repositorio->medicionesPorPunto($periodoId, 'presion'),
            'anualSectoresContinuidad' => $repositorio->resumenAnualSectores((int) $referencia['anio'], 'continuidad'),
            'anualFuentesContinuidad' => $repositorio->resumenAnualFuentes((int) $referencia['anio'], 'continuidad'),
            'anualSectoresPresion' => $repositorio->resumenAnualSectores((int) $referencia['anio'], 'presion'),
            'anualFuentesPresion' => $repositorio->resumenAnualFuentes((int) $referencia['anio'], 'presion'),
        ]);
    }

    public function calcular(): void
    {
        if (!ProteccionFormulario::validar($_POST['token'] ?? null)) { Sesion::mensaje('error', 'La sesión del formulario venció. Vuelve a intentarlo.'); $this->redirigir(); }
        $periodoId = filter_var($_POST['periodo_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodoId === false) { Sesion::mensaje('error', 'Selecciona un período válido.'); $this->redirigir(); }
        try {
            $usuario = ServicioAutenticacion::usuario();
            (new ServicioCalculoResultados())->calcularPeriodo((int) $usuario['id'], $periodoId);
            Sesion::mensaje('exito', 'El cálculo oficial se generó y quedó versionado.');
        } catch (\DomainException $error) { Sesion::mensaje('error', $error->getMessage());
        } catch (\Throwable $error) { Sesion::mensaje('error', 'No se pudo generar el cálculo. Inténtalo nuevamente.'); }
        $this->redirigir();
    }
    private function redirigir(): never { header('Location: ' . url('/resultados')); exit; }
}
