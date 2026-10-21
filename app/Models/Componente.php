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
        'modelo',
        'serial',
        'estado',
        'fecha_instalacion',
        'fecha_retiro',
    ];

    /** Estado físico del componente: la misma lista cerrada que el equipo. */
    public const ESTADOS = Equipo::ESTADOS_FUNCIONAMIENTO;

    /** Etiqueta legible del estado del componente. */
    public function estadoTexto(): ?string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

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
