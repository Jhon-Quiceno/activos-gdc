<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipo extends Model
{
    protected $table = 'tipos_equipo';

    protected $fillable = [
        'nombre',
        'familia',
        'tipo_activo',
        'usa_configuracion_computo',
        'campos_aplicables',
        'vida_util_meses',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'campos_aplicables' => 'array',
            'usa_configuracion_computo' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function modelos(): HasMany
    {
        return $this->hasMany(ModeloEquipo::class);
    }

    /** Hardware o soporte (GT-M4 tabla 2); los de soporte no se valoran (RN-25). */
    public function esSoporte(): bool
    {
        return $this->tipo_activo === 'soporte';
    }
}
