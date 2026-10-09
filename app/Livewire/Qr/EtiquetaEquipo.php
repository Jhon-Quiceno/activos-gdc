<?php

namespace App\Livewire\Qr;

use App\Livewire\Qr\Soporte\CodigoQr;
use App\Models\EtiquetaQr;
use App\Models\Equipo;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Tarjeta «Etiqueta QR» de la hoja de vida (RF-08, RF-48): muestra el QR del
 * equipo, su enlace corto, cuántas veces se ha impreso la etiqueta y el botón
 * para imprimirla. Se inserta en livewire/equipos/hoja-de-vida.blade.php.
 */
class EtiquetaEquipo extends Component
{
    #[Locked]
    public Equipo $equipo;

    public function render()
    {
        $impresiones = EtiquetaQr::query()
            ->where('equipo_id', $this->equipo->id)
            ->with('usuario')
            ->latest('fecha_impresion')
            ->get();

        return view('livewire.qr.etiqueta-equipo', [
            'qr' => CodigoQr::svg($this->equipo, 132),
            'enlace' => CodigoQr::enlace($this->equipo),
            'impresiones' => $impresiones,
        ]);
    }
}
