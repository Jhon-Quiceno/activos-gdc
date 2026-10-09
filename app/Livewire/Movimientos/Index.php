<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Livewire\Movimientos\Soporte\GestorAsignaciones;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Persona;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Responsables (ruta /movimientos): RF-18, RF-21 y RF-22.
 *
 * - Registrar personas responsables (nombre, cédula, cargo, dependencia y
 *   vinculación).
 * - Consultar los equipos a cargo de una persona y descargar su formato de
 *   entrega consolidado.
 * - Ver cuántos equipos están sin responsable (bodega) e ir a su listado.
 *
 * La asignación de un equipo a una persona se hace siempre con un traslado,
 * para que quede el evento en la hoja de vida y se generen los formatos.
 */
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $busqueda = '';

    #[Url(as: 'persona')]
    public ?int $personaSeleccionada = null;

    public bool $mostrarFormulario = false;

    public string $nombre = '';

    public string $cedula = '';

    public string $cargo = '';

    public ?int $dependenciaId = null;

    public ?string $tipoVinculacion = null;

    public ?string $mensaje = null;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function seleccionar(int $personaId): void
    {
        $this->personaSeleccionada = $this->personaSeleccionada === $personaId ? null : $personaId;
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'cedula' => ['required', 'digits_between:5,12', Rule::unique('personas', 'cedula')],
            'cargo' => ['required', 'string', 'max:255'],
            'dependenciaId' => ['required', 'exists:dependencias,id'],
            'tipoVinculacion' => ['required', 'in:planta,contratista'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'dependenciaId' => __('dependencia'),
            'tipoVinculacion' => __('tipo de vinculación'),
        ];
    }

    public function crearPersona(): void
    {
        $this->validate();

        $persona = Persona::create([
            'nombre' => trim($this->nombre),
            'cedula' => $this->cedula,
            'cargo' => trim($this->cargo),
            'dependencia_id' => $this->dependenciaId,
            'tipo_vinculacion' => $this->tipoVinculacion,
            'activo' => true,
        ]);

        $this->reset(['nombre', 'cedula', 'cargo', 'dependenciaId', 'tipoVinculacion', 'mostrarFormulario']);
        $this->personaSeleccionada = $persona->id;
        $this->mensaje = __(':nombre quedó registrado. Para asignarle un equipo, regístralo con un traslado.', ['nombre' => $persona->nombre]);
    }

    /**
     * RF-22: formato de entrega consolidado con todos los equipos a cargo.
     */
    public function descargarConsolidado(int $personaId, FormatosPdf $formatos)
    {
        $persona = Persona::findOrFail($personaId);
        $pdf = $formatos->pdfConsolidado($persona);
        $nombre = 'formato-entrega-consolidado-'.str($persona->nombre)->slug().'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre, ['Content-Type' => 'application/pdf']);
    }

    public function render(GestorAsignaciones $asignaciones)
    {
        $termino = trim($this->busqueda);
        $digitos = preg_replace('/\D/', '', $termino);

        $personas = Persona::query()
            ->with('dependencia')
            ->withCount(['asignaciones as equipos_a_cargo' => fn ($q) => $q
                ->whereNull('fecha_fin')
                ->whereHas('equipo', fn ($e) => $e->where('estado_ciclo_vida', '!=', 'dado_de_baja'))])
            ->when($termino !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$termino}%")
                ->orWhere('cargo', 'like', "%{$termino}%")
                ->when($digitos !== '', fn ($q) => $q->orWhere('cedula', 'like', "%{$digitos}%"))
                ->orWhereHas('dependencia', fn ($d) => $d->where('nombre', 'like', "%{$termino}%"))))
            ->orderBy('nombre')
            ->paginate(12);

        $seleccionada = $this->personaSeleccionada ? Persona::with('dependencia')->find($this->personaSeleccionada) : null;

        return view('livewire.movimientos.index', [
            'personas' => $personas,
            'seleccionada' => $seleccionada,
            'equiposACargo' => $seleccionada ? $asignaciones->equiposACargo($seleccionada) : collect(),
            'sinAsignar' => Equipo::where('estado_ciclo_vida', 'sin_asignar')->count(),
            'dependencias' => Dependencia::orderBy('nombre')->get(),
        ]);
    }
}
