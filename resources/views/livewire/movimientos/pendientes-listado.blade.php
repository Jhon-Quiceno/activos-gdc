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
@endphp

<div class="space-y-6">
    <x-ui.table>
        <x-slot name="filters">
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de evento') }}</label>
                <select wire:model.live="tipo" class="mt-1 h-11 w-48 rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Todos los tipos') }}</option>
                    <option value="alta">{{ __('Alta') }}</option>
                    <option value="traslado_responsable">{{ __('Traslado') }}</option>
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
                    $dias = (int) $evento->fecha->diffInDays(now());
                    $equipoEtiqueta = $evento->equipo?->codigo_activo ?: $evento->equipo?->serial;
                @endphp
                <tr wire:key="evento-{{ $evento->id }}">
                    <td>
                        <p class="font-semibold text-ink">{{ $tipoEventoLabels[$evento->tipo] ?? $evento->tipo }}</p>
                    </td>
                    <td>
                        @if($evento->equipo)
                            <a href="{{ route('equipos.show', $evento->equipo) }}" wire:navigate class="font-mono text-[14px] text-primary hover:underline">
                                {{ $equipoEtiqueta ?? __('Sin identificar') }}
                            </a>
                        @else
                            <span class="text-ink-muted">—</span>
                        @endif
                    </td>
                    <td>
                        {{ $evento->tipo === 'baja' ? __('Formato de baja') : __('Formato de entrega') }}
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
                    <td class="text-right">
                        @if($evento->equipo)
                            <x-ui.button variant="secondary" size="sm" :href="route('equipos.show', $evento->equipo)">
                                {{ __('Ver equipo') }}
                            </x-ui.button>
                        @endif
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
