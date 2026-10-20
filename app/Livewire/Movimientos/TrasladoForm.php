<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Concerns\FormularioPersona;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\Equipo;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Traslado o cambio de responsable" (CU-05, RF-18 a RF-21).
 *
 * El nuevo responsable se elige de las personas ya registradas o se crea en el
 * momento con los campos v4 (documento, nombres, cargo del Decreto 607,
 * vinculación…). También se puede dejar el equipo sin asignar (bodega).
 *
 * La ubicación se elige con x-ui.ubicacion-select: sede → piso de esa sede →
 * dependencia (RF-19). La asignación guarda el piso, que define la sede (RN-21).
 *
 * Al guardar, la lógica de RegistroMovimientos::trasladar() cierra la
 * asignación anterior, abre la nueva, crea el evento «Pendiente de firma» y
 * genera los dos formatos; la pantalla muestra entonces el panel para
 * descargarlos y subir los firmados.
 */
class TrasladoForm extends Component
{
    use FormularioPersona;

    public Equipo $equipo;

    public bool $bodega = false;

    /** 'existente' | 'nueva' */
    public string $modoResponsable = 'existente';

    public string $buscarPersona = '';

    public ?int $personaId = null;

    public ?int $sedeId = null;

    public ?int $pisoId = null;

    public ?int $dependenciaId = null;

    public string $fecha = '';

    public string $motivo = '';

    /** Evento creado al guardar; mientras sea null se muestra el formulario. */
    public ?int $eventoId = null;

    private const RELACIONES = [
        'tipoEquipo',
        'marca',
        'modeloEquipo',
        'asignacionActual.persona.cargoOficial',
        'asignacionActual.piso.sede',
        'asignacionActual.dependencia',
    ];

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load(self::RELACIONES);

        // Prellenamos la ubicación con la actual: el equipo sigue físicamente
        // en algún lado aunque quede sin responsable.
        $this->pisoId = $this->equipo->asignacionActual?->piso_id;
        $this->sedeId = $this->equipo->asignacionActual?->piso?->sede_id;
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
            // RN-21: el piso tiene que ser de la sede elegida.
            'pisoId' => ['required', Rule::exists('pisos', 'id')->where('sede_id', $this->sedeId)],
            // RN-13: la fecha del evento no puede ser futura.
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'motivo' => ['required', 'string', 'max:1000'],
        ];

        if ($this->bodega) {
            return $rules + ['dependenciaId' => ['nullable', 'exists:dependencias,id']];
        }

        $rules['dependenciaId'] = ['required', 'exists:dependencias,id'];

        if ($this->modoResponsable === 'nueva') {
            return $rules + $this->reglasPersona();
        }

        return $rules + [
            'personaId' => ['required', Rule::exists('personas', 'id')->where('activo', true)],
        ];
    }

    protected function messages(): array
    {
        return ['pisoId.exists' => __('Elige un piso de la sede seleccionada.')] + $this->mensajesPersona();
    }

    protected function validationAttributes(): array
    {
        return [
            'sedeId' => __('sede'),
            'pisoId' => __('piso'),
            'dependenciaId' => __('dependencia'),
            'personaId' => __('nuevo responsable'),
        ] + $this->atributosPersona();
    }

    public function guardar(RegistroMovimientos $movimientos): void
    {
        ReglasMovimiento::asegurarQueAdmiteEventos($this->equipo);

        if ($this->modoResponsable === 'nueva') {
            $this->updatedNumeroDocumento();
        }

        $this->validate();

        $actual = $this->equipo->asignacionActual;
        if (! $this->bodega && $this->modoResponsable === 'existente'
            && $actual?->persona_id === $this->personaId
            && $actual?->piso_id === $this->pisoId
            && $actual?->dependencia_id === $this->dependenciaId) {
            $this->addError('personaId', __('El equipo ya está asignado a esa persona en esa ubicación.'));

            return;
        }

        $evento = DB::transaction(function () use ($movimientos) {
            $persona = null;

            if (! $this->bodega) {
                $persona = $this->modoResponsable === 'nueva'
                    ? Persona::create($this->datosPersona($this->dependenciaId))
                    : Persona::findOrFail($this->personaId);
            }

            return $movimientos->trasladar($this->equipo, auth()->user(), [
                'persona' => $persona,
                'piso_id' => $this->pisoId,
                'dependencia_id' => $this->dependenciaId,
                'fecha' => $this->fecha,
                'motivo' => $this->motivo,
            ]);
        });

        $this->eventoId = $evento->id;
        $this->equipo->refresh()->load(self::RELACIONES);
    }

    public function render()
    {
        $termino = trim($this->buscarPersona);
        $documento = preg_replace('/[\s.,\-]/', '', $termino);

        return view('livewire.movimientos.traslado-form', [
            'personaElegida' => $this->personaId ? Persona::with(['dependencia', 'cargoOficial'])->find($this->personaId) : null,
            'coincidencias' => mb_strlen($termino) >= 2
                ? Persona::query()
                    ->with('dependencia')
                    ->where('activo', true)
                    ->where(fn ($q) => $q->whereRaw("CONCAT_WS(' ', nombres, apellidos) LIKE ?", ["%{$termino}%"])
                        ->when($documento !== '', fn ($q) => $q->orWhere('numero_documento', 'like', "%{$documento}%")))
                    ->orderBy('nombres')
                    ->limit(8)
                    ->get()
                : collect(),
            'dadoDeBaja' => $this->equipo->estado_ciclo_vida === 'dado_de_baja',
            'bajaEnTramite' => ReglasMovimiento::bajaEnTramite($this->equipo),
        ] + ($this->modoResponsable === 'nueva' ? $this->opcionesPersona() : []));
    }
}
