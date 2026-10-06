<?php

namespace App\Livewire\Equipos;

use App\Models\Equipo;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /**
     * Término de búsqueda libre: serial, código de activo, responsable, cédula,
     * dependencia o sede (RF-07). Tolera espacios y guiones en serial/código/cédula
     * (I1 24147 = I1-24147).
     */
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

        return view('livewire.equipos.index', ['equipos' => $equipos]);
    }
}
