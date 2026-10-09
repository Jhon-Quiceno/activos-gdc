<?php

namespace App\Livewire\Movimientos;

use App\Models\Equipo;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de equipos para elegir cuál trasladar o reasignar (RF de Movimientos).
 *
 * Misma búsqueda reactiva que App\Livewire\Equipos\Index, pero excluye los
 * equipos ya dados de baja (no tiene sentido trasladar algo que ya salió de
 * servicio) y cada fila lleva a `movimientos.traslado` en vez de a la hoja
 * de vida.
 */
class TrasladosListado extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $busqueda = '';

    /** RF-21: '' = todos, 'en_servicio' o 'sin_asignar' (equipos en bodega). */
    #[Url(as: 'estado')]
    public string $estado = '';

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
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
            ->when(in_array($this->estado, ['en_servicio', 'sin_asignar'], true), fn ($q) => $q->where('estado_ciclo_vida', $this->estado))
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

        return view('livewire.movimientos.traslados-listado', ['equipos' => $equipos]);
    }
}
