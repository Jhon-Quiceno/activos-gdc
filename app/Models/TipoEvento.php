<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de tipos de evento de la hoja de vida (RF-13). Los 8 tipos del
 * sistema (`es_sistema = true`) no se editan ni se desactivan: tienen efectos
 * propios (traslado, baja, valoración, cambio de componente...). Un tipo
 * configurable nuevo solo registra un hecho (fecha, descripción, autor y
 * adjuntos) vía HistorialService::registrar(); nunca se borra, solo se
 * desactiva (`activo = false`), porque los eventos existentes lo referencian.
 */
class TipoEvento extends Model
{
    protected $table = 'tipos_evento';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'requiere_firma',
        'es_sistema',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_firma' => 'boolean',
            'es_sistema' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class, 'tipo', 'clave');
    }
}
