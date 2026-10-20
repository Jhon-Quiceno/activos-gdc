<?php

namespace App\Livewire\Equipos\Concerns;

use App\Livewire\Equipos\Soporte\TextosEquipo;
use App\Models\Dependencia;
use App\Models\ModeloEquipo;
use App\Models\Proceso;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Datos del equipo que en v4 salen de catálogos (docs/base-de-datos.md §4.4):
 * modelo (marca + tipo), propietario tercero (proveedores, RN-16), descripción
 * y proceso. Lo comparten el registro y la edición.
 *
 * Si el modelo o el proveedor no está en el catálogo, se puede escribir uno
 * nuevo: se crea al guardar (con firstOrCreate, así no se duplica).
 *
 * El componente que lo use debe tener `$tipoEquipoId`, `$marcaId` y `$propiedad`.
 */
trait CatalogoDelEquipo
{
    public ?int $modeloId = null;

    public bool $agregarModelo = false;

    public string $modeloNuevo = '';

    public string $descripcion = '';

    /** Null = el proceso de la dependencia (se resuelve al guardar). */
    public ?int $procesoId = null;

    public ?int $proveedorTerceroId = null;

    public bool $agregarProveedor = false;

    public string $proveedorNuevo = '';

    public string $proveedorNuevoNit = '';

    /**
     * Al cambiar la marca, el modelo elegido deja de servir.
     */
    public function updatedMarcaId(): void
    {
        $this->modeloId = null;
    }

    public function updatedAgregarModelo(): void
    {
        $this->agregarModelo ? $this->modeloId = null : $this->modeloNuevo = '';
        $this->resetValidation(['modeloId', 'modeloNuevo']);
    }

    public function updatedAgregarProveedor(): void
    {
        if ($this->agregarProveedor) {
            $this->proveedorTerceroId = null;
        } else {
            $this->proveedorNuevo = '';
            $this->proveedorNuevoNit = '';
        }

        $this->resetValidation(['proveedorTerceroId', 'proveedorNuevo', 'proveedorNuevoNit']);
    }

    /**
     * Modelos del catálogo para la marca y el tipo elegidos.
     *
     * @return Collection<int, ModeloEquipo>
     */
    public function modelosDisponibles(): Collection
    {
        if (! $this->marcaId || ! $this->tipoEquipoId) {
            return collect();
        }

        return ModeloEquipo::query()
            ->where('marca_id', $this->marcaId)
            ->where('tipo_equipo_id', $this->tipoEquipoId)
            ->orderBy('nombre')
            ->get();
    }

    protected function reglasCatalogo(): array
    {
        $reglas = [
            'modeloId' => [
                'nullable',
                Rule::exists('modelos', 'id')
                    ->where('marca_id', $this->marcaId)
                    ->where('tipo_equipo_id', $this->tipoEquipoId),
            ],
            'modeloNuevo' => [$this->agregarModelo ? 'required' : 'nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'procesoId' => ['nullable', 'exists:procesos,id'],
        ];

        if ($this->propiedad === 'tercero') {
            // RN-16: el dueño de un equipo de tercero sale del catálogo de proveedores.
            $reglas['proveedorTerceroId'] = [$this->agregarProveedor ? 'nullable' : 'required', 'exists:proveedores,id'];
            $reglas['proveedorNuevo'] = [$this->agregarProveedor ? 'required' : 'nullable', 'string', 'max:160'];
            $reglas['proveedorNuevoNit'] = ['nullable', 'string', 'max:20'];
            $reglas['figura'] = ['required', Rule::in(array_keys(TextosEquipo::FIGURAS_TERCERO))];
        }

        return $reglas;
    }

    protected function mensajesCatalogo(): array
    {
        return [
            'modeloId.exists' => __('Ese modelo no es de la marca y el tipo elegidos.'),
            'proveedorTerceroId.required' => __('Elige el propietario o agrega uno nuevo.'),
        ];
    }

    protected function atributosCatalogo(): array
    {
        return [
            'modeloId' => __('modelo'),
            'modeloNuevo' => __('modelo nuevo'),
            'descripcion' => __('descripción'),
            'procesoId' => __('proceso'),
            'proveedorTerceroId' => __('propietario'),
            'proveedorNuevo' => __('propietario nuevo'),
            'proveedorNuevoNit' => __('NIT'),
            'figura' => __('figura'),
        ];
    }

    /**
     * Id del modelo a guardar; crea el modelo nuevo si se escribió uno.
     * Llamar dentro de la transacción del guardado.
     */
    protected function resolverModeloId(): ?int
    {
        if (! $this->agregarModelo) {
            return $this->modeloId;
        }

        return ModeloEquipo::firstOrCreate([
            'marca_id' => $this->marcaId,
            'tipo_equipo_id' => $this->tipoEquipoId,
            'nombre' => trim($this->modeloNuevo),
        ])->id;
    }

    /**
     * Id del propietario tercero; crea el proveedor nuevo si se escribió uno.
     * Llamar dentro de la transacción del guardado.
     */
    protected function resolverProveedorTerceroId(): ?int
    {
        if ($this->propiedad !== 'tercero') {
            return null;
        }

        if (! $this->agregarProveedor) {
            return $this->proveedorTerceroId;
        }

        $nit = trim($this->proveedorNuevoNit);
        $razonSocial = trim($this->proveedorNuevo);

        // El NIT es único: si ya existe, se usa ese proveedor en vez de duplicarlo.
        return ($nit !== ''
            ? Proveedor::firstOrCreate(['nit' => $nit], ['razon_social' => $razonSocial])
            : Proveedor::firstOrCreate(['razon_social' => $razonSocial, 'nit' => null])
        )->id;
    }

    /**
     * Proceso a guardar: el elegido o, por defecto, el de la dependencia
     * (docs/base-de-datos.md §4.4).
     */
    protected function procesoParaGuardar(?int $dependenciaId): ?int
    {
        return $this->procesoId
            ?? ($dependenciaId ? Dependencia::query()->whereKey($dependenciaId)->value('proceso_id') : null);
    }

    /** Nombre del modelo elegido o escrito, para el resumen y el historial. */
    protected function nombreModeloDelFormulario(): ?string
    {
        $nombre = $this->agregarModelo
            ? trim($this->modeloNuevo)
            : ModeloEquipo::query()->whereKey($this->modeloId)->value('nombre');

        return filled($nombre) ? $nombre : null;
    }

    /** Razón social del propietario elegido o escrito, para el historial. */
    protected function nombreProveedorDelFormulario(): ?string
    {
        if ($this->propiedad !== 'tercero') {
            return null;
        }

        $nombre = $this->agregarProveedor
            ? trim($this->proveedorNuevo)
            : Proveedor::query()->whereKey($this->proveedorTerceroId)->value('razon_social');

        return filled($nombre) ? $nombre : null;
    }

    /** Datos para los selects de las vistas. */
    protected function datosCatalogo(): array
    {
        return [
            'modelosDisponibles' => $this->modelosDisponibles(),
            'proveedores' => Proveedor::orderBy('razon_social')->get(),
            'procesos' => Proceso::where('activo', true)->orderBy('nombre')->get(),
            'figurasTercero' => TextosEquipo::FIGURAS_TERCERO,
        ];
    }
}
