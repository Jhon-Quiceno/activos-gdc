<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Piso de una sede (modelo v4: cada sede tiene los suyos). El piso define la
 * sede: una asignación guarda el piso, y de ahí sale la sede (RN-21).
 *
 * Mientras dure la migración en dos tiempos, `sede_id` puede venir vacío en
 * datos de prueba viejos; la migración de cierre del día 2 lo vuelve obligatorio.
 */
class Piso extends Model
{
    protected $fillable = [
        'sede_id',
        'numero',
        'nombre',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
        ];
    }

    /**
     * «Piso 3», «Local 44» o «Planta baja».
     */
    public function etiqueta(): string
    {
        if ($this->nombre) {
            return $this->nombre;
        }

        return $this->numero === 0 ? 'Planta baja' : "Piso {$this->numero}";
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    /** Dependencias que funcionan en este piso según el PAMP 2026. */
    public function dependencias(): BelongsToMany
    {
        return $this->belongsToMany(Dependencia::class, 'ubicaciones_dependencia');
    }

    public function puestosTrabajo(): HasMany
    {
        return $this->hasMany(PuestoTrabajo::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }
}
