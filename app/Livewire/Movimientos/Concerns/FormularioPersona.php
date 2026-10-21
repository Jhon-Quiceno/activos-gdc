<?php

namespace App\Livewire\Movimientos\Concerns;

use App\Models\Cargo;
use App\Models\Persona;
use Illuminate\Validation\Rule;

/**
 * Datos de la persona responsable con los campos del modelo v4 (RF-18):
 * tipo y número de documento, nombres y apellidos, correo institucional,
 * cargo del Decreto 607 con grado y encargo, jefe inmediato, planta (Central o
 * Educación), vinculación, provisión, contrato y fin de la vinculación (que
 * alimenta el paz y salvo, RF-23).
 *
 * Lo comparten Responsables (crear y editar) y el traslado (persona nueva).
 * La dependencia la pone cada pantalla, porque en el traslado es la del destino.
 */
trait FormularioPersona
{
    public string $tipoDocumento = 'CC';

    public string $numeroDocumento = '';

    public string $nombres = '';

    public string $apellidos = '';

    public string $correoInstitucional = '';

    public ?int $cargoId = null;

    public string $grado = '';

    public bool $encargo = false;

    public string $tipoVinculacion = '';

    public string $tipoProvision = 'no_aplica';

    public string $numeroContrato = '';

    public string $fechaFinVinculacion = '';

    /** Jefe inmediato (otra persona) y el texto con el que se busca. */
    public ?int $jefeInmediatoId = null;

    public string $jefeBusqueda = '';

    /** Planta de personal de la que viene: '' (no aplica), central o educacion. */
    public string $planta = '';

    /** Campos del formulario, para limpiarlos de una vez. */
    protected function camposPersona(): array
    {
        return ['tipoDocumento', 'numeroDocumento', 'nombres', 'apellidos', 'correoInstitucional', 'cargoId', 'grado', 'encargo', 'jefeInmediatoId', 'jefeBusqueda', 'planta', 'tipoVinculacion', 'tipoProvision', 'numeroContrato', 'fechaFinVinculacion'];
    }

    /** Valores de `personas.planta`: los mismos que guarda la carga de planta (RF-55). */
    protected function plantasPersona(): array
    {
        return ['central' => __('Central'), 'educacion' => __('Educación')];
    }

    public function elegirJefe(int $personaId): void
    {
        $this->jefeInmediatoId = $personaId;
        $this->jefeBusqueda = '';
        $this->resetValidation('jefeInmediatoId');
    }

    public function quitarJefe(): void
    {
        $this->jefeInmediatoId = null;
    }

    /** La persona que se está editando, si la pantalla edita (Responsables); el traslado solo crea. */
    protected function idPersonaEnEdicion(): ?int
    {
        return property_exists($this, 'editandoId') ? $this->editandoId : null;
    }

    /**
     * «1.067.888.999» → «1067888999»; un pasaporte queda en mayúsculas.
     */
    public function updatedNumeroDocumento(): void
    {
        $this->numeroDocumento = mb_strtoupper(preg_replace('/[\s.,\-]+/u', '', trim($this->numeroDocumento)));
    }

    /**
     * @param  int|null  $ignorarId  La persona que se está editando (su propio documento no cuenta como repetido).
     */
    protected function reglasPersona(?int $ignorarId = null): array
    {
        return [
            'tipoDocumento' => ['required', Rule::in(array_keys(Persona::TIPOS_DOCUMENTO))],
            'numeroDocumento' => [
                'required',
                'max:20',
                $this->tipoDocumento === 'CC' ? 'regex:/^\d{5,12}$/' : 'regex:/^[A-Z0-9]{4,20}$/',
                Rule::unique('personas', 'numero_documento')->ignore($ignorarId),
            ],
            'nombres' => ['required', 'string', 'max:80'],
            'apellidos' => ['nullable', 'string', 'max:80'],
            'correoInstitucional' => ['nullable', 'email', 'max:120'],
            'cargoId' => ['nullable', 'exists:cargos,id'],
            'grado' => ['nullable', 'regex:/^\d{1,2}$/'],
            // Nadie es su propio jefe inmediato.
            'jefeInmediatoId' => ['nullable', 'integer', 'exists:personas,id', ...($ignorarId ? ['not_in:'.$ignorarId] : [])],
            'planta' => ['nullable', Rule::in(array_keys($this->plantasPersona()))],
            'tipoVinculacion' => ['required', Rule::in(array_keys(Persona::VINCULACIONES))],
            'tipoProvision' => ['required', Rule::in(array_keys(Persona::PROVISIONES))],
            'numeroContrato' => [$this->tipoVinculacion === 'contratista' ? 'required' : 'nullable', 'string', 'max:40'],
            'fechaFinVinculacion' => ['nullable', 'date'],
        ];
    }

    protected function mensajesPersona(): array
    {
        return [
            'numeroDocumento.regex' => $this->tipoDocumento === 'CC'
                ? __('La cédula solo lleva números (entre 5 y 12).')
                : __('El documento solo lleva letras y números.'),
            'numeroDocumento.unique' => __('Ya hay una persona con ese documento.'),
            'numeroContrato.required' => __('Un contratista necesita el número de contrato.'),
            'jefeInmediatoId.not_in' => __('Una persona no puede ser su propio jefe inmediato.'),
        ];
    }

    protected function atributosPersona(): array
    {
        return [
            'tipoDocumento' => __('tipo de documento'),
            'numeroDocumento' => __('número de documento'),
            'correoInstitucional' => __('correo institucional'),
            'cargoId' => __('cargo'),
            'jefeInmediatoId' => __('jefe inmediato'),
            'planta' => __('planta'),
            'tipoVinculacion' => __('tipo de vinculación'),
            'tipoProvision' => __('provisión'),
            'numeroContrato' => __('número de contrato'),
            'fechaFinVinculacion' => __('fin de la vinculación'),
        ];
    }

    /** Columnas de `personas` listas para guardar. */
    protected function datosPersona(?int $dependenciaId): array
    {
        $texto = fn (string $valor) => trim($valor) !== '' ? trim($valor) : null;

        return [
            'tipo_documento' => $this->tipoDocumento,
            'numero_documento' => $this->numeroDocumento,
            'nombres' => trim($this->nombres),
            'apellidos' => $texto($this->apellidos),
            'correo_institucional' => $texto($this->correoInstitucional),
            'cargo_id' => $this->cargoId,
            'grado' => $this->grado !== '' ? str_pad($this->grado, 2, '0', STR_PAD_LEFT) : null,
            'encargo' => $this->cargoId !== null && $this->encargo,
            'dependencia_id' => $dependenciaId,
            'jefe_inmediato_id' => $this->jefeInmediatoId,
            'planta' => $this->planta !== '' ? $this->planta : null,
            'tipo_vinculacion' => $this->tipoVinculacion,
            'tipo_provision' => $this->tipoProvision,
            'numero_contrato' => $texto($this->numeroContrato),
            'fecha_fin_vinculacion' => $this->fechaFinVinculacion !== '' ? $this->fechaFinVinculacion : null,
            'activo' => true,
        ];
    }

    protected function llenarPersona(Persona $persona): void
    {
        $this->tipoDocumento = $persona->tipo_documento ?? 'CC';
        $this->numeroDocumento = (string) $persona->numero_documento;
        $this->nombres = (string) ($persona->nombres ?? $persona->nombreCompleto());
        $this->apellidos = (string) $persona->apellidos;
        $this->correoInstitucional = (string) $persona->correo_institucional;
        $this->cargoId = $persona->cargo_id;
        $this->grado = (string) $persona->grado;
        $this->encargo = (bool) $persona->encargo;
        $this->jefeInmediatoId = $persona->jefe_inmediato_id;
        $this->jefeBusqueda = '';
        $this->planta = (string) $persona->planta;
        $this->tipoVinculacion = (string) $persona->tipo_vinculacion;
        $this->tipoProvision = $persona->tipo_provision ?? 'no_aplica';
        $this->numeroContrato = (string) $persona->numero_contrato;
        $this->fechaFinVinculacion = (string) $persona->fecha_fin_vinculacion?->toDateString();
    }

    /** Listas para los selects de la vista parcial. */
    protected function opcionesPersona(): array
    {
        return [
            'cargos' => Cargo::orderBy('codigo')->orderBy('denominacion')->get(),
            'tiposDocumento' => Persona::TIPOS_DOCUMENTO,
            'vinculaciones' => Persona::VINCULACIONES,
            'provisiones' => Persona::PROVISIONES,
            'plantas' => $this->plantasPersona(),
            'jefeElegido' => $this->jefeInmediatoId ? Persona::with('cargoOficial')->find($this->jefeInmediatoId) : null,
            'jefeCandidatos' => $this->candidatosAJefe(),
        ];
    }

    /**
     * Hasta 8 personas activas por nombre o documento (la planta real tiene cientos,
     * así que no cabe en un select). Nunca ofrece a la persona que se está editando.
     */
    protected function candidatosAJefe()
    {
        $termino = trim($this->jefeBusqueda);

        if (mb_strlen($termino) < 2) {
            return collect();
        }

        $documento = preg_replace('/[\s.,\-]/', '', $termino);

        return Persona::query()
            ->with('cargoOficial')
            ->where('activo', true)
            ->when($this->idPersonaEnEdicion(), fn ($q, $id) => $q->whereKeyNot($id))
            ->where(fn ($q) => $q
                ->whereRaw("CONCAT_WS(' ', nombres, apellidos) LIKE ?", ["%{$termino}%"])
                ->when($documento !== '', fn ($q) => $q->orWhere('numero_documento', 'like', "%{$documento}%")))
            ->orderBy('nombres')
            ->orderBy('apellidos')
            ->limit(8)
            ->get();
    }
}
