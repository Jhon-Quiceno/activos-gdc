<div class="space-y-6">
    <x-ui.card class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 15.375c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 13.5h.008v.008H13.5V13.5zM13.5 19.5h.008v.008H13.5V19.5zM19.5 13.5h.008v.008H19.5V13.5zM19.5 19.5h.008v.008H19.5V19.5zM16.5 16.5h.008v.008h-.008V16.5z" />
            </svg>
        </div>

        <p class="mx-auto mt-4 max-w-md text-[14px] text-ink-muted">
            {{ __('Aquí vas a poder generar e imprimir las etiquetas QR de cada equipo.') }}
        </p>

        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-neutral-bg px-3 py-1 text-[13px] font-semibold text-neutral-text">
            {{ __('Próximamente') }}
        </span>
    </x-ui.card>

    {{-- Vista fantasma de la futura grilla de etiquetas QR --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 nav:grid-cols-6" aria-hidden="true">
        @for ($i = 0; $i < 6; $i++)
            <x-ui.card class="flex flex-col items-center gap-2 opacity-50">
                <div class="h-16 w-16 rounded bg-line"></div>
                <div class="h-2.5 w-14 rounded bg-line"></div>
            </x-ui.card>
        @endfor
    </div>
</div>
