<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Persona;

/**
 * RN-12 / Ley 1581 de 2012: en listados el documento se muestra enmascarado
 * (****6226); completo solo en la ficha y en los formatos para firma.
 *
 * Si Manuel publica un helper compartido de enmascaramiento, este se reemplaza
 * por ese.
 */
class Cedula
{
    public static function enmascarar(?string $documento): string
    {
        if (blank($documento)) {
            return '—';
        }

        // Letras y dígitos: un pasaporte o una cédula de extranjería también llevan letras.
        $caracteres = preg_replace('/[^A-Za-z0-9]/', '', $documento);

        return '****'.substr($caracteres, -4);
    }

    /**
     * «CC ****6226» con el tipo de documento (RF-18).
     */
    public static function documento(?Persona $persona): string
    {
        if (! $persona || blank($persona->numero_documento)) {
            return '—';
        }

        return trim(($persona->tipo_documento ?? 'CC').' '.self::enmascarar($persona->numero_documento));
    }
}
