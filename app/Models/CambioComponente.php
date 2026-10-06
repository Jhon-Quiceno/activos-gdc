<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioComponente extends Model
{
    protected $table = 'cambios_componente';

    protected $fillable = [
        'evento_id',
        'accion',
        'componente_retirado_id',
        'serial_retirado',
        'componente_instalado_id',
        'serial_instalado',
        'motivo',
        'destino_retirado',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function componenteRetirado(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_retirado_id');
    }

    public function componenteInstalado(): BelongsTo
    {
        return $this->belongsTo(Componente::class, 'componente_instalado_id');
    }
}
