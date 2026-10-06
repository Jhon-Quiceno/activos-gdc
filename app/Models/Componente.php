<?php

namespace App\Models;

use Database\Factories\ComponenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Componente extends Model
{
    /** @use HasFactory<ComponenteFactory> */
    use HasFactory;

    protected $fillable = [
        'equipo_id',
        'tipo_componente_id',
        'capacidad_caracteristica',
        'marca',
        'serial',
        'estado',
        'fecha_instalacion',
        'fecha_retiro',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_retiro' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipoComponente(): BelongsTo
    {
        return $this->belongsTo(TipoComponente::class);
    }

    public function cambiosComponenteComoRetirado(): HasMany
    {
        return $this->hasMany(CambioComponente::class, 'componente_retirado_id');
    }

    public function cambiosComponenteComoInstalado(): HasMany
    {
        return $this->hasMany(CambioComponente::class, 'componente_instalado_id');
    }
}
