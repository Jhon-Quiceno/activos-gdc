{{--
    Página de la ruta `equipos.crear` (Route::view, sin parámetros).

    Route::view() solo renderiza esta vista tal cual, sin instanciar ningún
    componente Livewire: por eso esta vista es apenas el layout + la etiqueta
    <livewire:equipos.crear />, igual que livewire/equipos/page.blade.php hace
    para el listado. La vista propia del componente Livewire "Crear" (con todo
    el formulario) vive en resources/views/livewire/equipos/page-crear.blade.php
    y se referencia explícitamente desde su render(), para no chocar con este
    mismo nombre de archivo.
--}}
<x-layouts.app-shell :title="__('Registrar equipo')">
    <div class="space-y-6">
        <p class="text-[13px] text-ink-muted">
            <a href="{{ route('equipos.index') }}" wire:navigate class="hover:text-ink">{{ __('Equipos') }}</a>
            / {{ __('Registrar equipo') }}
        </p>

        <x-ui.page-header
            :title="__('Registrar equipo')"
            :subtitle="__('El formulario cambia según el tipo de equipo. Los campos con * son obligatorios.')"
        />

        <livewire:equipos.crear />
    </div>
</x-layouts.app-shell>
