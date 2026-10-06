<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtiquetaQr extends Model
{
    protected $table = 'etiquetas_qr';

    protected $fillable = [
        'equipo_id',
        'fecha_impresion',
        'usuario_id',
        'motivo',
        'lote',
    ];

    protected function casts(): array
    {
        return [
            'fecha_impresion' => 'datetime',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
