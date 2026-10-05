<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SistemaOperativo extends Model
{
    protected $table = 'sistemas_operativos';

    protected $fillable = [
        'nombre',
    ];

    public function configuracionesComputo(): HasMany
    {
        return $this->hasMany(ConfiguracionComputo::class);
    }
}
