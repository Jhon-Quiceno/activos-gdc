<?php

namespace App\Livewire\Movimientos\Concerns;

use App\Models\Cargo;
use App\Models\Persona;
use Illuminate\Validation\Rule;

/**
 * Datos de la persona responsable con los campos del modelo v4 (RF-18):
 * tipo y número de documento, nombres y apellidos, correo institucional,
 * cargo del Decreto 607 con grado y encargo, vinculación, provisión, contrato
 * y fin de la vinculación (que alimenta el paz y salvo, RF-23).
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

    /** Campos del formulario, para limpiarlos de una vez. */
    protected function camposPersona(): array
    {
        return ['tipoDocumento', 'numeroDocumento', 'nombres', 'apellidos', 'correoInstitucional', 'cargoId', 'grado', 'encargo', 'tipoVinculacion', 'tipoProvision', 'numeroContrato', 'fechaFinVinculacion'];
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
        ];
    }

    protected function atributosPersona(): array
    {
        return [
            'tipoDocumento' => __('tipo de documento'),
            'numeroDocumento' => __('número de documento'),
            'correoInstitucional' => __('correo institucional'),
            'cargoId' => __('cargo'),
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
        ];
    }
}
