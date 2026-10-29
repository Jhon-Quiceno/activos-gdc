<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Concerns\FormularioPersona;
use App\Livewire\Movimientos\Soporte\PuestosDeTrabajo;
use App\Livewire\Movimientos\Soporte\RegistroMovimientos;
use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\PuestoTrabajo;
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
 *
 * RF-05: si el equipo está en un puesto de trabajo (su asignación abierta tiene
 * puesto), se puede trasladar el puesto completo: todos sus equipos van al mismo
 * responsable y ubicación, cada uno con su evento, y se genera un solo juego de
 * formatos para todo el puesto (RegistroMovimientos::trasladarPuesto()).
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

    /** RF-05: trasladar todos los equipos del puesto de este equipo. */
    public bool $trasladarPuesto = false;

    /** Puesto en el que quedan los equipos del puesto trasladado (null: ninguno). */
    public ?int $puestoDestinoId = null;

    /** Cuántos equipos se trasladaron (1 si fue un traslado normal). */
    public int $equiposTrasladados = 1;

    /** Evento creado al guardar; mientras sea null se muestra el formulario. */
    public ?int $eventoId = null;

    private const RELACIONES = [
        'tipoEquipo',
        'marca',
        'modeloEquipo',
        'asignacionActual.persona.cargoOficial',
        'asignacionActual.piso.sede',
        'asignacionActual.dependencia',
        'asignacionActual.puestoTrabajo',
    ];

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load(self::RELACIONES);

        // Prellenamos la ubicación con la actual: el equipo sigue físicamente
        // en algún lado aunque quede sin responsable.
        $this->pisoId = $this->equipo->asignacionActual?->piso_id;
        $this->sedeId = $this->equipo->asignacionActual?->piso?->sede_id;
        $this->dependenciaId = $this->equipo->asignacionActual?->dependencia_id;
        $this->puestoDestinoId = $this->equipo->asignacionActual?->puesto_trabajo_id;
        $this->fecha = now()->toDateString();
    }

    /**
     * Puesto de trabajo en el que está hoy el equipo (RF-05), si está en uno.
     */
    private function puestoActual(): ?PuestoTrabajo
    {
        return $this->equipo->asignacionActual?->puestoTrabajo;
    }

    public function updatedPisoId(): void
    {
        // El puesto destino tiene que ser del piso destino.
        if ($this->puestoDestinoId && ! PuestoTrabajo::query()->whereKey($this->puestoDestinoId)->where('piso_id', $this->pisoId)->exists()) {
            $this->puestoDestinoId = null;
        }
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

        if ($this->trasladarPuesto && $this->puestoActual()) {
            $rules['puestoDestinoId'] = [
                'nullable',
                Rule::exists('puestos_trabajo', 'id')->where('piso_id', $this->pisoId),
            ];
        }

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
        return [
            'pisoId.exists' => __('Elige un piso de la sede seleccionada.'),
            'puestoDestinoId.exists' => __('Elige un puesto de trabajo del piso destino, o ninguno.'),
        ] + $this->mensajesPersona();
    }

    protected function validationAttributes(): array
    {
        return [
            'sedeId' => __('sede'),
            'pisoId' => __('piso'),
            'dependenciaId' => __('dependencia'),
            'personaId' => __('nuevo responsable'),
            'puestoDestinoId' => __('puesto de trabajo destino'),
        ] + $this->atributosPersona();
    }

    public function guardar(RegistroMovimientos $movimientos): void
    {
        ReglasMovimiento::asegurarQueAdmiteEventos($this->equipo);

        if ($this->modoResponsable === 'nueva') {
            $this->updatedNumeroDocumento();
        }

        $this->validate();

        $puesto = $this->trasladarPuesto ? $this->puestoActual() : null;

        $actual = $this->equipo->asignacionActual;
        if (! $this->bodega && $this->modoResponsable === 'existente'
            && $actual?->persona_id === $this->personaId
            && $actual?->piso_id === $this->pisoId
            && $actual?->dependencia_id === $this->dependenciaId
            && (! $puesto || $actual?->puesto_trabajo_id === $this->puestoDestinoId)) {
            $this->addError('personaId', __('El equipo ya está asignado a esa persona en esa ubicación.'));

            return;
        }

        $eventos = DB::transaction(function () use ($movimientos, $puesto) {
            $persona = null;

            if (! $this->bodega) {
                $persona = $this->modoResponsable === 'nueva'
                    ? Persona::create($this->datosPersona($this->dependenciaId))
                    : Persona::findOrFail($this->personaId);
            }

            $datos = [
                'persona' => $persona,
                'piso_id' => $this->pisoId,
                'dependencia_id' => $this->dependenciaId,
                'fecha' => $this->fecha,
                'motivo' => $this->motivo,
            ];

            if ($puesto) {
                return $movimientos->trasladarPuesto($puesto, auth()->user(), $datos + [
                    'puesto_destino_id' => $this->bodega ? null : $this->puestoDestinoId,
                ]);
            }

            return collect([$movimientos->trasladar($this->equipo, auth()->user(), $datos)]);
        });

        // El panel de firmas se abre sobre el evento de ESTE equipo; en un
        // traslado de puesto, lo que se sube ahí vale para todos sus equipos.
        $this->eventoId = ($eventos->firstWhere('equipo_id', $this->equipo->id) ?? $eventos->first())->id;
        $this->equiposTrasladados = $eventos->count();
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
            'puestoActual' => $puesto = $this->puestoActual(),
            'equiposDelPuesto' => $puesto && $this->eventoId === null ? app(PuestosDeTrabajo::class)->equiposDelPuesto($puesto) : collect(),
            'puestosDestino' => $puesto && $this->pisoId
                ? PuestoTrabajo::query()->where('piso_id', $this->pisoId)->orderBy('nombre_o_codigo')->get()
                : collect(),
            'dadoDeBaja' => $this->equipo->estado_ciclo_vida === 'dado_de_baja',
            'bajaEnTramite' => ReglasMovimiento::bajaEnTramite($this->equipo),
        ] + ($this->modoResponsable === 'nueva' ? $this->opcionesPersona() : []));
    }
}
