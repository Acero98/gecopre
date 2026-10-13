<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositorios\RepositorioResultados;

final class ReporteController
{
    public function continuidadCsv(): void
    {
        $this->indicadorCsv('continuidad', 'Continuidad', 'h/día');
    }

    public function presionCsv(): void
    {
        $this->indicadorCsv('presion', 'Presión', 'm.c.a.');
    }

    private function indicadorCsv(string $indicador, string $nombre, string $unidad): void
    {
        $periodoId = filter_input(INPUT_GET, 'periodo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodoId === false || $periodoId === null) { http_response_code(400); echo 'Período inválido.'; return; }
        $detalle = (new RepositorioResultados())->detallePeriodo($periodoId);
        if ($detalle === []) { http_response_code(404); echo 'Resultado no disponible.'; return; }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $indicador . '_periodo_' . $periodoId . '.csv"');
        $salida = fopen('php://output', 'wb');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Ámbito', 'Nombre', $nombre . ' (' . $unidad . ')', 'Conexiones utilizadas', 'Cobertura obtenida', 'Cobertura esperada', 'Estado', 'Versión'], ';');
        foreach ($detalle as $fila) {
            if ($fila['indicador'] !== $indicador) { continue; }
            fputcsv($salida, [$fila['tipo_ambito'], $fila['ambito_nombre'], $fila['valor'], $fila['conexiones_utilizadas'], $fila['cobertura_obtenida'], $fila['cobertura_esperada'], $fila['estado_resultado'], $fila['numero_version']], ';');
        }
        fclose($salida);
    }
}
