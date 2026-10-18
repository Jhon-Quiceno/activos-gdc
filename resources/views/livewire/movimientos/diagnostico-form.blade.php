@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
        'dado_de_baja' => ['variant' => 'danger', 'label' => __('Dado de baja')],
    ];
    $ciclo = $cicloEstilos[$equipo->estado_ciclo_vida] ?? ['variant' => 'neutral', 'label' => $equipo->estado_ciclo_vida];
@endphp

<div class="space-y-6">
    <x-ui.page-header :title="__('Diagnóstico')">
        <x-slot name="actions">
            <x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge>
        </x-slot>
    </x-ui.page-header>

    <p class="-mt-4 text-[14px] text-ink-muted">
        {{ trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modelo ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : '')) }}
    </p>

    @if ($eventoId)
        <div class="rounded-lg border border-line bg-success-bg px-4 py-3 text-[14px] text-success-text" role="status">
            {{ __('Diagnóstico registrado. Descarga el formato, hazlo firmar por el responsable y súbelo para completar el evento.') }}
        </div>

        <livewire:movimientos.documentos-evento :evento-id="$eventoId" :key="'docs-diagnostico-'.$eventoId" />

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="primary" :href="route('equipos.show', $equipo)">{{ __('Ver hoja de vida') }}</x-ui.button>
        </div>
    @elseif ($dadoDeBaja)
        <div class="rounded-lg border border-[#EBC7C7] bg-danger-bg px-4 py-3 text-[14px] text-[#7E2A2A]">
            {{ __('Este equipo está dado de baja: no admite nuevos diagnósticos. Si la baja fue un error, anúlala desde la pantalla de baja.') }}
        </div>
        <div class="flex justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('equipos.show', $equipo)">{{ __('Ver hoja de vida') }}</x-ui.button>
            <x-ui.button variant="danger" :href="route('movimientos.baja', $equipo)">{{ __('Ir a la baja') }}</x-ui.button>
        </div>
    @else
    <x-input-error :messages="$errors->get('equipo')" />

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input type="date" name="fechaRevision" :label="__('Fecha de revisión') . ' *'" wire:model="fechaRevision" max="{{ now()->toDateString() }}" required />

            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Estado encontrado') }} *</label>
                <select wire:model="estadoEncontrado" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Selecciona…') }}</option>
                    @foreach ($estadosEncontrados as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('estadoEncontrado')" class="mt-1" />
            </div>
        </div>

        <div class="mt-4">
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Descripción del estado') }} *</label>
            <textarea wire:model="descripcionEstado" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
            <x-input-error :messages="$errors->get('descripcionEstado')" class="mt-1" />
        </div>

        <div class="mt-4">
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Causa') }} *</label>
            <textarea wire:model="causa" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
            <x-input-error :messages="$errors->get('causa')" class="mt-1" />
        </div>

        <div class="mt-4">
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Recomendaciones / sugerencias del área de sistemas') }}</label>
            <textarea wire:model="recomendaciones" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
        </div>

        @include('livewire.movimientos.partials.evidencias')

        <label class="mt-4 flex items-center gap-2 text-[14px] text-ink">
            <input type="checkbox" wire:model="generarFormato" class="rounded border-line-input text-primary focus:ring-primary">
            {{ __('Generar formato para firma del responsable (opcional): el evento quedará «Pendiente de firma» hasta subirlo firmado') }}
        </label>
    </x-ui.card>

    <p class="text-[13px] text-ink-muted">
        {{ __('Si el diagnóstico concluye que el equipo debe salir de servicio, registra la baja: allí se genera el formato de baja.') }}
    </p>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('equipos.show', $equipo)">
            {{ __('Cancelar') }}
        </x-ui.button>
        <x-ui.button variant="danger" wire:click="continuarABaja">
            {{ __('Continuar a baja') }}
        </x-ui.button>
        <x-ui.button variant="success" wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar,evidencias">
            {{ __('Guardar diagnóstico') }}
        </x-ui.button>
    </div>
    @endif
</div>
