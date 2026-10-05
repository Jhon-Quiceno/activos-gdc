<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dependencia extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function puestosTrabajo(): HasMany
    {
        return $this->hasMany(PuestoTrabajo::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }
}
