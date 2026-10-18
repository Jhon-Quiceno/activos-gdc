<?php

namespace App\Livewire\Equipos\Concerns;

use App\Models\Dependencia;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Búsqueda (RF-07) y filtros combinables de equipos. La usan el listado de
 * Equipos y el de Traslados, con la vista parcial
 * livewire/equipos/partials/filtros.blade.php.
 *
 * El componente debe usar WithPagination.
 */
trait FiltrosDeEquipos
{
    /**
     * Término libre: serial, código de activo, responsable, cédula, dependencia
     * o sede. Tolera espacios y guiones (I1 24147 = I1-24147).
     */
    #[Url(as: 'q')]
    public string $busqueda = '';

    // Los filtros van en la URL para que un listado filtrado se pueda
    // compartir o recargar tal cual.

    /** '' | 'con' | 'sin' */
    #[Url]
    public string $responsable = '';

    #[Url]
    public string $sede = '';

    #[Url]
    public string $dependencia = '';

    #[Url]
    public string $tipo = '';

    /** '' | en_servicio | sin_asignar | dado_de_baja */
    #[Url]
    public string $estado = '';

    /** '' | verificado | pendiente_de_verificar */
    #[Url]
    public string $verificacion = '';

    /** '' | sin_codigo | sin_serial */
    #[Url]
    public string $identificacion = '';

    /** '' | gobernacion | tercero */
    #[Url]
    public string $propiedad = '';

    /** '' | pendiente | al_dia: estado de los formatos firmados de sus movimientos (RF-30). */
    #[Url]
    public string $firmas = '';

    protected static array $filtrosDeEquipos = ['responsable', 'sede', 'dependencia', 'tipo', 'estado', 'verificacion', 'identificacion', 'propiedad', 'firmas'];

    /**
     * Cualquier cambio en la búsqueda o en un filtro vuelve a la primera página.
     */
    public function updatedFiltrosDeEquipos(string $propiedad): void
    {
        if ($propiedad === 'busqueda' || in_array($propiedad, self::$filtrosDeEquipos, true)) {
            $this->resetPage();
        }
    }

    public function updated(string $propiedad): void
    {
        $this->updatedFiltrosDeEquipos($propiedad);
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['busqueda', ...self::$filtrosDeEquipos]);
        $this->resetPage();
    }

    public function hayFiltros(): bool
    {
        return trim($this->busqueda) !== ''
            || collect(self::$filtrosDeEquipos)->contains(fn (string $filtro) => $this->{$filtro} !== '');
    }

    /**
     * Listas para los selects de la vista parcial de filtros.
     *
     * @return array<string, mixed>
     */
    protected function opcionesDeFiltros(): array
    {
        return [
            'sedes' => Sede::orderBy('nombre')->get(['id', 'nombre']),
            'dependencias' => Dependencia::orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => TipoEquipo::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    protected function aplicarBusqueda(Builder $query): void
    {
        $termino = trim($this->busqueda);
        $normalizado = str_replace([' ', '-'], '', $termino);

        if ($termino === '') {
            return;
        }

        $query->where(function (Builder $q) use ($termino, $normalizado) {
            // Si el término era solo espacios o guiones, $normalizado queda vacío y
            // un LIKE '%%' devolvería todos los equipos: esas columnas se omiten.
            if ($normalizado !== '') {
                $q->whereRaw("REPLACE(REPLACE(serial, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"])
                    ->orWhereRaw("REPLACE(REPLACE(codigo_activo, ' ', ''), '-', '') LIKE ?", ["%{$normalizado}%"]);
            }

            $q->orWhereHas('asignacionActual.persona', function ($p) use ($termino, $normalizado) {
                $p->where('nombre', 'like', "%{$termino}%");

                if ($normalizado !== '') {
                    $p->orWhereRaw("REPLACE(cedula, ' ', '') LIKE ?", ["%{$normalizado}%"]);
                }
            })
                ->orWhereHas('asignacionActual.dependencia', fn ($d) => $d->where('nombre', 'like', "%{$termino}%"))
                ->orWhereHas('asignacionActual.sede', fn ($s) => $s->where('nombre', 'like', "%{$termino}%"));
        });
    }

    protected function aplicarFiltros(Builder $query): void
    {
        if ($this->responsable === 'con') {
            $query->whereHas('asignacionActual', fn ($a) => $a->whereNotNull('persona_id'));
        } elseif ($this->responsable === 'sin') {
            // Sin asignación abierta o con una asignación sin persona (bodega).
            $query->whereDoesntHave('asignacionActual', fn ($a) => $a->whereNotNull('persona_id'));
        }

        if ($this->sede !== '') {
            $query->whereHas('asignacionActual', fn ($a) => $a->where('sede_id', $this->sede));
        }

        if ($this->dependencia !== '') {
            $query->whereHas('asignacionActual', fn ($a) => $a->where('dependencia_id', $this->dependencia));
        }

        if ($this->tipo !== '') {
            $query->where('tipo_equipo_id', $this->tipo);
        }

        if (in_array($this->estado, ['en_servicio', 'sin_asignar', 'dado_de_baja'], true)) {
            $query->where('estado_ciclo_vida', $this->estado);
        }

        if (in_array($this->verificacion, ['verificado', 'pendiente_de_verificar'], true)) {
            $query->where('verificacion', $this->verificacion);
        }

        if ($this->identificacion === 'sin_codigo') {
            $query->whereNull('codigo_activo');
        } elseif ($this->identificacion === 'sin_serial') {
            // La importación guarda un serial provisional «PENDIENTE-…» cuando el
            // inventario no lo trae (ver Importacion\Index::serialPendiente()).
            $query->where(fn ($q) => $q->whereNull('serial')->orWhere('serial', 'like', 'PENDIENTE-%'));
        }

        if (in_array($this->propiedad, ['gobernacion', 'tercero'], true)) {
            $query->where('propiedad', $this->propiedad);
        }

        // Un evento anulado ya no espera firmas, así que no cuenta como pendiente.
        $pendienteDeFirma = fn ($e) => $e->where('estado_firma', 'pendiente_de_firma')->whereDoesntHave('anulaciones');

        if ($this->firmas === 'pendiente') {
            $query->whereHas('eventos', $pendienteDeFirma);
        } elseif ($this->firmas === 'al_dia') {
            // Tiene movimientos con formatos y ninguno espera firmas.
            $query->whereHas('eventos', fn ($e) => $e->where('estado_firma', 'completo'))
                ->whereDoesntHave('eventos', $pendienteDeFirma);
        }
    }
}
