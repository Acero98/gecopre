<?php
declare(strict_types=1);

namespace App\Servicios;

final class ServicioRegistroMensual
{
    public static function normalizarValor(string $indicador, string $valor): string
    {
        $valor = trim($valor);
        if ($indicador === 'continuidad') {
            if (!preg_match('/^(?:[01]\\d|2[0-4]):[0-5]\\d$/', $valor) || str_starts_with($valor, '24:') && $valor !== '24:00') {
                throw new \DomainException('La continuidad debe tener el formato HH:MM, desde 00:00 hasta 24:00.');
            }
            [$horas, $minutos] = array_map('intval', explode(':', $valor));
            return number_format($horas + ($minutos / 60), 4, '.', '');
        }
        if ($indicador !== 'presion') {
            throw new \DomainException('El indicador seleccionado no es válido.');
        }
        $normalizado = str_replace(',', '.', $valor);
        if ($normalizado === '' || !is_numeric($normalizado) || (float) $normalizado < 0) {
            throw new \DomainException('La presión debe ser un número igual o mayor que cero.');
        }
        return number_format((float) $normalizado, 4, '.', '');
    }
}
