<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Unidad de la estructura del Decreto 609 de 2023, organizada en árbol:
 * Despacho del Gobernador → Secretaría / Oficina / DAP → Dirección → Área.
 *
 * RN-20: los nombres son los oficiales, el árbol no tiene ciclos y una
 * dependencia nunca se borra (se desactiva con `activa = false`).
 */
class Dependencia extends Model
{
    public const TIPOS = [
        'despacho' => 'Despacho',
        'secretaria' => 'Secretaría',
        'departamento_administrativo' => 'Departamento administrativo',
        'oficina' => 'Oficina',
        'direccion' => 'Dirección',
        'area' => 'Área',
    ];

    protected $fillable = [
        'padre_id',
        'tipo',
        'nombre',
        'nombre_corto',
        'sigla',
        'proceso_id',
        'planta',
        'acto_administrativo',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Dependencia $dependencia): void {
            if ($dependencia->isDirty('padre_id')) {
                $dependencia->validarSinCiclo();
            }
        });
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /**
     * Ids de esta dependencia y de todas las que cuelgan de ella, a cualquier
     * profundidad. Para filtrar «todo lo de la Secretaría de Hacienda» (RF-60):
     * `->whereIn('dependencia_id', Dependencia::idsDeRama($id))`.
     *
     * @return array<int, int>
     */
    public static function idsDeRama(int $dependenciaId): array
    {
        $hijosPorPadre = static::query()->get(['id', 'padre_id'])->groupBy('padre_id');

        $ids = [];
        $pendientes = [$dependenciaId];

        while ($pendientes !== []) {
            $id = array_pop($pendientes);
            $ids[] = $id;

            foreach ($hijosPorPadre->get($id, collect()) as $hijo) {
                $pendientes[] = $hijo->id;
            }
        }

        return $ids;
    }

    /**
     * Todas las dependencias en orden de árbol (cada una seguida de sus hijas,
     * alfabéticas), con el atributo `profundidad` (0 = Despacho). Es lo que usa
     * `x-ui.dependencia-select` para mostrar la sangría.
     *
     * @return Collection<int, Dependencia>
     */
    public static function arbol(bool $soloActivas = true): Collection
    {
        $todas = static::query()
            ->when($soloActivas, fn (Builder $q) => $q->activas())
            ->orderBy('nombre')
            ->get();

        $hijosPorPadre = $todas->groupBy(fn (Dependencia $d) => $d->padre_id ?? 0);
        $idsPresentes = $todas->pluck('id')->flip();
        $resultado = collect();

        $recorrer = function ($padreId, int $profundidad) use (&$recorrer, $hijosPorPadre, $resultado): void {
            foreach ($hijosPorPadre->get($padreId, collect()) as $dependencia) {
                $dependencia->profundidad = $profundidad;
                $resultado->push($dependencia);
                $recorrer($dependencia->id, $profundidad + 1);
            }
        };

        // Raíces: sin padre, o cuyo padre quedó fuera (por ejemplo, inactivo).
        $raices = $todas->filter(fn (Dependencia $d) => $d->padre_id === null || ! $idsPresentes->has($d->padre_id));

        foreach ($raices as $raiz) {
            $raiz->profundidad = 0;
            $resultado->push($raiz);
            $recorrer($raiz->id, 1);
        }

        return $resultado;
    }

    /**
     * «Secretaría de Hacienda › Dirección de Rentas».
     */
    public function ruta(): string
    {
        $nombres = [$this->nombre_corto ?: $this->nombre];
        $actual = $this->padre;

        while ($actual && $actual->padre_id !== null) {
            array_unshift($nombres, $actual->nombre_corto ?: $actual->nombre);
            $actual = $actual->padre;
        }

        return implode(' › ', $nombres);
    }

    /**
     * @throws ValidationException
     */
    protected function validarSinCiclo(): void
    {
        $actualId = $this->padre_id;
        $visitados = [];

        while ($actualId !== null) {
            if ($this->exists && $actualId === $this->id) {
                throw ValidationException::withMessages([
                    'padre_id' => ['Una dependencia no puede quedar dentro de sí misma ni de una de sus hijas (RN-20).'],
                ]);
            }

            if (isset($visitados[$actualId])) {
                break;
            }

            $visitados[$actualId] = true;
            $actualId = static::query()->whereKey($actualId)->value('padre_id');
        }
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'padre_id')->orderBy('nombre');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class);
    }

    public function equivalencias(): HasMany
    {
        return $this->hasMany(DependenciaEquivalencia::class);
    }

    /** Pisos donde funciona según el PAMP 2026. */
    public function pisos(): BelongsToMany
    {
        return $this->belongsToMany(Piso::class, 'ubicaciones_dependencia');
    }

    public function puestosTrabajo(): HasMany
    {
        return $this->hasMany(PuestoTrabajo::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
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
