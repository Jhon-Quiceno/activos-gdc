<?php

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    protected $fillable = [
        'serial',
        'codigo_activo',
        'codigo_activo_justificacion',
        'qr_uuid',
        'tipo_equipo_id',
        'marca_id',
        'modelo',
        'caracteristicas',
        'propiedad',
        'propietario_tercero',
        'figura_tercero',
        'estado_funcionamiento',
        'observaciones',
        'estado_ciclo_vida',
        'verificacion',
        'puesto_trabajo_id',
        'importacion_id',
        'fila_origen_importacion',
    ];

    protected function casts(): array
    {
        return [
            'caracteristicas' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Equipo $equipo): void {
            // RN-19: el qr_uuid es un identificador permanente e independiente del serial
            // o del código de activo. Se genera automáticamente si no viene dado.
            if (empty($equipo->qr_uuid)) {
                $equipo->qr_uuid = (string) Str::uuid();
            }
        });

        // Un solo hook para no arriesgar que una futura extensión solo actualice
        // creating o updating y se salte la validación en el otro. `! $equipo->exists`
        // reproduce el "siempre" de creating (antes de insertar nunca hay original
        // contra el que comparar); en update se preserva el isDirty de antes: revalida
        // también si solo cambia la justificación (si alguien la borra dejando un
        // código duplicado, tiene que fallar igual).
        static::saving(function (Equipo $equipo): void {
            if (! $equipo->exists
                || $equipo->isDirty('codigo_activo')
                || $equipo->isDirty('codigo_activo_justificacion')) {
                self::validarJustificacionCodigoActivo($equipo);
            }
        });
    }

    /**
     * Normaliza espacios en el código de activo al guardar, para que la
     * comparación de duplicados (RN-03) y el valor persistido sean consistentes
     * sin importar desde dónde se asigne (formulario, importación, factory...).
     * Un valor que queda vacío tras el trim se guarda como null, igual que si
     * nunca se hubiera indicado código.
     */
    public function setCodigoActivoAttribute(mixed $value): void
    {
        $normalizado = $value === null ? null : trim((string) $value);

        $this->attributes['codigo_activo'] = $normalizado === '' ? null : $normalizado;
    }

    /**
     * RN-03: el código de activo puede repetirse (ej. un All in One y su pantalla
     * integrada comparten código), pero el sistema exige justificación cuando eso
     * pasa. Se valida acá, a nivel de modelo, para que valga sin importar desde qué
     * formulario o importación se cree/edite el equipo (mismo criterio que la
     * inmutabilidad de Evento).
     */
    protected static function validarJustificacionCodigoActivo(Equipo $equipo): void
    {
        // is_null + trim en vez de empty(): empty("0") es true en PHP, así que un
        // código de activo literal "0" quedaría sin validar con el chequeo anterior.
        if (is_null($equipo->codigo_activo) || trim((string) $equipo->codigo_activo) === '') {
            return;
        }

        $codigoActivo = trim((string) $equipo->codigo_activo);

        // lockForUpdate(): sin esto, dos requests concurrentes podrían leer "no
        // existe duplicado" antes de que ninguna haya insertado (TOCTOU) y las dos
        // pasarían la validación. Solo tiene efecto real dentro de una transacción
        // (ver callers: Equipo::create()/save() deben invocarse dentro de
        // DB::transaction()).
        $existeEnOtroEquipo = static::where('codigo_activo', $codigoActivo)
            ->when($equipo->exists, fn ($query) => $query->whereKeyNot($equipo->getKey()))
            ->lockForUpdate()
            ->exists();

        if ($existeEnOtroEquipo && trim((string) $equipo->codigo_activo_justificacion) === '') {
            throw ValidationException::withMessages([
                'codigo_activo' => ['El código de activo ya existe en otro equipo (RN-03): hace falta indicar codigo_activo_justificacion.'],
            ]);
        }
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class);
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(Importacion::class);
    }

    public function configuracionComputo(): HasOne
    {
        return $this->hasOne(ConfiguracionComputo::class);
    }

    public function componentes(): HasMany
    {
        return $this->hasMany(Componente::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function etiquetasQr(): HasMany
    {
        return $this->hasMany(EtiquetaQr::class);
    }

    /**
     * Asignación abierta/actual del equipo (fecha_fin null), si existe.
     */
    public function asignacionActual(): HasOne
    {
        return $this->hasOne(Asignacion::class)->whereNull('fecha_fin')->latestOfMany('fecha_inicio');
    }
}
