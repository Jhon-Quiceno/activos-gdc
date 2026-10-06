@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
    ];
@endphp

<div class="space-y-6">
    <x-ui.table>
        <x-slot name="filters">
            <div class="relative w-full max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="busqueda"
                    placeholder="{{ __('Buscar por serial, código, responsable, cédula, dependencia o sede...') }}"
                    class="h-11 w-full rounded-lg border-line-input pl-9 text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                >
            </div>

            <span class="ml-auto text-[14px] text-ink-muted">
                {{ trans_choice(':count equipo|:count equipos', $equipos->total(), ['count' => $equipos->total()]) }}
            </span>
        </x-slot>

        <thead>
            <tr>
                <th>{{ __('Serial') }}</th>
                <th>{{ __('Código de activo') }}</th>
                <th>{{ __('Tipo') }}</th>
                <th>{{ __('Marca y modelo') }}</th>
                <th>{{ __('Responsable') }}</th>
                <th>{{ __('Dependencia') }}</th>
                <th>{{ __('Sede') }}</th>
                <th>{{ __('Estado') }}</th>
                <th>{{ __('Verificación') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($equipos as $equipo)
                @php
                    $asignacion = $equipo->asignacionActual;
                    $ciclo = $cicloEstilos[$equipo->estado_ciclo_vida] ?? ['variant' => 'neutral', 'label' => $equipo->estado_ciclo_vida];
                @endphp
                <tr
                    wire:key="equipo-{{ $equipo->id }}"
                    onclick="window.location.href='{{ route('equipos.show', $equipo) }}'"
                    class="cursor-pointer"
                >
                    <td class="font-mono">{{ $equipo->serial ?? __('Pendiente') }}</td>
                    <td class="font-mono">{{ $equipo->codigo_activo ?? __('Sin código') }}</td>
                    <td>{{ $equipo->tipoEquipo?->nombre }}</td>
                    <td>{{ $equipo->marca?->nombre }}{{ $equipo->modelo ? ' '.$equipo->modelo : '' }}</td>
                    <td>
                        @if($asignacion?->persona)
                            {{ $asignacion->persona->nombre }}
                        @else
                            <span class="text-ink-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($asignacion?->dependencia)
                            {{ $asignacion->dependencia->nombre }}
                        @else
                            <span class="text-ink-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($asignacion?->sede)
                            {{ $asignacion->sede->nombre }}
                        @else
                            <span class="text-ink-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge>
                    </td>
                    <td>
                        @if($equipo->verificacion === 'verificado')
                            <x-ui.badge variant="success">{{ __('Verificado') }}</x-ui.badge>
                        @elseif($equipo->verificacion === 'pendiente_de_verificar')
                            <x-ui.badge variant="warning">{{ __('Por verificar') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">{{ $equipo->verificacion }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="text-right" onclick="event.stopPropagation()">
                        <x-ui.button variant="primary" size="sm" class="whitespace-nowrap" :href="route('movimientos.traslado', $equipo)">
                            {{ __('Trasladar') }}
                        </x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="py-10 text-center text-ink-muted">
                        {{ __('No se encontraron equipos con ese criterio de búsqueda.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if($equipos->hasPages())
            <x-slot name="pagination">
                {{ $equipos->links() }}
            </x-slot>
        @endif
    </x-ui.table>
</div>
