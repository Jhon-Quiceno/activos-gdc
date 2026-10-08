<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Exportes\ReporteExport;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

abstract class ReporteBase extends Component
{
    use WithPagination;

    /** Filtros combinables (RF-34). Vacío = no filtra. */
    public array $filtros = [
        'sede_id'           => null,
        'piso_id'           => null,
        'dependencia_id'    => null,
        'tipo_equipo_id'    => null,
        'marca_id'          => null,
        'estado_ciclo_vida' => null,
        'propiedad'         => null,
        'persona_id'        => null,
        'tipo_vinculacion'  => null,
        'fecha_desde'       => null,
        'fecha_hasta'       => null,
    ];

    // ---------- Lo que define cada reporte hijo ----------

    abstract protected function titulo(): string;

    /** Consulta base del reporte, antes de aplicar los filtros. */
    abstract protected function consultaBase(): Builder;

    /** ['Encabezado' => fn ($equipo) => valor, ...] */
    abstract protected function columnas(): array;

    // ---------- Lógica compartida ----------

    public function updatedFiltros(): void
    {
        $this->resetPage(); // al cambiar un filtro, volver a la página 1
    }

    public function limpiarFiltros(): void
    {
        $this->reset('filtros');
        $this->resetPage();
    }

    protected function consultaFiltrada(): Builder
    {
        $f = $this->filtros;

        return $this->consultaBase()
            ->when($f['tipo_equipo_id'],    fn ($q, $v) => $q->where('tipo_equipo_id', $v))
            ->when($f['marca_id'],          fn ($q, $v) => $q->where('marca_id', $v))
            ->when($f['estado_ciclo_vida'], fn ($q, $v) => $q->where('estado_ciclo_vida', $v))
            ->when($f['propiedad'],         fn ($q, $v) => $q->where('propiedad', $v))
            ->when($f['fecha_desde'],       fn ($q, $v) => $q->whereDate('equipos.created_at', '>=', $v))
            ->when($f['fecha_hasta'],       fn ($q, $v) => $q->whereDate('equipos.created_at', '<=', $v))
            // Ubicación y responsable: siempre sobre la asignación actual (fecha_fin nulo)
            ->when($f['sede_id'],           fn ($q, $v) => $q->whereHas('asignacionActual', fn ($a) => $a->where('sede_id', $v)))
            ->when($f['piso_id'],           fn ($q, $v) => $q->whereHas('asignacionActual', fn ($a) => $a->where('piso_id', $v)))
            ->when($f['dependencia_id'],    fn ($q, $v) => $q->whereHas('asignacionActual', fn ($a) => $a->where('dependencia_id', $v)))
            ->when($f['persona_id'],        fn ($q, $v) => $q->whereHas('asignacionActual', fn ($a) => $a->where('persona_id', $v)))
            ->when($f['tipo_vinculacion'],  fn ($q, $v) => $q->whereHas('asignacionActual.persona', fn ($p) => $p->where('tipo_vinculacion', $v)))
            ->orderBy('equipos.id'); // orden estable para que la paginación no repita filas
    }

    /** Filas listas para exportar: una lista de valores por cada equipo. */
    protected function filas(): Collection
    {
        $columnas = $this->columnas();

        return $this->consultaFiltrada()->get()->map(
            fn ($modelo) => collect($columnas)->map(fn ($fn) => $fn($modelo))->values()->all()
        );
    }

    /** RN-12: la cédula nunca sale completa en listados ni exportaciones. */
    protected function enmascararCedula(?string $cedula): string
    {
        if (blank($cedula)) {
            return '';
        }

        return '****' . substr($cedula, -4);
    }

    // ---------- Exportaciones (RF-35) ----------

    public function exportarExcel()
    {
        return Excel::download(
            new ReporteExport($this->filas(), array_keys($this->columnas())),
            str($this->titulo())->slug() . '.xlsx'
        );
    }

    public function exportarPdf()
    {
        $pdf = Pdf::loadView('livewire.reportes.pdf.reporte', [
            'titulo'      => $this->titulo(),
            'encabezados' => array_keys($this->columnas()),
            'filas'       => $this->filas(),
        ])->setPaper('letter', 'landscape'); // RNF-13: tamaño carta

        return response()->streamDownload(
            fn () => print($pdf->output()),
            str($this->titulo())->slug() . '.pdf'
        );
    }

    // ---------- Pantalla ----------

    public function render()
    {
        return view('livewire.reportes.base', [
            'titulo'      => $this->titulo(),
            'encabezados' => array_keys($this->columnas()),
            'columnas'    => $this->columnas(),
            'registros'   => $this->consultaFiltrada()->paginate(25),
            'catalogos'   => $this->catalogos(),
        ]);
    }

    /** Opciones de los <select> de filtros. */
    protected function catalogos(): array
    {
        return [
            'sedes'         => Sede::orderBy('nombre')->get(['id', 'nombre']),
            'pisos'         => Piso::orderBy('nombre')->get(['id', 'nombre']),
            'dependencias'  => Dependencia::orderBy('nombre')->get(['id', 'nombre']),
            'tipos'         => TipoEquipo::orderBy('nombre')->get(['id', 'nombre']),
            'marcas'        => Marca::orderBy('nombre')->get(['id', 'nombre']),
            'personas'      => Persona::orderBy('nombre')->get(['id', 'nombre']),
            // Se leen de la base de datos para no adivinar los valores exactos
            'estados'       => Equipo::query()->distinct()->orderBy('estado_ciclo_vida')->pluck('estado_ciclo_vida'),
            'propiedades'   => Equipo::query()->distinct()->orderBy('propiedad')->pluck('propiedad'),
            'vinculaciones' => Persona::query()->distinct()->orderBy('tipo_vinculacion')->pluck('tipo_vinculacion'),
        ];
    }
}