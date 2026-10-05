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

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input type="date" name="fechaRevision" :label="__('Fecha de revisión') . ' *'" wire:model="fechaRevision" required />

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
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Recomendaciones') }}</label>
            <textarea wire:model="recomendaciones" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
        </div>

        <div class="mt-4">
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Evidencias') }}</label>
            {{-- TODO: la subida y el almacenamiento real de evidencias los resuelve el bloque de documentos. --}}
            <input type="file" class="mt-1 block w-full text-[14px] text-ink-muted" disabled>
        </div>

        <label class="mt-4 flex items-center gap-2 text-[14px] text-ink">
            <input type="checkbox" wire:model="generarFormato" class="rounded border-line-input text-primary focus:ring-primary">
            {{ __('Generar formato para firma del responsable (opcional)') }}
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
        <x-ui.button variant="success" wire:click="guardar">
            {{ __('Guardar diagnóstico') }}
        </x-ui.button>
    </div>
</div>
