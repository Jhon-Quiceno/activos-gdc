<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuestoTrabajo extends Model
{
    protected $table = 'puestos_trabajo';

    protected $fillable = [
        'nombre_o_codigo',
        'sede_id',
        'piso_id',
        'dependencia_id',
    ];

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

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
