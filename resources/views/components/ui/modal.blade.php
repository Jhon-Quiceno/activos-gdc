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
            <div class="mb-4 flex items-center justify-between gap-4">
                <h3 class="section-title">{{ $title }}</h3>
                <button
                    type="button"
                    x-on:click="$dispatch('close-modal', '{{ $name }}')"
                    class="shrink-0 rounded-lg p-1 text-ink-muted transition-colors duration-150 hover:bg-app-bg hover:text-ink"
                    aria-label="{{ __('Cerrar') }}"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <div class="text-[14px] text-ink">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="mt-6 flex justify-end gap-3 border-t border-line pt-4">
                {{ $footer }}
            </div>
        @endisset
    </div>
</x-modal>
