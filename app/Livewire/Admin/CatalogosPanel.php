<?php

namespace App\Livewire\Admin;

use App\Models\Cargo;
use App\Models\Marca;
use App\Models\Municipio;
use App\Models\ModeloEquipo;
use App\Models\Piso;
use App\Models\Proceso;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\TipoEquipo;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Catálogos relacionales de v4 que no caben en el panel de Listas simple
 * (Manuel, Administración): sedes con sus pisos, municipios, procesos, cargos,
 * proveedores y modelos. Un control segmentado cambia entre ellos.
 *
 * Las sedes y los procesos se desactivan en vez de borrarse (tienen columna de
 * estado); el resto se puede crear y editar.
 */
class CatalogosPanel extends Component
{
    public string $segmento = 'sedes';

    /** Campos del formulario de alta/edición del catálogo activo. */
    public array $form = [];

    public ?int $editandoId = null;

    /** Alta de piso dentro de una sede. */
    public ?int $pisoSedeId = null;

    public string $pisoNumero = '';

    public string $pisoNombre = '';

    public const SEGMENTOS = [
        'sedes' => 'Sedes y pisos',
        'municipios' => 'Municipios',
        'procesos' => 'Procesos',
        'cargos' => 'Cargos',
        'proveedores' => 'Proveedores',
        'modelos' => 'Modelos',
    ];

    public function seleccionar(string $segmento): void
    {
        if (! array_key_exists($segmento, self::SEGMENTOS)) {
            return;
        }

        $this->segmento = $segmento;
        $this->reset(['form', 'editandoId', 'pisoSedeId', 'pisoNumero', 'pisoNombre']);
        $this->resetErrorBag();
    }

    /** @return array{model: class-string, reglas: array, nombre: string} */
    private function config(): array
    {
        return match ($this->segmento) {
            'sedes' => [
                'model' => Sede::class,
                'nombre' => 'Sede',
                'reglas' => [
                    'form.nombre' => ['required', 'string', 'max:160'],
                    'form.municipio_id' => ['required', 'integer', Rule::exists('municipios', 'id')],
                    'form.tipo' => ['required', Rule::in(array_keys(Sede::TIPOS))],
                    'form.tenencia' => ['required', Rule::in(array_keys(Sede::TENENCIAS))],
                    'form.direccion' => ['nullable', 'string', 'max:160'],
                    'form.direccion_reservada' => ['boolean'],
                ],
            ],
            'municipios' => [
                'model' => Municipio::class,
                'nombre' => 'Municipio',
                'reglas' => [
                    'form.codigo_dane' => ['required', 'string', 'max:10', Rule::unique('municipios', 'codigo_dane')->ignore($this->editandoId)],
                    'form.nombre' => ['required', 'string', 'max:120'],
                ],
            ],
            'procesos' => [
                'model' => Proceso::class,
                'nombre' => 'Proceso',
                'reglas' => [
                    'form.nombre' => ['required', 'string', 'max:160', Rule::unique('procesos', 'nombre')->ignore($this->editandoId)],
                    'form.tipo' => ['required', Rule::in(array_keys(Proceso::TIPOS))],
                ],
            ],
            'cargos' => [
                'model' => Cargo::class,
                'nombre' => 'Cargo',
                'reglas' => [
                    'form.codigo' => ['nullable', 'string', 'max:10'],
                    'form.denominacion' => ['required', 'string', 'max:160'],
                    'form.nivel' => ['required', Rule::in(array_keys(Cargo::NIVELES))],
                ],
            ],
            'proveedores' => [
                'model' => Proveedor::class,
                'nombre' => 'Proveedor',
                'reglas' => [
                    'form.nit' => ['nullable', 'string', 'max:20'],
                    'form.razon_social' => ['required', 'string', 'max:160'],
                    'form.telefono' => ['nullable', 'string', 'max:40'],
                    'form.correo' => ['nullable', 'email', 'max:120'],
                ],
            ],
            'modelos' => [
                'model' => ModeloEquipo::class,
                'nombre' => 'Modelo',
                'reglas' => [
                    'form.marca_id' => ['required', 'integer', Rule::exists('marcas', 'id')],
                    'form.tipo_equipo_id' => ['required', 'integer', Rule::exists('tipos_equipo', 'id')],
                    'form.nombre' => ['required', 'string', 'max:100'],
                ],
            ],
        };
    }

    public function guardar(): void
    {
        $config = $this->config();
        $datos = $this->validate($config['reglas'])['form'];

        if ($this->segmento === 'modelos' && ! $this->editandoId) {
            // RN del catálogo: el modelo es único por marca y tipo.
            $existe = ModeloEquipo::where($datos)->exists();
            if ($existe) {
                $this->addError('form.nombre', __('Ese modelo ya existe para esa marca y tipo.'));

                return;
            }
        }

        if ($this->editandoId) {
            $config['model']::findOrFail($this->editandoId)->update($datos);
        } else {
            $config['model']::create($datos);
        }

        $this->reset(['form', 'editandoId']);
        session()->flash('status', __(':nombre guardado correctamente.', ['nombre' => $config['nombre']]));
    }

    public function abrirEdicion(int $id): void
    {
        $config = $this->config();
        $registro = $config['model']::findOrFail($id);

        $this->editandoId = $id;
        $this->form = collect(array_keys($config['reglas']))
            ->mapWithKeys(fn ($clave) => [str_replace('form.', '', $clave) => $registro->{str_replace('form.', '', $clave)}])
            ->all();
        $this->resetErrorBag();
    }

    public function cancelarEdicion(): void
    {
        $this->reset(['form', 'editandoId']);
        $this->resetErrorBag();
    }

    public function alternarActiva(int $id): void
    {
        $campo = $this->segmento === 'procesos' ? 'activo' : 'activa';
        $registro = $this->config()['model']::findOrFail($id);
        $registro->update([$campo => ! $registro->{$campo}]);
        session()->flash('status', __('Estado actualizado.'));
    }

    public function agregarPiso(int $sedeId): void
    {
        $this->pisoSedeId = $sedeId;
        $this->validate([
            'pisoNumero' => ['required', 'integer', Rule::unique('pisos', 'numero')->where('sede_id', $sedeId)],
            'pisoNombre' => ['nullable', 'string', 'max:60'],
        ]);

        Piso::create(['sede_id' => $sedeId, 'numero' => $this->pisoNumero, 'nombre' => $this->pisoNombre ?: null]);

        $this->reset(['pisoNumero', 'pisoNombre']);
        session()->flash('status', __('Piso agregado.'));
    }

    public function render()
    {
        $datos = match ($this->segmento) {
            'sedes' => Sede::with('pisos', 'municipio')->orderBy('nombre')->get(),
            'municipios' => Municipio::orderBy('nombre')->get(),
            'procesos' => Proceso::orderBy('nombre')->get(),
            'cargos' => Cargo::orderBy('denominacion')->get(),
            'proveedores' => Proveedor::orderBy('razon_social')->get(),
            'modelos' => ModeloEquipo::with('marca', 'tipoEquipo')->orderBy('nombre')->get(),
        };

        return view('livewire.admin.catalogos-panel', [
            'segmentos' => self::SEGMENTOS,
            'datos' => $datos,
            'municipios' => Municipio::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'tiposEquipo' => TipoEquipo::orderBy('nombre')->get(),
            'tiposSede' => Sede::TIPOS,
            'tenencias' => Sede::TENENCIAS,
            'tiposProceso' => Proceso::TIPOS,
            'nivelesCargo' => Cargo::NIVELES,
        ]);
    }
}
