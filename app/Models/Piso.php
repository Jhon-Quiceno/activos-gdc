<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piso extends Model
{
    protected $fillable = [
        'numero',
    ];

    public function puestosTrabajo(): HasMany
    {
        return $this->hasMany(PuestoTrabajo::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }
}
