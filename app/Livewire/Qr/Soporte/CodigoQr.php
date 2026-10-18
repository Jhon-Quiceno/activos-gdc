<?php

namespace App\Livewire\Qr\Soporte;

use App\Models\Equipo;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Código QR de un equipo (RF-08).
 *
 * RN-17: el QR contiene únicamente el enlace corto a la hoja de vida con el
 * identificador del equipo, nunca datos del equipo ni personales.
 * RN-19: ese identificador es `qr_uuid`, que no cambia aunque se corrija el
 * serial, el código de activo, el responsable o la ubicación.
 * RNF-18: la ruta es corta y estable (/e/{uuid}) para que, si cambia el
 * dominio, baste con redirigir sin reimprimir etiquetas.
 */
class CodigoQr
{
    public static function enlace(Equipo $equipo): string
    {
        return route('qr.escanear', $equipo->qr_uuid);
    }

    /**
     * QR en SVG, listo para incrustar en una página o en una etiqueta.
     * Corrección de errores «M»: sigue leyéndose con la etiqueta algo rayada.
     */
    public static function svg(Equipo $equipo, int $tamano = 160): HtmlString
    {
        return new HtmlString((string) QrCode::format('svg')
            ->size($tamano)
            ->margin(1)
            ->errorCorrection('M')
            ->generate(self::enlace($equipo)));
    }
}
