<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\Dependencia;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Piso;
use App\Models\Sede;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Traslado o cambio de responsable" (CU-05, RF-18 a RF-21).
 *
 * El nuevo responsable se elige de las personas ya registradas o se crea en el
 * momento con sus datos completos (nombre, cédula, cargo, dependencia y
 * vinculación). También se puede dejar el equipo sin asignar (bodega).
 *
 * Al guardar, la lógica de RegistroMovimientos::trasladar() cierra la
 * asignación anterior, abre la nueva, crea el evento «Pendiente de firma» y
 * genera los dos formatos; la pantalla muestra entonces el panel para
 * descargarlos y subir los firmados.
 */
class TrasladoForm extends Component
{
    public Equipo $equipo;

    public bool $bodega = false;

    /** 'existente' | 'nueva' */
    public string $modoResponsable = 'existente';

    public string $buscarPersona = '';

    public ?int $personaId = null;

    // Datos de la persona nueva (RF-18).
    public string $nombre = '';

    public string $cedula = '';

    public string $cargo = '';

    public ?string $tipoVinculacion = null;

    public ?int $sedeId = null;

    public ?int $pisoId = null;

    public ?int $dependenciaId = null;

    public string $fecha = '';

    public string $motivo = '';

    /** Evento creado al guardar; mientras sea null se muestra el formulario. */
    public ?int $eventoId = null;

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

        // Prellenamos la ubicación con la actual: asignaciones.sede_id/piso_id no
        // son nulables porque el equipo sigue físicamente en algún lado aunque
        // quede sin responsable.
        $this->sedeId = $this->equipo->asignacionActual?->sede_id;
        $this->pisoId = $this->equipo->asignacionActual?->piso_id;
        $this->dependenciaId = $this->equipo->asignacionActual?->dependencia_id;
        $this->fecha = now()->toDateString();
    }

    public function updatedPersonaId($valor): void
    {
        // Al elegir una persona, su dependencia es la sugerencia natural.
        $persona = $valor ? Persona::find($valor) : null;
        if ($persona?->dependencia_id) {
            $this->dependenciaId = $persona->dependencia_id;
        }
    }

    public function elegirPersona(int $id): void
    {
        $this->personaId = $id;
        $this->buscarPersona = '';
        $this->updatedPersonaId($id);
    }

    protected function rules(): array
    {
        $rules = [
            'sedeId' => ['required', 'exists:sedes,id'],
            'pisoId' => ['required', 'exists:pisos,id'],
            // RN-13: la fecha del evento no puede ser futura.
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'motivo' => ['required', 'string', 'max:1000'],
        ];

        if ($this->bodega) {
            return $rules + ['dependenciaId' => ['nullable', 'exists:dependencias,id']];
        }

        $rules['dependenciaId'] = ['required', 'exists:dependencias,id'];

        if ($this->modoResponsable === 'nueva') {
            return $rules + [
                'nombre' => ['required', 'string', 'max:255'],
                // Solo dígitos (sección 8.2) y sin duplicar personas.
                'cedula' => ['required', 'digits_between:5,12', Rule::unique('personas', 'cedula')],
                'cargo' => ['required', 'string', 'max:255'],
                'tipoVinculacion' => ['required', 'in:planta,contratista'],
            ];
        }

        return $rules + [
            'personaId' => ['required', Rule::exists('personas', 'id')->where('activo', true)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'sedeId' => __('sede'),
            'pisoId' => __('piso'),
            'dependenciaId' => __('dependencia'),
            'personaId' => __('nuevo responsable'),
            'tipoVinculacion' => __('tipo de vinculación'),
        ];
    }

    public function guardar(RegistroMovimientos $movimientos): void
    {
        ReglasMovimiento::asegurarQueAdmiteEventos($this->equipo);
        $this->validate();

        if (! $this->bodega && $this->modoResponsable === 'existente'
            && $this->equipo->asignacionActual?->persona_id === $this->personaId
            && $this->equipo->asignacionActual?->sede_id === $this->sedeId
            && $this->equipo->asignacionActual?->piso_id === $this->pisoId
            && $this->equipo->asignacionActual?->dependencia_id === $this->dependenciaId) {
            $this->addError('personaId', __('El equipo ya está asignado a esa persona en esa ubicación.'));

            return;
        }

        $evento = DB::transaction(function () use ($movimientos) {
            $persona = null;

            if (! $this->bodega) {
                $persona = $this->modoResponsable === 'nueva'
                    ? Persona::create([
                        'nombre' => trim($this->nombre),
                        'cedula' => $this->cedula,
                        'cargo' => trim($this->cargo),
                        'dependencia_id' => $this->dependenciaId,
                        'tipo_vinculacion' => $this->tipoVinculacion,
                        'activo' => true,
                    ])
                    : Persona::findOrFail($this->personaId);
            }

            return $movimientos->trasladar($this->equipo, auth()->user(), [
                'persona' => $persona,
                'sede_id' => $this->sedeId,
                'piso_id' => $this->pisoId,
                'dependencia_id' => $this->dependenciaId,
                'fecha' => $this->fecha,
                'motivo' => $this->motivo,
            ]);
        });

        $this->eventoId = $evento->id;
        $this->equipo->refresh()->load(['asignacionActual.persona', 'asignacionActual.sede', 'asignacionActual.piso', 'asignacionActual.dependencia']);
    }

    public function render()
    {
        $termino = trim($this->buscarPersona);
        $digitos = preg_replace('/\D/', '', $termino);

        return view('livewire.movimientos.traslado-form', [
            'sedes' => Sede::orderBy('nombre')->get(),
            'pisos' => Piso::orderBy('numero')->get(),
            'dependencias' => Dependencia::orderBy('nombre')->get(),
            'personaElegida' => $this->personaId ? Persona::with('dependencia')->find($this->personaId) : null,
            'coincidencias' => mb_strlen($termino) >= 2
                ? Persona::query()
                    ->with('dependencia')
                    ->where('activo', true)
                    ->where(fn ($q) => $q->where('nombre', 'like', "%{$termino}%")
                        ->when($digitos !== '', fn ($q) => $q->orWhere('cedula', 'like', "%{$digitos}%")))
                    ->orderBy('nombre')
                    ->limit(8)
                    ->get()
                : collect(),
            'dadoDeBaja' => $this->equipo->estado_ciclo_vida === 'dado_de_baja',
            'bajaEnTramite' => ReglasMovimiento::bajaEnTramite($this->equipo),
        ]);
    }
}
