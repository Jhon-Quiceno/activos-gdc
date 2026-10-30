<?php

namespace App\Livewire\Admin;

use App\Models\TipoEvento;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Tipos de evento" (Manuel, bloque de Administración): RF-13.
 *
 * Los 8 tipos del sistema (`es_sistema = true`) se muestran pero no se editan ni
 * se desactivan desde aquí: tienen efectos propios (traslado, baja, valoración,
 * cambio de componente...) que esta pantalla no toca. Un tipo configurable solo
 * registra un hecho (fecha, descripción, autor y adjuntos) vía
 * HistorialService::registrar(), que ya valida contra esta tabla (F3-3).
 *
 * Un tipo nunca se borra desde ninguna pantalla (base-de-datos.md, 10.4): se
 * desactiva, porque los eventos lo referencian por su `clave`. La llave foránea
 * de `eventos.tipo` ya lo impide a nivel de base de datos (restrictOnDelete).
 */
class TiposEventoPanel extends Component
{
    public string $nuevoNombre = '';

    public string $nuevaDescripcion = '';

    public bool $nuevoRequiereFirma = false;

    public ?int $editandoId = null;

    public string $editandoNombre = '';

    public string $editandoDescripcion = '';

    public bool $editandoRequiereFirma = false;

    public function agregar(): void
    {
        $this->validate([
            'nuevoNombre' => ['required', 'string', 'max:80', Rule::unique('tipos_evento', 'nombre')],
            'nuevaDescripcion' => ['nullable', 'string', 'max:255'],
            'nuevoRequiereFirma' => ['boolean'],
        ], [], [
            'nuevoNombre' => __('nombre'),
            'nuevaDescripcion' => __('descripción'),
        ]);

        TipoEvento::create([
            'clave' => $this->claveDisponible($this->nuevoNombre),
            'nombre' => $this->nuevoNombre,
            'descripcion' => $this->nuevaDescripcion !== '' ? $this->nuevaDescripcion : null,
            'requiere_firma' => $this->nuevoRequiereFirma,
            'es_sistema' => false,
            'activo' => true,
        ]);

        $this->reset(['nuevoNombre', 'nuevaDescripcion', 'nuevoRequiereFirma']);

        session()->flash('status', __('Tipo de evento agregado correctamente.'));
    }

    /** Clave estable (snake_case, varchar(40)) a partir del nombre, única entre los tipos. */
    private function claveDisponible(string $nombre): string
    {
        $base = substr(Str::slug($nombre, '_'), 0, 36) ?: 'tipo';
        $clave = $base;
        $sufijo = 2;

        while (TipoEvento::where('clave', $clave)->exists()) {
            $clave = "{$base}_{$sufijo}";
            $sufijo++;
        }

        return $clave;
    }

    public function abrirEdicion(int $id): void
    {
        $tipo = TipoEvento::findOrFail($id);

        if ($tipo->es_sistema) {
            return;
        }

        $this->editandoId = $tipo->id;
        $this->editandoNombre = $tipo->nombre;
        $this->editandoDescripcion = (string) $tipo->descripcion;
        $this->editandoRequiereFirma = $tipo->requiere_firma;
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'editar-tipo-evento');
    }

    public function guardarEdicion(): void
    {
        $tipo = TipoEvento::findOrFail($this->editandoId);

        if ($tipo->es_sistema) {
            return;
        }

        $this->validate([
            'editandoNombre' => ['required', 'string', 'max:80', Rule::unique('tipos_evento', 'nombre')->ignore($tipo->id)],
            'editandoDescripcion' => ['nullable', 'string', 'max:255'],
            'editandoRequiereFirma' => ['boolean'],
        ], [], [
            'editandoNombre' => __('nombre'),
            'editandoDescripcion' => __('descripción'),
        ]);

        $tipo->update([
            'nombre' => $this->editandoNombre,
            'descripcion' => $this->editandoDescripcion !== '' ? $this->editandoDescripcion : null,
            'requiere_firma' => $this->editandoRequiereFirma,
        ]);

        $this->dispatch('close-modal', 'editar-tipo-evento');

        session()->flash('status', __('Tipo de evento actualizado correctamente.'));
    }

    public function desactivar(int $id): void
    {
        $tipo = TipoEvento::findOrFail($id);

        if ($tipo->es_sistema) {
            return;
        }

        $tipo->update(['activo' => ! $tipo->activo]);

        session()->flash('status', $tipo->activo ? __('Tipo de evento reactivado.') : __('Tipo de evento desactivado.'));
    }

    public function render()
    {
        $tipos = TipoEvento::query()
            ->orderByDesc('es_sistema')
            ->orderBy('nombre')
            ->get()
            ->map(fn (TipoEvento $tipo) => [
                'tipo' => $tipo,
                'eventos' => $tipo->eventos()->count(),
            ]);

        return view('livewire.admin.tipos-evento-panel', ['filas' => $tipos]);
    }
}
