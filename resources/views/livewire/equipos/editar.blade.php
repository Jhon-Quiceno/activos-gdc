{{--
    Página de la ruta `equipos.editar` (Route::view, recibe el id en $equipo).
    Solo arma el layout; el formulario es el componente Livewire
    App\Livewire\Equipos\Editar (vista page-editar.blade.php).
--}}
@php
    $registro = \App\Models\Equipo::findOrFail($equipo);
@endphp

<x-layouts.app-shell :title="__('Editar equipo')">
    <div class="space-y-6">
        <p class="text-[13px] text-ink-muted">
            <a href="{{ route('equipos.index') }}" wire:navigate class="hover:text-ink">{{ __('Equipos') }}</a>
            / <a href="{{ route('equipos.show', $registro) }}" wire:navigate class="hover:text-ink">{{ $registro->serial }}</a>
            / {{ __('Editar') }}
        </p>

        <x-ui.page-header
            :title="__('Editar equipo')"
            :subtitle="__('Cada cambio queda en el historial con el valor anterior y el nuevo. Los campos con * son obligatorios.')"
        />

        <livewire:equipos.editar :equipo="$registro" />
    </div>
</x-layouts.app-shell>
