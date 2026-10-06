<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Bitácora técnica de auditoría (distinta de la hoja de vida de Evento). Solo tiene
 * created_at (sin updated_at) y es inmutable: no se permite update() ni delete().
 */
class Auditoria extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'accion',
        'entidad',
        'entidad_id',
        'valor_anterior',
        'valor_nuevo',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'array',
            'valor_nuevo' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Auditoria $auditoria): void {
            throw new LogicException('Las auditorías son inmutables: no se pueden actualizar.');
        });

        static::deleting(function (Auditoria $auditoria): void {
            throw new LogicException('Las auditorías son inmutables: no se pueden eliminar.');
        });
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Las auditorías son inmutables: no se pueden actualizar.');
    }

    public function delete(): ?bool
    {
        throw new LogicException('Las auditorías son inmutables: no se pueden eliminar.');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
