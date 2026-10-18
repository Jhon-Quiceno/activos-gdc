<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Levantamiento de activos de una dependencia y proceso, cerrado con el acta
 * de identificación y aceptación firmada (GT-M4 4.1 y 4.1.2).
 */
class LevantamientoActivos extends Model
{
    public const ESTADOS = [
        'en_curso' => 'En curso',
        'pendiente_de_firma' => 'Pendiente de firma',
        'aceptado' => 'Aceptado',
    ];

    protected $table = 'levantamientos_activos';

    protected $fillable = [
        'codigo',
        'dependencia_id',
        'proceso_id',
        'sede_id',
        'fecha_inicio',
        'fecha_aceptacion',
        'propietario_persona_id',
        'custodio_persona_id',
        'estado',
        'acta_path',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_aceptacion' => 'date',
        ];
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'propietario_persona_id');
    }

    public function custodio(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'custodio_persona_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function valoraciones(): HasMany
    {
        return $this->hasMany(ValoracionActivo::class, 'levantamiento_id');
    }
}
