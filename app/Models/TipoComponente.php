<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoComponente extends Model
{
    protected $table = 'tipos_componente';

    protected $fillable = [
        'nombre',
        'es_periferico',
    ];

    protected function casts(): array
    {
        return [
            'es_periferico' => 'boolean',
        ];
    }

    public function componentes(): HasMany
    {
        return $this->hasMany(Componente::class);
    }
}
