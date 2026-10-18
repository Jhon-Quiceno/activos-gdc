<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asignacion extends Model
{
    protected $table = 'asignaciones';

    protected $fillable = [
        'equipo_id',
        'persona_id',
        'sede_id',
        'piso_id',
        'dependencia_id',
        'puesto_trabajo_id',
        'fecha_inicio',
        'fecha_fin',
        'evento_origen_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Migración en dos tiempos: mientras exista la columna vieja `sede_id`,
        // se copia del piso, que es quien define la sede (RN-21). La migración de
        // cierre del día 2 borra `sede_id`; desde ahí la sede sale de `piso->sede`.
        static::saving(function (self $registro): void {
            if ($registro->piso_id && $registro->isDirty('piso_id')) {
                $sedeDelPiso = Piso::query()->whereKey($registro->piso_id)->value('sede_id');

                if ($sedeDelPiso) {
                    $registro->sede_id = $sedeDelPiso;
                }
            }
        });
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function piso(): BelongsTo
    {
        return $this->belongsTo(Piso::class);
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class);
    }

    public function eventoOrigen(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_origen_id');
    }
}
