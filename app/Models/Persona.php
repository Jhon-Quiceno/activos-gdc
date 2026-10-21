<?php

namespace App\Models;

use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Funcionario, contratista o pasante que responde por equipos (RF-18).
 *
 * Minimización (Ley 1581, RNF-19): solo se guardan los datos necesarios para
 * responder por un equipo. Nada de salario, RH, estado civil, etnia, salud ni
 * dirección de residencia, aunque vengan en la planta de Talento Humano.
 *
 * El nombre vive en `nombres` + `apellidos`, el documento en `numero_documento`
 * y el cargo en `cargo_id` (Decreto 607). Las columnas de texto viejas (`nombre`,
 * `cedula`, `cargo`) se borraron en la migración de cierre (docs/base-de-datos.md §6).
 */
class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    public const TIPOS_DOCUMENTO = [
        'CC' => 'Cédula de ciudadanía',
        'CE' => 'Cédula de extranjería',
        'PPT' => 'Permiso por protección temporal',
        'PA' => 'Pasaporte',
        'TI' => 'Tarjeta de identidad',
    ];

    public const VINCULACIONES = [
        'planta' => 'Planta',
        'contratista' => 'Contratista',
        'pasante' => 'Pasante',
    ];

    public const PROVISIONES = [
        'carrera_administrativa' => 'Carrera administrativa',
        'libre_nombramiento' => 'Libre nombramiento y remoción',
        'provisional' => 'Provisional',
        'temporal' => 'Temporal',
        'periodo' => 'Período',
        'no_aplica' => 'No aplica',
    ];

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'correo_institucional',
        'cargo_id',
        'grado',
        'encargo',
        'dependencia_id',
        'jefe_inmediato_id',
        'planta',
        'tipo_vinculacion',
        'tipo_provision',
        'numero_contrato',
        'fecha_fin_vinculacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'encargo' => 'boolean',
            'fecha_fin_vinculacion' => 'date',
        ];
    }

    /**
     * «Nombres Apellidos».
     */
    public function nombreCompleto(): string
    {
        return trim(($this->nombres ?? '').' '.($this->apellidos ?? ''));
    }

    /**
     * Denominación del cargo, con «(E)» si se ejerce en encargo.
     */
    public function cargoTexto(): ?string
    {
        $denominacion = $this->cargoOficial?->denominacion;

        if (blank($denominacion)) {
            return null;
        }

        return $this->encargo ? "{$denominacion} (E)" : $denominacion;
    }

    /** Cargo del catálogo oficial (Decreto 607). Se llama así porque `cargo` es el campo viejo de texto. */
    public function cargoOficial(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function jefeInmediato(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'jefe_inmediato_id');
    }

    public function subordinados(): HasMany
    {
        return $this->hasMany(Persona::class, 'jefe_inmediato_id');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }
}
