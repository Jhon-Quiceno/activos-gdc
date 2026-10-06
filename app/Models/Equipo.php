<?php

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

            self::validarJustificacionCodigoActivo($equipo);
        });

        static::updating(function (Equipo $equipo): void {
            // Revalidar también si solo cambia la justificación (no el código): si
            // alguien la borra dejando un código duplicado, tiene que fallar igual.
            if ($equipo->isDirty('codigo_activo') || $equipo->isDirty('codigo_activo_justificacion')) {
                self::validarJustificacionCodigoActivo($equipo);
            }
        });
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
        if (empty($equipo->codigo_activo)) {
            return;
        }

        $existeEnOtroEquipo = static::where('codigo_activo', $equipo->codigo_activo)
            ->when($equipo->exists, fn ($query) => $query->whereKeyNot($equipo->getKey()))
            ->exists();

        if ($existeEnOtroEquipo && trim((string) $equipo->codigo_activo_justificacion) === '') {
            throw new InvalidArgumentException(
                'El código de activo ya existe en otro equipo (RN-03): hace falta indicar codigo_activo_justificacion.'
            );
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
