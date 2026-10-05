<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotivoBaja extends Model
{
    protected $table = 'motivos_baja';

    protected $fillable = [
        'nombre',
    ];

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(Diagnostico::class);
    }
}
