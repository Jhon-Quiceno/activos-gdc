<?php

namespace App\Livewire\Qr;

use App\Livewire\Equipos\Concerns\FiltrosDeEquipos;
use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Etiquetas QR (ruta /qr): elegir equipos e imprimir sus etiquetas por lotes
 * (RF-49), por ejemplo todos los PC de una sede, con los mismos filtros del
 * listado de Equipos. Cada equipo ya tiene su QR desde que se registra (RF-08).
 */
class Index extends Component
{
    use FiltrosDeEquipos;
    use WithPagination;

    /** '' | sin | con: si la etiqueta ya se imprimió alguna vez. */
    #[Url]
    public string $etiqueta = '';

    /** @var array<int, string> Ids elegidos (como texto, por los checkbox). */
    public array $seleccionados = [];

    public function updated(string $propiedad): void
    {
        $this->updatedFiltrosDeEquipos($propiedad);

        if ($propiedad === 'etiqueta') {
            $this->resetPage();
        }
    }

    private function consulta(): Builder
    {
        return Equipo::query()
            ->tap(fn (Builder $query) => $this->aplicarBusqueda($query))
            ->tap(fn (Builder $query) => $this->aplicarFiltros($query))
            ->when($this->etiqueta === 'sin', fn ($q) => $q->whereDoesntHave('etiquetasQr'))
            ->when($this->etiqueta === 'con', fn ($q) => $q->whereHas('etiquetasQr'));
    }

    /**
     * Marca todos los equipos que cumplen los filtros (hasta el máximo de una hoja).
     */
    public function seleccionarFiltrados(): void
    {
        $this->seleccionados = $this->consulta()
            ->latest('id')
            ->limit(Etiquetas::MAXIMO)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
    }

    public function render()
    {
        $equipos = $this->consulta()
            ->with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'asignacionActual.sede'])
            ->withCount('etiquetasQr')
            ->latest('id')
            ->paginate(15);

        return view('livewire.qr.index', [
            'equipos' => $equipos,
            'sinEtiqueta' => Equipo::whereDoesntHave('etiquetasQr')->count(),
            'urlImprimir' => route('qr.etiquetas', ['equipos' => implode(',', array_slice($this->seleccionados, 0, Etiquetas::MAXIMO))]),
        ] + $this->opcionesDeFiltros());
    }
}
