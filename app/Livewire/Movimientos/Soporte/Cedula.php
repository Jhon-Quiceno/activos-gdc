<?php

namespace App\Livewire\Movimientos\Soporte;

/**
 * RN-12 / Ley 1581 de 2012: en listados la cédula se muestra enmascarada
 * (****6226); completa solo en la ficha y en los formatos para firma.
 *
 * Si Manuel publica un helper compartido de enmascaramiento, este se reemplaza
 * por ese.
 */
class Cedula
{
    public static function enmascarar(?string $cedula): string
    {
        if (blank($cedula)) {
            return '—';
        }

        $digitos = preg_replace('/\D/', '', $cedula);

        return '****'.substr($digitos, -4);
    }
}
