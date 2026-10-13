<?php
declare(strict_types=1);

namespace App\Servicios;

final class ServicioPrediccion
{
    public function generar(array $serie): array
    {
        $porIndicador = [];
        foreach ($serie as $fila) {
            $porIndicador[$fila['indicador']][] = $fila;
        }
        $resultado = [];
        foreach ($porIndicador as $indicador => $filas) {
            $muestras = count($filas);
            if ($muestras < 3) {
                $resultado[$indicador] = ['muestras' => $muestras, 'proyecciones' => []];
                continue;
            }
            $sumX = $sumY = $sumXY = $sumX2 = 0.0;
            foreach ($filas as $indice => $fila) {
                $x = (float) ($indice + 1);
                $y = (float) $fila['valor'];
                $sumX += $x;
                $sumY += $y;
                $sumXY += $x * $y;
                $sumX2 += $x * $x;
            }
            $denominador = ($muestras * $sumX2) - ($sumX * $sumX);
            $pendiente = $denominador === 0.0 ? 0.0 : (($muestras * $sumXY) - ($sumX * $sumY)) / $denominador;
            $intercepto = ($sumY - ($pendiente * $sumX)) / $muestras;
            $ultimo = $filas[$muestras - 1];
            $proyecciones = [];
            for ($horizonte = 1; $horizonte <= 2; $horizonte++) {
                $fecha = new \DateTimeImmutable(sprintf('%04d-%02d-01', (int) $ultimo['anio'], (int) $ultimo['mes']));
                $fecha = $fecha->modify('+' . $horizonte . ' month');
                $proyecciones[] = [
                    'anio' => (int) $fecha->format('Y'),
                    'mes' => (int) $fecha->format('n'),
                    'valor' => max(0, $intercepto + ($pendiente * ($muestras + $horizonte))),
                    'horizonte' => $horizonte,
                ];
            }
            $resultado[$indicador] = ['muestras' => $muestras, 'proyecciones' => $proyecciones];
        }
        return $resultado;
    }
}
