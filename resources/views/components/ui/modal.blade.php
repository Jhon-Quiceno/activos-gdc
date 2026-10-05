{{--
    Modal centrado con overlay oscuro semitransparente.
    Envuelve el <x-modal> de Breeze (ya trae focus-trap, escape y eventos
    open-modal/close-modal) y solo aplica la cabecera/cuerpo/footer institucional.

    Uso:
    <x-ui.modal name="confirmar-baja" title="Dar de baja equipo">
        Contenido del modal...

        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'confirmar-baja')">Cancelar</x-ui.button>
            <x-ui.button variant="danger">Confirmar</x-ui.button>
        </x-slot>
    </x-ui.modal>
--}}
@props(['name', 'maxWidth' => '2xl', 'title' => null])

<x-modal :name="$name" :max-width="$maxWidth" {{ $attributes }}>
    <div class="p-6">
        @if ($title)
            <h3 class="mb-4 font-display text-lg font-semibold text-ink">{{ $title }}</h3>
        @endif

        <div class="text-sm text-ink">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 flex justify-end gap-3 border-t border-line pt-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</x-modal>
