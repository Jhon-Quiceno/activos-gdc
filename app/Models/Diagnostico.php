<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnostico extends Model
{
    protected $fillable = [
        'evento_id',
        'estado_encontrado',
        'causa',
        'recomendaciones',
        'es_baja',
        'motivo_baja_id',
    ];

    protected function casts(): array
    {
        return [
            'es_baja' => 'boolean',
        ];
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function motivoBaja(): BelongsTo
    {
        return $this->belongsTo(MotivoBaja::class);
    }
}
