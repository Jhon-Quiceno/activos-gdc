<?php

namespace App\Livewire\Qr;

use App\Livewire\Qr\Soporte\CodigoQr;
use App\Models\EtiquetaQr;
use App\Models\Equipo;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Hoja de etiquetas QR para imprimir (RF-48 individual; RF-49 por lotes).
 *
 * Recibe los equipos en la URL (?equipos=1,2,3). Cada etiqueta lleva el QR, el
 * serial, el código de activo, el tipo y «Gobernación de Córdoba · Dirección
 * TIC» (RF-48), en 6 × 3,5 cm con el QR de 2,6 cm (RNF-17: mínimo 5 × 3 cm y
 * QR de 2 × 2 cm).
 *
 * Al imprimir se registra cada etiqueta en `etiquetas_qr` con quién y cuándo,
 * como «primera_impresion» o «reposicion» si el equipo ya tenía una (RF-51), y
 * con un código de lote cuando se imprimen varias a la vez.
 */
class Etiquetas extends Component
{
    /** Máximo de etiquetas por hoja de impresión. */
    public const MAXIMO = 120;

    /** Ids de equipos separados por coma. */
    #[Url]
    public string $equipos = '';

    public ?string $mensaje = null;

    /**
     * @return array<int, int>
     */
    private function ids(): array
    {
        return collect(explode(',', $this->equipos))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(self::MAXIMO)
            ->values()
            ->all();
    }

    public function imprimir(): void
    {
        $equipos = Equipo::whereIn('id', $this->ids())->get(['id']);

        if ($equipos->isEmpty()) {
            return;
        }

        $yaImpresas = EtiquetaQr::whereIn('equipo_id', $equipos->pluck('id'))->pluck('equipo_id')->flip();
        $lote = $equipos->count() > 1 ? 'L-'.now()->format('YmdHis').'-'.auth()->id() : null;

        foreach ($equipos as $equipo) {
            EtiquetaQr::create([
                'equipo_id' => $equipo->id,
                'fecha_impresion' => now(),
                'usuario_id' => auth()->id(),
                'motivo' => isset($yaImpresas[$equipo->id]) ? 'reposicion' : 'primera_impresion',
                'lote' => $lote,
            ]);
        }

        $this->mensaje = trans_choice(
            'Se registró :count etiqueta impresa.|Se registraron :count etiquetas impresas.',
            $equipos->count(),
            ['count' => $equipos->count()],
        ).($lote ? ' '.__('Lote :lote.', ['lote' => $lote]) : '');

        $this->js('window.print()');
    }

    public function render()
    {
        $ids = $this->ids();
        $equipos = Equipo::with('tipoEquipo')->whereIn('id', $ids)->get()
            ->sortBy(fn (Equipo $equipo) => array_search($equipo->id, $ids, true))
            ->values();

        return view('livewire.qr.etiquetas-hoja', [
            'etiquetas' => $equipos->map(fn (Equipo $equipo) => [
                'equipo' => $equipo,
                'qr' => CodigoQr::svg($equipo, 200),
            ]),
        ]);
    }
}
