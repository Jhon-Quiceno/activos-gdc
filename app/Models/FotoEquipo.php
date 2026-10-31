<?php

namespace App\Models;

use Database\Factories\FotoEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Fotografía del equipo (RF-06), no de las personas. Se guarda en el mismo
 * disco privado de los documentos firmados (nunca en `public/`); subir o
 * retirar una foto deja un evento `actualizacion_datos` en el historial
 * (responsabilidad de quien registra el evento, no de este modelo).
 *
 * RN-22 (mismo patrón que la valoración vigente): como máximo una foto
 * principal por equipo. Guardar una nueva como principal degrada
 * automáticamente a la anterior.
 */
class FotoEquipo extends Model
{
    /** @use HasFactory<FotoEquipoFactory> */
    use HasFactory;

    protected $table = 'fotos_equipo';

    protected $fillable = [
        'equipo_id',
        'archivo_path',
        'descripcion',
        'tomada_en',
        'es_principal',
        'subida_por',
    ];

    protected function casts(): array
    {
        return [
            'tomada_en' => 'date',
            'es_principal' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FotoEquipo $foto): void {
            if (! $foto->es_principal) {
                return;
            }

            DB::transaction(function () use ($foto) {
                static::where('equipo_id', $foto->equipo_id)
                    ->where('es_principal', true)
                    ->when($foto->exists, fn ($query) => $query->whereKeyNot($foto->id))
                    ->update(['es_principal' => false]);
            });
        });
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function subidaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subida_por');
    }
}
