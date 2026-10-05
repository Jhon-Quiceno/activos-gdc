<?php

namespace App\Livewire\Movimientos;

use App\Models\Equipo;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de equipos para elegir cuál dar de baja. Misma búsqueda reactiva
 * que TrasladosListado, excluyendo los que ya están dados de baja.
 */
class BajasListado extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $busqueda = '';

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $termino = trim($this->busqueda);
        $normalizado = str_replace([' ', '-'], '', $termino);

        $equipos = Equipo::query()
            ->with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'asignacionActual.sede', 'asignacionActual.dependencia'])
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->when($termino !== '', function ($query) use ($termino, $normalizado) {
                $query->where(function ($q) use ($termino, $normalizado) {
                    $q->whereRaw("REPLACE(REPLACE(serial, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"])
                        ->orWhereRaw("REPLACE(REPLACE(codigo_activo, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"])
                        ->orWhereHas('asignacionActual.persona', function ($p) use ($termino, $normalizado) {
                            $p->where('nombre', 'like', "%{$termino}%")
                                ->orWhereRaw("REPLACE(cedula, ' ', '') LIKE ?", ["%{$normalizado}%"]);
                        })
                        ->orWhereHas('asignacionActual.dependencia', fn ($d) => $d->where('nombre', 'like', "%{$termino}%"))
                        ->orWhereHas('asignacionActual.sede', fn ($s) => $s->where('nombre', 'like', "%{$termino}%"));
                });
            })
            ->latest('id')
            ->paginate(10);

        return view('livewire.movimientos.bajas-listado', ['equipos' => $equipos]);
    }
}
