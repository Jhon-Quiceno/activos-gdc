<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Equipos\Concerns\FiltrosDeEquipos;
use App\Models\Equipo;
use App\Models\Evento;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Primera vista de Traslados, con dos pestañas:
 *
 * - «Equipos»: elegir qué equipo trasladar o reasignar, con la misma búsqueda y
 *   los mismos filtros del listado de Equipos (trait FiltrosDeEquipos). Nunca
 *   muestra equipos dados de baja: no se trasladan (RN-10).
 * - «Traslados registrados»: historial de traslados con su estado de firma. Al
 *   elegir uno se abre su panel de documentos, para descargar los dos formatos
 *   (retiro y entrega) y subirlos firmados, aunque el traslado sea de otro día.
 */
class TrasladosListado extends Component
{
    use FiltrosDeEquipos;
    use WithPagination;

    /** 'equipos' | 'registrados' */
    #[Url]
    public string $vista = 'equipos';

    /** Búsqueda en los traslados registrados: serial, código o nombre de quien entrega/recibe. */
    #[Url(as: 'qt')]
    public string $busquedaTraslados = '';

    /** '' | pendiente_de_firma | completo */
    #[Url]
    public string $firma = '';

    /** Traslado elegido en la pestaña «Traslados registrados». */
    public ?int $trasladoSeleccionado = null;

    public function updated(string $propiedad): void
    {
        $this->updatedFiltrosDeEquipos($propiedad);

        if (in_array($propiedad, ['vista', 'busquedaTraslados', 'firma'], true)) {
            $this->resetPage();
            $this->trasladoSeleccionado = null;
        }
    }

    public function seleccionarTraslado(int $eventoId): void
    {
        $this->trasladoSeleccionado = $this->trasladoSeleccionado === $eventoId ? null : $eventoId;
    }

    public function render()
    {
        if ($this->vista === 'registrados') {
            return view('livewire.movimientos.traslados-listado', [
                'traslados' => $this->trasladosRegistrados(),
            ] + $this->opcionesDeFiltros());
        }

        $equipos = Equipo::query()
            ->with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'asignacionActual.sede', 'asignacionActual.dependencia'])
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->tap(fn (Builder $query) => $this->aplicarBusqueda($query))
            ->tap(fn (Builder $query) => $this->aplicarFiltros($query))
            ->latest('id')
            ->paginate(10);

        return view('livewire.movimientos.traslados-listado', ['equipos' => $equipos] + $this->opcionesDeFiltros());
    }

    private function trasladosRegistrados()
    {
        $termino = trim($this->busquedaTraslados);
        $normalizado = str_replace([' ', '-'], '', $termino);

        return Evento::query()
            ->where('tipo', 'traslado_responsable')
            ->with(['usuario', 'equipo.tipoEquipo', 'anulaciones', 'asignacionesOrigen.persona', 'asignacionesOrigen.sede'])
            ->when(in_array($this->firma, ['pendiente_de_firma', 'completo'], true), fn ($q) => $q->where('estado_firma', $this->firma))
            ->when($termino !== '', function (Builder $query) use ($termino, $normalizado) {
                $query->where(function (Builder $q) use ($termino, $normalizado) {
                    // La descripción del traslado dice «Traslado de A a B. Motivo: …».
                    $q->where('descripcion', 'like', "%{$termino}%");

                    if ($normalizado !== '') {
                        $q->orWhereHas('equipo', fn ($e) => $e
                            ->whereRaw("REPLACE(REPLACE(serial, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"])
                            ->orWhereRaw("REPLACE(REPLACE(codigo_activo, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"]));
                    }
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(10);
    }
}
