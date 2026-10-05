<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionComputo extends Model
{
    protected $table = 'configuraciones_computo';

    protected $fillable = [
        'equipo_id',
        'sistema_operativo_id',
        'licencia',
        'tiene_antivirus',
        'antivirus_producto',
        'nombre_red',
    ];

    protected function casts(): array
    {
        return [
            'tiene_antivirus' => 'boolean',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function sistemaOperativo(): BelongsTo
    {
        return $this->belongsTo(SistemaOperativo::class);
    }
}
