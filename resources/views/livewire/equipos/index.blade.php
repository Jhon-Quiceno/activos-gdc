@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
        'dado_de_baja' => ['variant' => 'danger', 'label' => __('Dado de baja')],
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
                    class="h-11 w-full rounded-lg border-line-input pl-9 text-sm text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                >
            </div>

            <span class="ml-auto text-sm text-ink-muted">
                {{ trans_choice(':count equipo|:count equipos', $equipos->total(), ['count' => $equipos->total()]) }}
            </span>
        </x-slot>

        <thead>
            <tr>
                <th>{{ __('Equipo') }}</th>
                <th>{{ __('Serial / Código') }}</th>
                <th>{{ __('Responsable') }}</th>
                <th>{{ __('Ubicación') }}</th>
                <th>{{ __('Estado') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($equipos as $equipo)
                @php
                    $asignacion = $equipo->asignacionActual;
                    $ciclo = $cicloEstilos[$equipo->estado_ciclo_vida] ?? ['variant' => 'neutral', 'label' => $equipo->estado_ciclo_vida];
                @endphp
                <tr wire:key="equipo-{{ $equipo->id }}">
                    <td>
                        <p class="font-semibold text-ink">{{ $equipo->tipoEquipo?->nombre }}</p>
                        <p class="text-xs text-ink-muted">{{ $equipo->marca?->nombre }} @if($equipo->modelo) · {{ $equipo->modelo }} @endif</p>
                    </td>
                    <td>
                        <p class="font-mono text-sm">{{ $equipo->serial }}</p>
                        <p class="text-xs text-ink-muted">{{ $equipo->codigo_activo ?? __('Sin código de activo') }}</p>
                    </td>
                    <td>
                        @if($asignacion?->persona)
                            <p>{{ $asignacion->persona->nombre }}</p>
                            <p class="text-xs text-ink-muted">{{ $asignacion->persona->cargo }}</p>
                        @else
                            <span class="text-ink-muted">{{ __('Sin asignar') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($asignacion)
                            <p>{{ $asignacion->sede?->nombre }}</p>
                            <p class="text-xs text-ink-muted">{{ $asignacion->dependencia?->nombre }}</p>
                        @else
                            <span class="text-ink-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-col items-start gap-1">
                            <x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge>
                            @if($equipo->verificacion === 'pendiente_de_verificar')
                                <x-ui.badge variant="warning">{{ __('Pendiente de verificar') }}</x-ui.badge>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-ink-muted">
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

    <p class="text-xs text-ink-muted">
        {{ __('El registro, la edición y la hoja de vida completa de cada equipo son la siguiente parte de este bloque.') }}
    </p>
</div>
