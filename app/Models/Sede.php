<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Edificio donde funciona la entidad («sucursal» en el GT-M4). Cada sede
 * tiene sus propios pisos (modelo v4).
 */
class Sede extends Model
{
    public const TIPOS = [
        'administrativa' => 'Administrativa',
        'salud' => 'Salud',
        'social' => 'Social',
        'cultural' => 'Cultural',
        'institucion_educativa' => 'Institución educativa',
    ];

    public const TENENCIAS = [
        'propia' => 'Propia',
        'arrendada' => 'Arrendada',
        'comodato' => 'Comodato',
        'por_confirmar' => 'Por confirmar',
    ];

    protected $fillable = [
        'nombre',
        'direccion',
        'barrio',
        'municipio_id',
        'tipo',
        'tenencia',
        'direccion_reservada',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'direccion_reservada' => 'boolean',
            'activa' => 'boolean',
        ];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * Dirección para mostrar en pantallas, reportes, PDF y etiquetas. La de una
     * sede reservada (Casa Refugio) nunca se muestra (RN-28).
     */
    public function direccionVisible(): ?string
    {
        return $this->direccion_reservada ? null : $this->direccion;
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function pisos(): HasMany
    {
        return $this->hasMany(Piso::class)->orderBy('numero');
    }

    public function puestosTrabajo(): HasMany
    {
        return $this->hasMany(PuestoTrabajo::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function levantamientos(): HasMany
    {
        return $this->hasMany(LevantamientoActivos::class);
    }
}
