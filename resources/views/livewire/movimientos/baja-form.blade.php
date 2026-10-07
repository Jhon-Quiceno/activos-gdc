@php
    $asignacion = $equipo->asignacionActual;
    $dadoDeBaja = $equipo->estado_ciclo_vida === 'dado_de_baja';
    $enTramite = ! $dadoDeBaja && $bajaVigente && $bajaVigente->estado_firma === 'pendiente_de_firma';
    $selectClass = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
    $textareaClass = 'mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-6">
    <x-ui.page-header
        :title="$dadoDeBaja ? __('Equipo dado de baja') : __('Registrar baja')"
        :subtitle="trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modelo ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : ''))"
    >
        <x-slot name="actions">
            @if ($dadoDeBaja)
                <x-ui.badge variant="danger">{{ __('Dado de baja') }}</x-ui.badge>
            @elseif ($enTramite)
                <x-ui.badge variant="warning">{{ __('Baja pendiente de firma') }}</x-ui.badge>
            @endif
        </x-slot>
    </x-ui.page-header>

    @if (session('estado-baja'))
        <div class="rounded-lg border border-line bg-success-bg px-4 py-3 text-[14px] text-success-text" role="status">
            {{ session('estado-baja') }}
        </div>
    @endif

    @if ($dadoDeBaja || $enTramite)
        {{-- Estados 2 y 3: la baja ya está registrada. --}}
        @if ($dadoDeBaja)
            <div class="rounded-lg border border-[#EBC7C7] bg-danger-bg px-4 py-3 text-[14px] text-[#7E2A2A]">
                {{ __('El equipo está dado de baja y conserva su hoja de vida. No admite nuevos eventos, salvo la anulación justificada de la baja.') }}
            </div>
        @endif

        @if ($bajaVigente)
            <x-ui.card>
                <p class="section-title">{{ __('Baja registrada') }}</p>
                <x-ui.definition-list :items="[
                    __('Fecha de revisión') => $bajaVigente->fecha->format('d/m/Y'),
                    __('Motivo') => $bajaVigente->diagnostico?->motivoBaja?->nombre ?? '—',
                    __('Estado encontrado') => $bajaVigente->diagnostico?->estado_encontrado ?? '—',
                    __('Diagnóstico') => $bajaVigente->diagnostico?->causa ?? '—',
                    __('Recomendaciones') => $bajaVigente->diagnostico?->recomendaciones ?? '—',
                    __('Registrada por') => $bajaVigente->usuario?->name ?? '—',
                ]" class="mt-2" />
            </x-ui.card>

            <livewire:movimientos.documentos-evento :evento-id="$bajaVigente->id" :key="'docs-baja-'.$bajaVigente->id" />
        @endif

        <x-ui.card>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="section-title">{{ __('Anular baja') }}</p>
                    <p class="mt-1 text-[13px] text-ink-muted">{{ __('Si la baja se registró por error, se anula con un evento justificado y el equipo vuelve a su estado anterior. La baja original queda en el historial.') }}</p>
                </div>
                @unless ($mostrarAnulacion)
                    <x-ui.button variant="secondary" size="sm" wire:click="$set('mostrarAnulacion', true)">{{ __('Anular baja') }}</x-ui.button>
                @endunless
            </div>

            @if ($mostrarAnulacion)
                <div class="mt-4">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Justificación') }} *</label>
                    <textarea wire:model="justificacion" rows="3" class="{{ $textareaClass }}"></textarea>
                    <x-input-error :messages="$errors->get('justificacion')" class="mt-1" />
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <x-ui.button variant="secondary" size="sm" wire:click="$set('mostrarAnulacion', false)">{{ __('Cancelar') }}</x-ui.button>
                    <x-ui.button variant="danger" size="sm" wire:click="anular" wire:loading.attr="disabled" wire:target="anular">{{ __('Confirmar anulación') }}</x-ui.button>
                </div>
            @endif
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('movimientos.bajas.index')">{{ __('Volver a bajas') }}</x-ui.button>
            <x-ui.button variant="primary" :href="route('equipos.show', $equipo)">{{ __('Ver hoja de vida') }}</x-ui.button>
        </div>
    @else
        {{-- Estado 1: formulario de baja. --}}
        <div class="rounded-lg border border-[#EBC7C7] bg-danger-bg px-4 py-3 text-[14px] text-[#7E2A2A]">
            {!! __('Al confirmar se genera el formato de baja prellenado. Cuando subas el formato <strong>firmado</strong>, el equipo quedará como <strong>Dado de baja</strong> en su hoja de vida. Solo se puede revertir con una anulación justificada.') !!}
        </div>

        <x-input-error :messages="$errors->get('equipo')" />

        <x-ui.card>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Motivo') }} *</label>
                    <select wire:model="motivoBajaId" class="{{ $selectClass }}">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($motivosBaja as $motivo)
                            <option value="{{ $motivo->id }}">{{ $motivo->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('motivoBajaId')" class="mt-1" />
                </div>

                <x-ui.input type="date" name="fechaRevision" :label="__('Fecha de revisión') . ' *'" wire:model="fechaRevision" max="{{ now()->toDateString() }}" required />
            </div>

            <div class="mt-4">
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Estado encontrado') }} *</label>
                <textarea wire:model="estadoEncontrado" rows="3" class="{{ $textareaClass }}"></textarea>
                <x-input-error :messages="$errors->get('estadoEncontrado')" class="mt-1" />
            </div>

            <div class="mt-4">
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Diagnóstico') }} *</label>
                <textarea wire:model="diagnostico" rows="3" class="{{ $textareaClass }}"></textarea>
                <x-input-error :messages="$errors->get('diagnostico')" class="mt-1" />
            </div>

            <div class="mt-4">
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Recomendaciones / sugerencias del área de sistemas') }}</label>
                <textarea wire:model="recomendaciones" rows="3" class="{{ $textareaClass }}"></textarea>
                <x-input-error :messages="$errors->get('recomendaciones')" class="mt-1" />
            </div>

            @include('livewire.movimientos.partials.evidencias')
        </x-ui.card>

        <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
            <x-ui.card>
                <div class="flex items-center justify-between gap-3">
                    <p class="section-title">{{ __('Formato de baja') }}</p>
                    <x-ui.badge variant="neutral">{{ __('Se genera al confirmar') }}</x-ui.badge>
                </div>
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Firman: ingeniero de soporte y funcionario responsable') }}</p>
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
            <x-ui.button variant="danger" wire:click="confirmar" wire:loading.attr="disabled" wire:target="confirmar,evidencias">
                {{ __('Confirmar baja') }}
            </x-ui.button>
        </div>
    @endif
</div>
