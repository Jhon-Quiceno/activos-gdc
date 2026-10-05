<div class="space-y-6">
    <x-ui.card class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success-bg text-success-text">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 7.5L12 3m0 0L7.5 7.5M12 3v13.5" />
            </svg>
        </div>

        <p class="mx-auto mt-4 max-w-md text-sm text-ink-muted">
            {{ __('Aquí vas a poder cargar masivamente equipos desde Excel/CSV y revisar el resultado de cada importación.') }}
        </p>

        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-neutral-bg px-3 py-1 text-xs font-semibold text-neutral-text">
            {{ __('Próximamente') }}
        </span>
    </x-ui.card>

    {{-- Vista fantasma de la futura zona de carga + historial de archivos --}}
    <div class="grid grid-cols-1 gap-4 nav:grid-cols-3">
        <div class="flex h-40 items-center justify-center rounded-lg border-2 border-dashed border-line-input opacity-50 nav:col-span-1" aria-hidden="true">
            <div class="text-center">
                <div class="mx-auto h-8 w-8 rounded bg-line"></div>
                <div class="mx-auto mt-3 h-2.5 w-24 rounded bg-line"></div>
            </div>
        </div>

        <x-ui.card :padding="false" class="opacity-50 nav:col-span-2" aria-hidden="true">
            <div class="divide-y divide-line">
                @for ($i = 0; $i < 3; $i++)
                    <div class="flex items-center gap-4 px-5 py-4">
                        <div class="flex-1 space-y-2">
                            <div class="h-3 w-1/3 rounded bg-line"></div>
                            <div class="h-1.5 w-full rounded-full bg-line"></div>
                        </div>
                        <div class="h-6 w-20 shrink-0 rounded-full bg-line"></div>
                    </div>
                @endfor
            </div>
        </x-ui.card>
    </div>
</div>
