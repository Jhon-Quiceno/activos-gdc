<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuestoTrabajo extends Model
{
    protected $table = 'puestos_trabajo';

    protected $fillable = [
        'nombre_o_codigo',
        'sede_id',
        'piso_id',
        'dependencia_id',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function piso(): BelongsTo
    {
        return $this->belongsTo(Piso::class);
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
