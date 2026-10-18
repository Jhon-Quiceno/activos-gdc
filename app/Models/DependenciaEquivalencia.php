<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Variante de texto de una dependencia (como llega en un Excel) y la
 * dependencia oficial a la que corresponde (RF-39).
 */
class DependenciaEquivalencia extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'dependencia_id',
        'texto',
        'origen',
    ];

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class);
    }

    /**
     * Normaliza un texto para compararlo: minúsculas, sin tildes y con los
     * espacios repetidos colapsados. «DIRECIÓN  de Sistemas» → «direcion de sistemas».
     */
    public static function normalizar(string $texto): string
    {
        $sinTildes = strtr(mb_strtolower(trim($texto)), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return preg_replace('/\s+/', ' ', $sinTildes);
    }

    /**
     * Dependencia oficial para un texto: primero por nombre oficial o corto,
     * después por equivalencia. Null si no hay coincidencia exacta: nunca se
     * adivina por similitud.
     */
    public static function resolver(string $texto): ?Dependencia
    {
        $buscado = self::normalizar($texto);

        if ($buscado === '') {
            return null;
        }

        $oficial = Dependencia::query()->get(['id', 'nombre', 'nombre_corto'])
            ->first(fn (Dependencia $d) => self::normalizar($d->nombre) === $buscado
                || ($d->nombre_corto && self::normalizar($d->nombre_corto) === $buscado));

        if ($oficial) {
            return Dependencia::find($oficial->id);
        }

        $equivalencia = self::query()->get(['dependencia_id', 'texto'])
            ->first(fn (self $e) => self::normalizar($e->texto) === $buscado);

        return $equivalencia ? Dependencia::find($equivalencia->dependencia_id) : null;
    }
}
