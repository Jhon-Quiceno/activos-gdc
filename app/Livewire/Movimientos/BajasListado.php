<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Equipos\Concerns\FiltrosDeEquipos;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\MotivoBaja;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Primera vista de Bajas, con dos pestañas (igual que TrasladosListado):
 *
 * - «Equipos»: elegir qué equipo dar de baja, con la búsqueda y los filtros del
 *   listado de Equipos (trait FiltrosDeEquipos). Nunca muestra los equipos ya
 *   dados de baja.
 * - «Bajas registradas»: historial de bajas con su estado; al elegir una se abre
 *   su panel para descargar el formato de baja y subirlo firmado (RF-25, RF-30).
 */
class BajasListado extends Component
{
    use FiltrosDeEquipos;
    use WithPagination;

    /** 'equipos' | 'registradas' */
    #[Url]
    public string $vista = 'equipos';

    /** Búsqueda en las bajas registradas: serial, código o texto del diagnóstico. */
    #[Url(as: 'qb')]
    public string $busquedaBajas = '';

    /** '' | pendiente_de_firma | completo | anulada */
    #[Url]
    public string $estadoBaja = '';

    #[Url]
    public string $motivo = '';

    /** Baja elegida en la pestaña «Bajas registradas». */
    public ?int $bajaSeleccionada = null;

    public function updated(string $propiedad): void
    {
        $this->updatedFiltrosDeEquipos($propiedad);

        if (in_array($propiedad, ['vista', 'busquedaBajas', 'estadoBaja', 'motivo'], true)) {
            $this->resetPage();
            $this->bajaSeleccionada = null;
        }
    }

    public function seleccionarBaja(int $eventoId): void
    {
        $this->bajaSeleccionada = $this->bajaSeleccionada === $eventoId ? null : $eventoId;
    }

    public function render()
    {
        if ($this->vista === 'registradas') {
            return view('livewire.movimientos.bajas-listado', [
                'bajas' => $this->bajasRegistradas(),
                'motivos' => MotivoBaja::orderBy('nombre')->get(['id', 'nombre']),
            ] + $this->opcionesDeFiltros());
        }

        $equipos = Equipo::query()
            ->with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'asignacionActual.sede', 'asignacionActual.dependencia'])
            ->where('estado_ciclo_vida', '!=', 'dado_de_baja')
            ->tap(fn (Builder $query) => $this->aplicarBusqueda($query))
            ->tap(fn (Builder $query) => $this->aplicarFiltros($query))
            ->latest('id')
            ->paginate(10);

        return view('livewire.movimientos.bajas-listado', ['equipos' => $equipos] + $this->opcionesDeFiltros());
    }

    private function bajasRegistradas()
    {
        $termino = trim($this->busquedaBajas);
        $normalizado = str_replace([' ', '-'], '', $termino);

        return Evento::query()
            ->where('tipo', 'baja')
            ->with(['usuario', 'equipo.tipoEquipo', 'anulaciones', 'diagnostico.motivoBaja'])
            ->when($this->estadoBaja === 'anulada', fn ($q) => $q->whereHas('anulaciones'))
            ->when(in_array($this->estadoBaja, ['pendiente_de_firma', 'completo'], true), fn ($q) => $q
                ->where('estado_firma', $this->estadoBaja)
                ->whereDoesntHave('anulaciones'))
            ->when($this->motivo !== '', fn ($q) => $q->whereHas('diagnostico', fn ($d) => $d->where('motivo_baja_id', $this->motivo)))
            ->when($termino !== '', function (Builder $query) use ($termino, $normalizado) {
                $query->where(function (Builder $q) use ($termino, $normalizado) {
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
