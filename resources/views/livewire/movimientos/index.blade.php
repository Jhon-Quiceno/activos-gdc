<div class="space-y-6">
    <x-ui.card class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-info-bg text-info-text">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 3m0 0l-4.5 4.5M21 3H7.5" />
            </svg>
        </div>

        <p class="mx-auto mt-4 max-w-md text-sm text-ink-muted">
            {{ __('Aquí vas a poder registrar y consultar los traslados, cambios de responsable y demás movimientos de los equipos.') }}
        </p>

        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-neutral-bg px-3 py-1 text-xs font-semibold text-neutral-text">
            {{ __('Próximamente') }}
        </span>
    </x-ui.card>

    {{-- Vista fantasma de la forma futura del historial de movimientos --}}
    <x-ui.card :padding="false" class="opacity-50" aria-hidden="true">
        <div class="divide-y divide-line">
            @for ($i = 0; $i < 4; $i++)
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-line">
                        <div class="h-4 w-4 rounded-sm bg-white/60"></div>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 w-2/5 rounded bg-line"></div>
                        <div class="h-2.5 w-1/4 rounded bg-line"></div>
                    </div>
                    <div class="h-2.5 w-16 shrink-0 rounded bg-line"></div>
                </div>
            @endfor
        </div>
    </x-ui.card>
</div>
