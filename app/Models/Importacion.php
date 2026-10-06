<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $fillable = [
        'archivo',
        'fecha',
        'usuario_id',
        'filas',
        'errores',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'errores' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
