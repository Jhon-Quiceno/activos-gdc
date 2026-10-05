<div class="space-y-6">
    <x-ui.card class="text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-danger-bg text-danger-text">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>

        <p class="mx-auto mt-4 max-w-md text-sm text-ink-muted">
            {{ __('Aquí vas a poder administrar usuarios, catálogos y parámetros del sistema.') }}
        </p>

        <span class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-neutral-bg px-3 py-1 text-xs font-semibold text-neutral-text">
            {{ __('Próximamente') }}
        </span>
    </x-ui.card>

    {{-- Vista fantasma de la futura gestión de usuarios/permisos --}}
    <x-ui.card :padding="false" class="opacity-50" aria-hidden="true">
        <div class="divide-y divide-line">
            @for ($i = 0; $i < 4; $i++)
                <div class="flex items-center gap-4 px-5 py-4">
                    <div class="h-9 w-9 shrink-0 rounded-full bg-line"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-3 w-1/4 rounded bg-line"></div>
                        <div class="h-2.5 w-1/3 rounded bg-line"></div>
                    </div>
                    <div class="h-5 w-9 shrink-0 rounded-full bg-line"></div>
                </div>
            @endfor
        </div>
    </x-ui.card>
</div>
