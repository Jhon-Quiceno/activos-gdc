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
        'equipo_destino_id',
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

    /** Equipo que recibió lo retirado, si el destino es «otro_equipo» (RF-17). */
    public function equipoDestino(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_destino_id');
    }
}
