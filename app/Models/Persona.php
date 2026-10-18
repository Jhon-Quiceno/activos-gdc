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
 * Migración en dos tiempos (docs/base-de-datos.md §6): los campos viejos
 * `nombre`, `cedula` y `cargo` (texto) siguen existiendo hasta la migración
 * de cierre del día 2. Mientras tanto este modelo los mantiene sincronizados
 * con los nuevos (`nombres` + `apellidos`, `numero_documento`, `cargo_id`),
 * así el código que aún usa los viejos sigue funcionando. Código nuevo: usar
 * siempre los campos nuevos.
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
        // Campos viejos: se borran en la migración de cierre del día 2.
        'nombre',
        'cedula',
        'cargo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'encargo' => 'boolean',
            'fecha_fin_vinculacion' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Persona $persona): void {
            $persona->sincronizarCamposViejos();
        });
    }

    /**
     * Mantiene iguales los campos viejos y los nuevos mientras conviven. Gana
     * el que se haya cambiado; si los dos cambiaron, se respetan ambos.
     */
    protected function sincronizarCamposViejos(): void
    {
        $nombreCompleto = trim(($this->nombres ?? '').' '.($this->apellidos ?? ''));

        if ($nombreCompleto !== '' && (blank($this->nombre) || ($this->isDirty(['nombres', 'apellidos']) && ! $this->isDirty('nombre')))) {
            $this->nombre = $nombreCompleto;
        } elseif (filled($this->nombre) && (blank($this->nombres) || ($this->isDirty('nombre') && ! $this->isDirty(['nombres', 'apellidos'])))) {
            $this->nombres = $this->nombre;
            $this->apellidos = null;
        }

        if (filled($this->numero_documento) && (blank($this->cedula) || ($this->isDirty('numero_documento') && ! $this->isDirty('cedula')))) {
            $this->cedula = $this->numero_documento;
        } elseif (filled($this->cedula) && (blank($this->numero_documento) || ($this->isDirty('cedula') && ! $this->isDirty('numero_documento')))) {
            $this->numero_documento = $this->cedula;
        }

        if ($this->cargo_id && (blank($this->cargo) || ($this->isDirty('cargo_id') && ! $this->isDirty('cargo')))) {
            $this->cargo = Cargo::query()->whereKey($this->cargo_id)->value('denominacion');
        }
    }

    /**
     * «Nombres Apellidos»; cae al campo viejo si aún no se migró la persona.
     */
    public function nombreCompleto(): string
    {
        $completo = trim(($this->nombres ?? '').' '.($this->apellidos ?? ''));

        return $completo !== '' ? $completo : (string) $this->nombre;
    }

    /**
     * Denominación del cargo, con «(E)» si se ejerce en encargo.
     */
    public function cargoTexto(): ?string
    {
        $denominacion = $this->cargoOficial?->denominacion ?? $this->cargo;

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
