<?php

namespace App\Livewire\Admin;

use App\Models\Dependencia;
use App\Models\DependenciaEquivalencia;
use App\Models\Proceso;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Árbol de dependencias (Manuel, Administración): ver con buscador, crear,
 * renombrar, mover y desactivar (RF-45, RF-60). Nunca se borra una dependencia
 * (RN-20): solo se desactiva, para no romper su historial. El propio modelo
 * bloquea los ciclos al mover (`Dependencia::save()` lanza ValidationException).
 *
 * Incluye el mantenimiento de equivalencias (RF-39): las variantes de texto con
 * que una dependencia llega en un Excel, para poder agregarlas y reasignarlas.
 */
class DependenciasPanel extends Component
{
    public string $busqueda = '';

    public bool $verInactivas = false;

    // Formulario de crear / editar.
    public ?int $editandoId = null;

    public string $nombre = '';

    public string $nombreCorto = '';

    public string $sigla = '';

    public string $tipo = 'area';

    public ?int $padreId = null;

    public ?int $procesoId = null;

    // Panel de equivalencias de una dependencia.
    public ?int $equivalenciasDe = null;

    public string $nuevaEquivalencia = '';

    public function nuevo(): void
    {
        $this->reset(['editandoId', 'nombre', 'nombreCorto', 'sigla', 'padreId', 'procesoId']);
        $this->tipo = 'area';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'dependencia-form');
    }

    public function abrirEdicion(int $id): void
    {
        $dependencia = Dependencia::findOrFail($id);

        $this->editandoId = $dependencia->id;
        $this->nombre = $dependencia->nombre;
        $this->nombreCorto = (string) $dependencia->nombre_corto;
        $this->sigla = (string) $dependencia->sigla;
        $this->tipo = $dependencia->tipo;
        $this->padreId = $dependencia->padre_id;
        $this->procesoId = $dependencia->proceso_id;
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'dependencia-form');
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => ['required', 'string', 'max:160'],
            'nombreCorto' => ['nullable', 'string', 'max:60'],
            'sigla' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', Rule::in(array_keys(Dependencia::TIPOS))],
            'padreId' => ['nullable', 'integer', Rule::exists('dependencias', 'id')],
            'procesoId' => ['nullable', 'integer', Rule::exists('procesos', 'id')],
        ]);

        $atributos = [
            'nombre' => $datos['nombre'],
            'nombre_corto' => $datos['nombreCorto'] ?: null,
            'sigla' => $datos['sigla'] ?: null,
            'tipo' => $datos['tipo'],
            'padre_id' => $datos['padreId'],
            'proceso_id' => $datos['procesoId'],
        ];

        try {
            if ($this->editandoId) {
                Dependencia::findOrFail($this->editandoId)->update($atributos);
            } else {
                Dependencia::create($atributos + ['activa' => true]);
            }
        } catch (ValidationException $e) {
            // RN-20: mover una dependencia dentro de una de sus hijas.
            $this->addError('padreId', $e->validator->errors()->first());

            return;
        }

        $this->dispatch('close-modal', 'dependencia-form');
        session()->flash('status', __('Dependencia guardada correctamente.'));
    }

    /**
     * Desactivar o reactivar. Nunca se borra (RN-20): el historial de equipos y
     * personas que la referencian no se pierde.
     */
    public function alternarActiva(int $id): void
    {
        $dependencia = Dependencia::findOrFail($id);
        $dependencia->update(['activa' => ! $dependencia->activa]);

        session()->flash('status', $dependencia->activa
            ? __('Dependencia reactivada.')
            : __('Dependencia desactivada (no se borra, para conservar el historial).'));
    }

    public function abrirEquivalencias(int $id): void
    {
        $this->equivalenciasDe = $id;
        $this->nuevaEquivalencia = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'dependencia-equivalencias');
    }

    public function agregarEquivalencia(): void
    {
        $this->validate([
            'nuevaEquivalencia' => ['required', 'string', 'max:200', Rule::unique('dependencia_equivalencias', 'texto')],
        ], attributes: ['nuevaEquivalencia' => __('variante')]);

        DependenciaEquivalencia::create([
            'dependencia_id' => $this->equivalenciasDe,
            'texto' => trim($this->nuevaEquivalencia),
            'origen' => 'manual',
        ]);

        $this->nuevaEquivalencia = '';
        session()->flash('status', __('Variante agregada.'));
    }

    public function reasignarEquivalencia(int $equivalenciaId, int $nuevaDependenciaId): void
    {
        DependenciaEquivalencia::whereKey($equivalenciaId)->update(['dependencia_id' => $nuevaDependenciaId]);
        session()->flash('status', __('Variante reasignada.'));
    }

    public function eliminarEquivalencia(int $equivalenciaId): void
    {
        DependenciaEquivalencia::whereKey($equivalenciaId)->delete();
        session()->flash('status', __('Variante eliminada.'));
    }

    public function render()
    {
        $arbol = Dependencia::arbol($this->verInactivas ? false : true);

        if ($this->busqueda !== '') {
            $buscado = DependenciaEquivalencia::normalizar($this->busqueda);
            $arbol = $arbol->filter(fn (Dependencia $d) => str_contains(DependenciaEquivalencia::normalizar($d->nombre), $buscado)
                || ($d->sigla && str_contains(DependenciaEquivalencia::normalizar($d->sigla), $buscado)))
                ->values();
        }

        $equivalencias = $this->equivalenciasDe
            ? DependenciaEquivalencia::where('dependencia_id', $this->equivalenciasDe)->orderBy('texto')->get()
            : collect();

        return view('livewire.admin.dependencias-panel', [
            'arbol' => $arbol,
            'tipos' => Dependencia::TIPOS,
            'procesos' => Proceso::orderBy('nombre')->get(),
            'opcionesPadre' => Dependencia::arbol(false),
            'equivalencias' => $equivalencias,
            'dependenciaActiva' => $this->equivalenciasDe ? Dependencia::find($this->equivalenciasDe) : null,
        ]);
    }
}
