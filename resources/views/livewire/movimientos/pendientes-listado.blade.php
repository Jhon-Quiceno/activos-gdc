@php
    $tipoEventoLabels = [
        'alta' => __('Alta'),
        'traslado_responsable' => __('Traslado'),
        'cambio_componente' => __('Cambio de componente'),
        'diagnostico' => __('Diagnóstico'),
        'baja' => __('Baja'),
        'actualizacion_datos' => __('Actualización de datos'),
        'anulacion_aclaracion' => __('Anulación / aclaración'),
    ];
    $formatoLabels = [
        'formato_baja' => __('Formato de baja'),
        'formato_entrega' => __('Formato de entrega'),
    ];
@endphp

<div class="space-y-6">
    @if ($eventoSeleccionado)
        <div>
            <div class="mb-2 flex justify-end">
                <x-ui.button variant="ghost" size="sm" wire:click="cerrarPanel">{{ __('Cerrar panel de firmas') }}</x-ui.button>
            </div>
            <livewire:movimientos.documentos-evento :evento-id="$eventoSeleccionado" :key="'docs-pendiente-'.$eventoSeleccionado" />
        </div>
    @endif

    <x-ui.table>
        <x-slot name="filters">
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de evento') }}</label>
                <select wire:model.live="tipo" class="mt-1 h-11 w-48 rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Todos los tipos') }}</option>
                    <option value="alta">{{ __('Alta') }}</option>
                    <option value="traslado_responsable">{{ __('Traslado') }}</option>
                    <option value="diagnostico">{{ __('Diagnóstico') }}</option>
                    <option value="baja">{{ __('Baja') }}</option>
                </select>
            </div>

            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Antigüedad') }}</label>
                <select wire:model.live="antiguedad" class="mt-1 h-11 w-48 rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Cualquier antigüedad') }}</option>
                    <option value="mas_30">{{ __('Más de 30 días') }}</option>
                </select>
            </div>

            <span class="ml-auto text-[14px] text-ink-muted">
                {{ trans_choice(':count evento pendiente|:count eventos pendientes', $eventos->total(), ['count' => $eventos->total()]) }}
            </span>
        </x-slot>

        <thead>
            <tr>
                <th>{{ __('Evento') }}</th>
                <th>{{ __('Equipo') }}</th>
                <th>{{ __('Falta') }}</th>
                <th>{{ __('Quién firma') }}</th>
                <th>{{ __('Registrado por') }}</th>
                <th>{{ __('Fecha') }}</th>
                <th>{{ __('Pendiente') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($eventos as $evento)
                @php
                    $firmados = $evento->documentos->pluck('tipo')->all();
                    $faltan = array_values(array_diff($firmas->requeridos($evento), $firmados));
                    $dias = (int) $evento->fecha->diffInDays(now());
                    $equipoEtiqueta = $evento->equipo?->codigo_activo ?: $evento->equipo?->serial;
                @endphp
                <tr
                    wire:key="evento-{{ $evento->id }}"
                    @if($evento->equipo)
                        onclick="window.location.href='{{ route('equipos.show', $evento->equipo) }}'"
                        class="cursor-pointer"
                    @endif
                >
                    <td>
                        <p class="font-semibold text-ink">{{ $tipoEventoLabels[$evento->tipo] ?? $evento->tipo }}</p>
                    </td>
                    <td class="font-mono">
                        {{ $equipoEtiqueta ?? __('Sin identificar') }}
                    </td>
                    <td>
                        {{ collect($faltan)->map(fn ($t) => $formatoLabels[$t] ?? $t)->implode(' + ') ?: '—' }}
                    </td>
                    <td>
                        {{ $evento->equipo?->asignacionActual?->persona?->nombre ?? '—' }}
                    </td>
                    <td>
                        {{ $evento->usuario?->name ?? '—' }}
                    </td>
                    <td>
                        {{ $evento->fecha->format('d/m/Y') }}
                    </td>
                    <td>
                        <x-ui.badge :variant="$dias > 30 ? 'danger' : 'warning'">
                            {{ trans_choice(':count día|:count días', $dias, ['count' => $dias]) }}
                        </x-ui.badge>
                    </td>
                    <td class="text-right" onclick="event.stopPropagation()">
                        <x-ui.button variant="primary" size="sm" class="whitespace-nowrap" wire:click="gestionar({{ $evento->id }})">
                            {{ $eventoSeleccionado === $evento->id ? __('Ocultar') : __('Subir firmados') }}
                        </x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-ink-muted">
                        {{ __('No hay movimientos pendientes de firma con ese filtro.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($eventos->hasPages())
            <x-slot name="pagination">
                {{ $eventos->links() }}
            </x-slot>
        @endif
    </x-ui.table>
</div>
