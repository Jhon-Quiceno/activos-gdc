<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\PendientesDeFirma;
use App\Livewire\Reportes\Exportes\ReporteExport;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Pantalla de pendientes de firma (RF-31; RF-30, RN-05).
 *
 * Lista los eventos que siguen esperando sus documentos firmados (la regla
 * está en Soporte\PendientesDeFirma), filtrables por tipo de evento, por
 * antigüedad y por serial o código de activo. Cada fila dice qué falta y
 * quién firma, y lleva directo a subir el documento: el enlace
 * `/movimientos/pendientes?evento=<id>` abre el panel de firmas de ese evento,
 * así que también sirve para compartirlo o para llegar desde otra pantalla.
 */
class PendientesListado extends Component
{
    use WithPagination;

    #[Url(as: 'tipo')]
    public string $tipo = '';

    #[Url(as: 'antiguedad')]
    public string $antiguedad = '';

    #[Url(as: 'q')]
    public string $busqueda = '';

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

    public function limpiarFiltros(): void
    {
        $this->reset(['tipo', 'antiguedad', 'busqueda']);
        $this->resetPage();
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

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    /** Exporta a Excel el listado tal como está filtrado. */
    public function exportarExcel(PendientesDeFirma $pendientes)
    {
        return Excel::download(
            new ReporteExport($pendientes->filas($this->filtros()), PendientesDeFirma::encabezados()),
            'pendientes-de-firma-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** @return array{tipo: string, antiguedad: string, busqueda: string} */
    private function filtros(): array
    {
        return ['tipo' => $this->tipo, 'antiguedad' => $this->antiguedad, 'busqueda' => $this->busqueda];
    }

    public function render(PendientesDeFirma $pendientes)
    {
        $eventos = $pendientes->consulta($this->filtros())
            ->with([
                'equipo.tipoEquipo',
                'equipo.asignacionActual.persona',
                'usuario',
                'documentos' => fn ($q) => $q->where('firmado', true),
            ])
            ->orderBy('fecha')
            ->orderBy('id')
            ->paginate(15);

        return view('livewire.movimientos.pendientes-listado', [
            'eventos' => $eventos,
            'pendientes' => $pendientes,
            'resumen' => $pendientes->resumen(),
            'tipos' => $pendientes->tiposConPendientes(),
            'antiguedades' => PendientesDeFirma::ANTIGUEDADES,
            'hayFiltros' => $this->tipo !== '' || $this->antiguedad !== '' || trim($this->busqueda) !== '',
        ]);
    }
}
