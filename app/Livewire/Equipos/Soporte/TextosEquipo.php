<?php

namespace App\Livewire\Equipos\Soporte;

/**
 * Textos y formatos de datos del equipo que comparten las pantallas, el PDF y
 * el historial. Van en una clase y no en los traits porque PHP no deja leer
 * constantes ni llamar métodos estáticos de un trait directamente.
 */
final class TextosEquipo
{
    /** Mismas opciones que el enum `equipos.figura_tercero` (RF-04). */
    public const FIGURAS_TERCERO = [
        'comodato' => 'Comodato',
        'convenio' => 'Convenio',
        'proveedor' => 'Proveedor',
        'arrendamiento' => 'Arrendamiento',
    ];

    /** «$ 2.350.000» para pantallas, PDF e historial (RNF-14). */
    public static function pesos(string|float|null $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $decimales = fmod((float) $valor, 1.0) === 0.0 ? 0 : 2;

        return '$ '.number_format((float) $valor, $decimales, ',', '.');
    }

    /** «2350000.00» → «2350000»; «2350000.50» → «2350000,50». */
    public static function valorParaFormulario(string|float $valor): string
    {
        $texto = number_format((float) $valor, 2, ',', '');

        return str_ends_with($texto, ',00') ? substr($texto, 0, -3) : $texto;
    }
}
