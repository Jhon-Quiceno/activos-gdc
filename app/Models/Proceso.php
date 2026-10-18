<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proceso del Sistema Integrado de Gestión (formato FOR.01, hoja «Datos»).
 */
class Proceso extends Model
{
    public const TIPOS = [
        'estrategico' => 'Estratégico',
        'misional' => 'Misional',
        'apoyo' => 'Apoyo',
        'evaluacion_control' => 'Evaluación y control',
    ];

    protected $fillable = [
        'nombre',
        'tipo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(Dependencia::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
