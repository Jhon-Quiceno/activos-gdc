<div class="flex min-h-[70vh] items-center justify-center">
    <div class="w-full max-w-md">
        <x-ui.card>
            <p class="section-title">{{ __('Cambiar contraseña') }}</p>
            <p class="mt-2 text-[14px] text-ink-muted">
                {{ __('Por seguridad, antes de continuar tenés que cambiar la contraseña inicial que te asignó el administrador.') }}
            </p>

            <form wire:submit="guardar" class="mt-5 space-y-4">
                <x-ui.input
                    name="password"
                    type="password"
                    :label="__('Contraseña nueva') . ' *'"
                    wire:model="password"
                    required
                />
                <x-ui.input
                    name="password_confirmation"
                    type="password"
                    :label="__('Confirmar contraseña') . ' *'"
                    wire:model="password_confirmation"
                    required
                />

                <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                    {{ __('Guardar y continuar') }}
                </x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>
