<?php

namespace App\Models;

use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    protected $fillable = [
        'serial',
        'codigo_activo',
        'qr_uuid',
        'tipo_equipo_id',
        'marca_id',
        'modelo',
        'propiedad',
        'propietario_tercero',
        'estado_funcionamiento',
        'estado_ciclo_vida',
        'verificacion',
        'puesto_trabajo_id',
        'importacion_id',
        'fila_origen_importacion',
    ];

    protected static function booted(): void
    {
        static::creating(function (Equipo $equipo): void {
            // RN-19: el qr_uuid es un identificador permanente e independiente del serial
            // o del código de activo. Se genera automáticamente si no viene dado.
            if (empty($equipo->qr_uuid)) {
                $equipo->qr_uuid = (string) Str::uuid();
            }
        });
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
