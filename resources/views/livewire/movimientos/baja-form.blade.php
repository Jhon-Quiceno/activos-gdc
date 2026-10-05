@php
    $asignacion = $equipo->asignacionActual;
@endphp

<div class="space-y-6">
    <x-ui.page-header
        :title="__('Registrar baja')"
        :subtitle="trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modelo ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : ''))"
    />

    <div class="rounded-lg bg-danger-bg px-4 py-3 text-[14px] text-danger-text">
        {!! __('Al confirmar, el equipo quedará como <strong>Dado de baja</strong> en su hoja de vida. Solo se puede revertir con una anulación justificada.') !!}
    </div>

    <x-ui.card>
        <p class="section-title">{{ __('Diagnóstico de salida') }}</p>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Motivo') }} *</label>
                <select wire:model="motivoBajaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Selecciona…') }}</option>
                    @foreach ($motivosBaja as $motivo)
                        <option value="{{ $motivo->id }}">{{ $motivo->nombre }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('motivoBajaId')" class="mt-1" />
            </div>

            <x-ui.input type="date" name="fechaRevision" :label="__('Fecha de revisión') . ' *'" wire:model="fechaRevision" required />
        </div>

        <div class="mt-4">
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Estado encontrado') }} *</label>
            <textarea wire:model="estadoEncontrado" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
            <x-input-error :messages="$errors->get('estadoEncontrado')" class="mt-1" />
        </div>

        <div class="mt-4">
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Diagnóstico') }} *</label>
            <textarea wire:model="diagnostico" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
            <x-input-error :messages="$errors->get('diagnostico')" class="mt-1" />
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
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
        <x-ui.card>
            <p class="section-title">{{ __('Formato de baja') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-ui.button variant="secondary" size="sm" type="button">{{ __('Descargar PDF prellenado') }}</x-ui.button>
                <x-ui.button variant="ghost" size="sm" type="button">{{ __('Subir documento firmado') }}</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <p class="section-title">{{ __('Responsable que entrega') }}</p>
            <x-ui.definition-list :items="[
                __('Responsable') => $asignacion?->persona?->nombre ?? __('Sin asignar'),
                __('Sede / Piso') => $asignacion
                    ? trim(($asignacion->sede?->nombre ?? '—') . ' · ' . __('Piso :numero', ['numero' => $asignacion->piso?->numero ?? '—']))
                    : '—',
                __('Dependencia') => $asignacion?->dependencia?->nombre ?? '—',
            ]" class="mt-2" />
        </x-ui.card>
    </div>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('equipos.show', $equipo)">
            {{ __('Cancelar') }}
        </x-ui.button>
        <x-ui.button variant="danger" wire:click="confirmar">
            {{ __('Confirmar baja') }}
        </x-ui.button>
    </div>
</div>
