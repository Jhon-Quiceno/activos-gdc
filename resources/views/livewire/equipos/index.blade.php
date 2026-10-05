<div class="space-y-6">
    <x-ui.card class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.129V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
            </svg>
        </div>

        <p class="mt-4 font-display text-xl font-semibold text-ink">{{ __('Equipos') }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-ink-muted">
            {{ __('Aquí vas a poder buscar, registrar y consultar la hoja de vida de cada equipo.') }}
        </p>

        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-neutral-bg px-3 py-1 text-xs font-semibold text-neutral-text">
            {{ __('Próximamente') }}
        </span>
    </x-ui.card>

    {{-- Vista fantasma de la forma futura del listado de equipos --}}
    <x-ui.card :padding="false" class="opacity-50" aria-hidden="true">
        <div class="divide-y divide-line">
            @for ($i = 0; $i < 4; $i++)
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="h-10 w-10 shrink-0 rounded-lg bg-line"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 w-1/3 rounded bg-line"></div>
                        <div class="h-2.5 w-1/4 rounded bg-line"></div>
                    </div>
                    <div class="h-6 w-24 shrink-0 rounded-full bg-line"></div>
                </div>
            @endfor
        </div>
    </x-ui.card>
</div>
