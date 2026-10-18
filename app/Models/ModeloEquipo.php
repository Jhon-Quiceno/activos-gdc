<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de equipo por marca y tipo (tabla `modelos`). Se llama ModeloEquipo
 * para no confundirse con Illuminate\Database\Eloquent\Model.
 */
class ModeloEquipo extends Model
{
    protected $table = 'modelos';

    protected $fillable = [
        'marca_id',
        'tipo_equipo_id',
        'nombre',
    ];

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'modelo_id');
    }
}
