<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\GestorFirmas;
use App\Models\Evento;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado reactivo de eventos pendientes de firma (RF-30 / RN-05).
 *
 * Usa exactamente la misma condición que la campanita del topbar
 * (resources/views/components/layouts/app-shell.blade.php):
 * Evento::where('estado_firma', 'pendiente_de_firma'), para que el contador
 * del topbar y el total mostrado aquí siempre coincidan.
 */
class PendientesListado extends Component
{
    use WithPagination;

    #[Url(as: 'tipo')]
    public string $tipo = '';

    #[Url(as: 'antiguedad')]
    public string $antiguedad = '';

    /** Evento cuyo panel de firmas está abierto (CU-09). */
    #[Url(as: 'evento')]
    public ?int $eventoSeleccionado = null;

    public function gestionar(int $eventoId): void
    {
        $this->eventoSeleccionado = $this->eventoSeleccionado === $eventoId ? null : $eventoId;
    }

    public function cerrarPanel(): void
    {
        $this->eventoSeleccionado = null;
    }

    #[On('documentos-actualizados')]
    public function documentosActualizados(): void
    {
        // Solo fuerza el re-render: si el evento se completó, sale del listado.
    }

    public function updatingTipo(): void
    {
        $this->resetPage();
    }

    public function updatingAntiguedad(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $eventos = Evento::query()
            ->with(['equipo.asignacionActual.persona', 'usuario', 'documentos' => fn ($q) => $q->where('firmado', true)])
            ->where('estado_firma', 'pendiente_de_firma')
            ->when($this->tipo !== '', fn ($query) => $query->where('tipo', $this->tipo))
            ->when($this->antiguedad === 'mas_30', fn ($query) => $query->where('fecha', '<=', now()->subDays(30)))
            ->orderBy('fecha')
            ->paginate(15);

        return view('livewire.movimientos.pendientes-listado', [
            'eventos' => $eventos,
            'firmas' => app(GestorFirmas::class),
        ]);
    }
}
