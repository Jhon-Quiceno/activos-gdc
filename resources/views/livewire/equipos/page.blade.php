<x-layouts.app-shell title="Equipos">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Equipos')"
            :subtitle="__(':total equipos registrados · se identifican por serial y código de activo', ['total' => \App\Models\Equipo::count()])"
        >
            <x-slot name="actions">
                <x-ui.button :href="route('equipos.crear')" variant="primary">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('Registrar equipo') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <livewire:equipos.index />
    </div>
</x-layouts.app-shell>
