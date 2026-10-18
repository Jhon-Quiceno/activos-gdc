<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Municipio de Córdoba (DIVIPOLA). Catálogo fijo: no lleva timestamps.
 */
class Municipio extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'codigo_dane',
        'nombre',
    ];

    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class);
    }
}
