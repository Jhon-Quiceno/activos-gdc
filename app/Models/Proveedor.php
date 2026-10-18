<?php

namespace App\Models;

use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quien vende, dona o presta equipos; también el dueño de un equipo de tercero.
 */
class Proveedor extends Model
{
    /** @use HasFactory<ProveedorFactory> */
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nit',
        'razon_social',
        'telefono',
        'correo',
    ];

    /** Equipos comprados a este proveedor. */
    public function equiposVendidos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'proveedor_id');
    }

    /** Equipos de tercero de los que este proveedor es dueño. */
    public function equiposPropios(): HasMany
    {
        return $this->hasMany(Equipo::class, 'proveedor_tercero_id');
    }
}
