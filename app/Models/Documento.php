<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    protected $fillable = [
        'evento_id',
        'tipo',
        'consecutivo',
        'archivo_path',
        'firmado',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'firmado' => 'boolean',
            'fecha' => 'date',
        ];
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }
}
