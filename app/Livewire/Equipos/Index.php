<?php

namespace App\Livewire\Equipos;

use App\Livewire\Equipos\Concerns\FiltrosDeEquipos;
use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de equipos con búsqueda (RF-07) y filtros combinables.
 */
class Index extends Component
{
    use FiltrosDeEquipos;
    use WithPagination;

    public function render()
    {
        $equipos = Equipo::query()
            ->with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'asignacionActual.sede', 'asignacionActual.dependencia'])
            ->tap(fn (Builder $query) => $this->aplicarBusqueda($query))
            ->tap(fn (Builder $query) => $this->aplicarFiltros($query))
            ->latest('id')
            ->paginate(10);

        return view('livewire.equipos.index', ['equipos' => $equipos] + $this->opcionesDeFiltros());
    }
}
