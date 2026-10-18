<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Empleo de la nomenclatura oficial (Decreto 607 de 2023). El primer dígito
 * del código indica el nivel (art. 16). Contratistas y pasantes no tienen código.
 */
class Cargo extends Model
{
    public const NIVELES = [
        'directivo' => 'Directivo',
        'asesor' => 'Asesor',
        'profesional' => 'Profesional',
        'tecnico' => 'Técnico',
        'asistencial' => 'Asistencial',
    ];

    protected $fillable = [
        'codigo',
        'denominacion',
        'nivel',
    ];

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }

    /**
     * «219 · Profesional Universitario» o solo la denominación si no tiene código.
     */
    public function etiqueta(): string
    {
        return $this->codigo ? "{$this->codigo} · {$this->denominacion}" : $this->denominacion;
    }
}
