<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asignacion extends Model
{
    protected $table = 'asignaciones';

    protected $fillable = [
        'equipo_id',
        'persona_id',
        'piso_id',
        'dependencia_id',
        'puesto_trabajo_id',
        'fecha_inicio',
        'fecha_fin',
        'evento_origen_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function piso(): BelongsTo
    {
        return $this->belongsTo(Piso::class);
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class);
    }

    public function eventoOrigen(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_origen_id');
    }
}
