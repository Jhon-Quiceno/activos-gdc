<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Concerns\FormularioPersona;
use App\Livewire\Movimientos\Soporte\FormatosPdf;
use App\Livewire\Movimientos\Soporte\GestorAsignaciones;
use App\Livewire\Movimientos\Soporte\PazYSalvo;
use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Persona;
use Illuminate\Support\Arr;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Responsables (ruta /movimientos): RF-18, RF-21, RF-22 y RF-23.
 *
 * - Registrar y editar personas responsables con los campos v4 (documento,
 *   nombres y apellidos, cargo del Decreto 607, vinculación, provisión…).
 * - Consultar los equipos a cargo de una persona, descargar su formato de
 *   entrega consolidado y su paz y salvo.
 * - Avisar de quienes terminan su vinculación pronto y todavía tienen equipos.
 *
 * La asignación de un equipo a una persona se hace siempre con un traslado,
 * para que quede el evento en la hoja de vida y se generen los formatos.
 */
class Index extends Component
{
    use FormularioPersona;
    use WithPagination;

    #[Url(as: 'q')]
    public string $busqueda = '';

    #[Url(as: 'persona')]
    public ?int $personaSeleccionada = null;

    public bool $mostrarFormulario = false;

    /** Persona que se está editando; null = persona nueva. */
    public ?int $editandoId = null;

    public ?int $dependenciaId = null;

    public ?string $mensaje = null;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    /**
     * Abre la ventana emergente con la información de la persona, sin salir de
     * la página ni perder la posición en el listado.
     */
    public function seleccionar(int $personaId): void
    {
        $this->personaSeleccionada = $personaId;
        $this->dispatch('open-modal', 'persona-detalle');
    }

    public function cerrarPersona(): void
    {
        $this->personaSeleccionada = null;
        $this->dispatch('close-modal', 'persona-detalle');
    }

    public function nuevaPersona(): void
    {
        $this->reset(['editandoId', 'dependenciaId', ...$this->camposPersona()]);
        $this->resetValidation();
        $this->mostrarFormulario = true;
    }

    public function editarPersona(int $personaId): void
    {
        $persona = Persona::findOrFail($personaId);

        $this->resetValidation();
        $this->llenarPersona($persona);
        $this->editandoId = $persona->id;
        $this->dependenciaId = $persona->dependencia_id;
        $this->mostrarFormulario = true;
        $this->cerrarPersona();
    }

    public function cancelarFormulario(): void
    {
        $this->reset(['mostrarFormulario', 'editandoId', 'dependenciaId', ...$this->camposPersona()]);
        $this->resetValidation();
    }

    protected function rules(): array
    {
        return $this->reglasPersona($this->editandoId) + [
            'dependenciaId' => ['required', 'exists:dependencias,id'],
        ];
    }

    protected function messages(): array
    {
        return $this->mensajesPersona();
    }

    protected function validationAttributes(): array
    {
        return ['dependenciaId' => __('dependencia')] + $this->atributosPersona();
    }

    public function guardarPersona(): void
    {
        $this->updatedNumeroDocumento();
        $this->validate();

        $datos = $this->datosPersona($this->dependenciaId);

        if ($this->editandoId) {
            $persona = Persona::findOrFail($this->editandoId);
            // Editar los datos no reactiva a una persona retirada.
            $persona->update(Arr::except($datos, 'activo'));
            $this->mensaje = __('Datos de :nombre actualizados.', ['nombre' => $persona->nombreCompleto()]);
        } else {
            $persona = Persona::create($datos);
            $this->mensaje = __(':nombre quedó registrado. Para asignarle un equipo, regístralo con un traslado.', ['nombre' => $persona->nombreCompleto()]);
        }

        $this->cancelarFormulario();
        $this->personaSeleccionada = $persona->id;
    }

    /**
     * RF-22: formatos de entrega de todos los equipos a cargo de la persona. En
     * la Dirección TIC no existe un «formato consolidado»: sale el formato
     * oficial en contexto «entrega», una hoja por equipo, en un solo PDF.
     */
    public function descargarConsolidado(int $personaId, FormatosPdf $formatos)
    {
        $persona = Persona::findOrFail($personaId);
        $pdf = $formatos->pdfConsolidado($persona);
        $nombre = 'formatos-entrega-'.str($persona->nombreCompleto())->slug().'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre, ['Content-Type' => 'application/pdf']);
    }

    /**
     * RF-23: paz y salvo de equipos de la persona (o la lista de lo que tiene
     * pendiente por entregar).
     */
    public function descargarPazYSalvo(int $personaId, PazYSalvo $pazYSalvo)
    {
        $persona = Persona::findOrFail($personaId);
        $pdf = $pazYSalvo->pdf($persona);
        $nombre = 'paz-y-salvo-'.str($persona->nombreCompleto())->slug().'.pdf';

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre, ['Content-Type' => 'application/pdf']);
    }

    public function render(GestorAsignaciones $asignaciones, PazYSalvo $pazYSalvo)
    {
        $termino = trim($this->busqueda);
        $documento = preg_replace('/[\s.,\-]/', '', $termino);

        $personas = Persona::query()
            ->with(['dependencia', 'cargoOficial'])
            ->withCount(['asignaciones as equipos_a_cargo' => fn ($q) => $q
                ->whereNull('fecha_fin')
                ->whereHas('equipo', fn ($e) => $e->where('estado_ciclo_vida', '!=', 'dado_de_baja'))])
            ->when($termino !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw("CONCAT_WS(' ', nombres, apellidos) LIKE ?", ["%{$termino}%"])
                ->orWhereHas('cargoOficial', fn ($c) => $c->where('denominacion', 'like', "%{$termino}%")->orWhere('codigo', $termino))
                ->when($documento !== '', fn ($q) => $q->orWhere('numero_documento', 'like', "%{$documento}%"))
                ->orWhereHas('dependencia', fn ($d) => $d->where('nombre', 'like', "%{$termino}%"))))
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->paginate(12);

        $seleccionada = $this->personaSeleccionada ? Persona::with(['dependencia', 'cargoOficial', 'jefeInmediato'])->find($this->personaSeleccionada) : null;

        return view('livewire.movimientos.index', [
            'personas' => $personas,
            'seleccionada' => $seleccionada,
            'equiposACargo' => $seleccionada ? $asignaciones->equiposACargo($seleccionada) : collect(),
            // Equipos que tuvo a cargo y ya no (asignaciones cerradas), lo más reciente primero.
            'equiposAnteriores' => $seleccionada
                ? Asignacion::query()
                    ->where('persona_id', $seleccionada->id)
                    ->whereNotNull('fecha_fin')
                    ->with('equipo.tipoEquipo')
                    ->orderByDesc('fecha_fin')
                    ->limit(20)
                    ->get()
                : collect(),
            'sinAsignar' => Equipo::where('estado_ciclo_vida', 'sin_asignar')->count(),
            'porTerminar' => $pazYSalvo->porTerminarConEquipos(),
            'dependencias' => $this->mostrarFormulario ? Dependencia::arbol() : collect(),
        ] + $this->opcionesPersona());
    }
}
