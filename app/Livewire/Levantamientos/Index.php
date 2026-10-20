<?php

namespace App\Livewire\Levantamientos;

use App\Livewire\Levantamientos\Soporte\GestorLevantamientos;
use App\Models\Dependencia;
use App\Models\LevantamientoActivos;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\Sede;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Levantamientos de activos (ruta /levantamientos, RF-59, CU-18): lista y
 * creación de levantamientos por dependencia, proceso y sede. El trabajo de
 * cada uno (verificar, valorar, acta) está en Levantamientos\Detalle.
 */
class Index extends Component
{
    use WithPagination;

    public bool $mostrarFormulario = false;

    public ?int $dependenciaId = null;

    /** Null = el proceso de la dependencia. */
    public ?int $procesoId = null;

    public ?int $sedeId = null;

    public ?int $propietarioId = null;

    public ?int $custodioId = null;

    protected function rules(): array
    {
        return [
            'dependenciaId' => ['required', 'exists:dependencias,id'],
            'procesoId' => ['nullable', 'exists:procesos,id'],
            'sedeId' => ['required', 'exists:sedes,id'],
            'propietarioId' => ['nullable', 'exists:personas,id'],
            'custodioId' => ['nullable', 'exists:personas,id', 'different:propietarioId'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'dependenciaId' => __('dependencia'),
            'procesoId' => __('proceso'),
            'sedeId' => __('sede'),
            'propietarioId' => __('propietario'),
            'custodioId' => __('custodio'),
        ];
    }

    /** Al cambiar la dependencia, el proceso por defecto es el suyo. */
    public function updatedDependenciaId(): void
    {
        $this->procesoId = $this->dependenciaId ? Dependencia::query()->whereKey($this->dependenciaId)->value('proceso_id') : null;
        $this->reset(['propietarioId', 'custodioId']);
    }

    public function crear(GestorLevantamientos $gestor): void
    {
        $this->validate();

        $levantamiento = $gestor->crear([
            'dependencia_id' => $this->dependenciaId,
            'proceso_id' => $this->procesoId,
            'sede_id' => $this->sedeId,
            'propietario_persona_id' => $this->propietarioId,
            'custodio_persona_id' => $this->custodioId,
        ], auth()->user());

        $this->redirect(route('levantamientos.show', $levantamiento), navigate: true);
    }

    public function render()
    {
        // Firmantes posibles: las personas activas de la dependencia y sus subdependencias.
        $personas = $this->dependenciaId
            ? Persona::with('cargoOficial')->where('activo', true)->whereIn('dependencia_id', Dependencia::idsDeRama($this->dependenciaId))->orderBy('nombres')->get()
            : collect();

        return view('livewire.levantamientos.index', [
            'levantamientos' => LevantamientoActivos::query()
                ->with(['dependencia', 'sede', 'proceso'])
                ->withCount('valoraciones')
                ->latest('id')
                ->paginate(20),
            'dependencias' => $this->mostrarFormulario ? Dependencia::arbol() : collect(),
            'procesos' => $this->mostrarFormulario ? Proceso::where('activo', true)->orderBy('nombre')->get() : collect(),
            'sedes' => $this->mostrarFormulario ? Sede::where('activa', true)->orderBy('nombre')->get() : collect(),
            'personas' => $personas,
        ]);
    }
}
