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
            @include('livewire.equipos.partials.filtros')
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
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="py-10 text-center text-ink-muted">
                        {{ __('No se encontraron equipos con esa búsqueda y filtros.') }}
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
