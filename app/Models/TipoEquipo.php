<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipo extends Model
{
    protected $table = 'tipos_equipo';

    protected $fillable = [
        'nombre',
        'familia',
        'campos_aplicables',
    ];

    protected function casts(): array
    {
        return [
            'campos_aplicables' => 'array',
        ];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
