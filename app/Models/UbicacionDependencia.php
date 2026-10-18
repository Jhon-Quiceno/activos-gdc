<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * En qué piso (y por lo tanto en qué sede) funciona una dependencia, según el
 * Programa Anual de Mantenimiento 2026. Se usa para proponer ubicaciones.
 */
class UbicacionDependencia extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ubicaciones_dependencia';

    protected $fillable = [
        'dependencia_id',
        'piso_id',
    ];

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function piso(): BelongsTo
    {
        return $this->belongsTo(Piso::class);
    }
}
