<?php

namespace App\Livewire\Movimientos;

use App\Models\Asignacion;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use App\Services\HistorialService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Pantalla "Traslado o cambio de responsable". Cierra la asignación abierta
 * del equipo (fecha_fin = hoy) y abre una nueva: con un nuevo responsable, o
 * sin responsable si el equipo se envía a bodega.
 */
class TrasladoForm extends Component
{
    public Equipo $equipo;

    public bool $bodega = false;

    public string $nuevoResponsable = '';

    public ?int $sedeId = null;

    public ?int $pisoId = null;

    public ?int $dependenciaId = null;

    public string $fecha = '';

    public string $motivo = '';

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load([
            'tipoEquipo',
            'marca',
            'asignacionActual.persona',
            'asignacionActual.sede',
            'asignacionActual.piso',
            'asignacionActual.dependencia',
        ]);

        // Prellenamos sede/piso con la ubicación actual: si el usuario marca
        // "bodega" estos campos quedan ocultos pero igual viajan a la BD,
        // porque asignaciones.sede_id/piso_id no son nulables (el equipo
        // sigue físicamente en algún lado aunque no tenga responsable).
        $this->sedeId = $this->equipo->asignacionActual?->sede_id;
        $this->pisoId = $this->equipo->asignacionActual?->piso_id;
        $this->fecha = now()->toDateString();
    }

    protected function rules(): array
    {
        if ($this->bodega) {
            return [
                'sedeId' => ['required', 'exists:sedes,id'],
                'pisoId' => ['required', 'exists:pisos,id'],
            ];
        }

        return [
            'nuevoResponsable' => ['required', 'string', 'max:255'],
            'sedeId' => ['required', 'exists:sedes,id'],
            'pisoId' => ['required', 'exists:pisos,id'],
            'dependenciaId' => ['required', 'exists:dependencias,id'],
            'fecha' => ['required', 'date'],
            'motivo' => ['required', 'string'],
        ];
    }

    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {
            $asignacionActual = $this->equipo->asignacionActual;

            if ($asignacionActual) {
                $asignacionActual->fecha_fin = now();
                $asignacionActual->save();
            }

            if ($this->bodega) {
                Asignacion::create([
                    'equipo_id' => $this->equipo->id,
                    'persona_id' => null,
                    'sede_id' => $this->sedeId,
                    'piso_id' => $this->pisoId,
                    'dependencia_id' => null,
                    'fecha_inicio' => now(),
                ]);

                $this->equipo->estado_ciclo_vida = 'sin_asignar';
                $this->equipo->save();

                $descripcion = __('Traslado a bodega (sin responsable asignado).');
            } else {
                $persona = Persona::create([
                    'nombre' => $this->nuevoResponsable,
                    'dependencia_id' => $this->dependenciaId,
                ]);

                Asignacion::create([
                    'equipo_id' => $this->equipo->id,
                    'persona_id' => $persona->id,
                    'sede_id' => $this->sedeId,
                    'piso_id' => $this->pisoId,
                    'dependencia_id' => $this->dependenciaId,
                    'fecha_inicio' => $this->fecha,
                ]);

                $this->equipo->estado_ciclo_vida = 'en_servicio';
                $this->equipo->save();

                $descripcion = __('Traslado a :responsable.', ['responsable' => $persona->nombre]);
            }

            app(HistorialService::class)->registrar(
                equipo: $this->equipo,
                tipo: 'traslado_responsable',
                usuario: auth()->user(),
                datos: ['estado_firma' => 'pendiente_de_firma'],
                descripcion: $descripcion,
            );
        });

        $this->redirect(route('equipos.show', $this->equipo), navigate: true);
    }

    public function render()
    {
        return view('livewire.movimientos.traslado-form', [
            'sedes' => Sede::orderBy('nombre')->get(),
            'pisos' => Piso::orderBy('numero')->get(),
            'dependencias' => Dependencia::orderBy('nombre')->get(),
        ]);
    }
}
