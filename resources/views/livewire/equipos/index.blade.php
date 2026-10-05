@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
        'dado_de_baja' => ['variant' => 'danger', 'label' => __('Dado de baja')],
    ];
@endphp

<div class="space-y-6">
    <x-ui.table>
        {{--
            La búsqueda vive en la barra superior (global, ya filtra esta misma lista
            por estar enlazada a la URL ?q=). No se repite aquí para no duplicarla.
        --}}
        <x-slot name="filters">
            <span class="text-[14px] text-ink-muted">
                @if($busqueda !== '')
                    {{ __('Resultados para ":termino"', ['termino' => $busqueda]) }}
                @endif
            </span>

            <span class="ml-auto text-[14px] text-ink-muted">
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
                        <p class="text-[13px] text-ink-muted">{{ $equipo->marca?->nombre }} @if($equipo->modelo) · {{ $equipo->modelo }} @endif</p>
                    </td>
                    <td>
                        <p class="font-mono text-[14px]">{{ $equipo->serial }}</p>
                        <p class="text-[13px] text-ink-muted">{{ $equipo->codigo_activo ?? __('Sin código de activo') }}</p>
                    </td>
                    <td>
                        @if($asignacion?->persona)
                            <p>{{ $asignacion->persona->nombre }}</p>
                            <p class="text-[13px] text-ink-muted">{{ $asignacion->persona->cargo }}</p>
                        @else
                            <span class="text-ink-muted">{{ __('Sin asignar') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($asignacion)
                            <p>{{ $asignacion->sede?->nombre }}</p>
                            <p class="text-[13px] text-ink-muted">{{ $asignacion->dependencia?->nombre }}</p>
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

    <p class="text-[13px] text-ink-muted">
        {{ __('El registro, la edición y la hoja de vida completa de cada equipo son la siguiente parte de este bloque.') }}
    </p>
</div>
