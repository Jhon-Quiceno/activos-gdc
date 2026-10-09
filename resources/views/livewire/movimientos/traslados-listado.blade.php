@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
    ];
    $firmaEstilos = [
        'pendiente_de_firma' => ['variant' => 'warning', 'label' => __('Pendiente de firma')],
        'completo' => ['variant' => 'success', 'label' => __('Firmas completas')],
    ];
    $claseFiltro = 'h-10 rounded-lg border-line-input text-[13px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-6">
    <x-ui.segmented-control
        :options="['equipos' => __('Equipos'), 'registrados' => __('Traslados registrados')]"
        :selected="$vista"
        model="vista"
    />

    @if ($vista === 'registrados')
        {{-- Historial de traslados: desde aquí se descargan sus dos formatos y se suben firmados. --}}
        <x-ui.table>
            <x-slot name="filters">
                <div class="relative w-full max-w-sm">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="busquedaTraslados"
                        placeholder="{{ __('Buscar por serial, código o nombre de quien entrega o recibe...') }}"
                        class="h-11 w-full rounded-lg border-line-input pl-9 text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                    >
                </div>

                <select wire:model.live="firma" class="{{ $claseFiltro }}" aria-label="{{ __('Firmas') }}">
                    <option value="">{{ __('Firmas: todas') }}</option>
                    <option value="pendiente_de_firma">{{ __('Pendiente de firma') }}</option>
                    <option value="completo">{{ __('Firmas completas') }}</option>
                </select>

                <span class="ml-auto text-[14px] text-ink-muted">
                    {{ trans_choice(':count traslado|:count traslados', $traslados->total(), ['count' => $traslados->total()]) }}
                </span>
            </x-slot>

            <thead>
                <tr>
                    <th>{{ __('Fecha') }}</th>
                    <th>{{ __('Equipo') }}</th>
                    <th>{{ __('Traslado') }}</th>
                    <th>{{ __('Registró') }}</th>
                    <th>{{ __('Firmas') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($traslados as $traslado)
                    @php
                        $anulado = $traslado->anulaciones->isNotEmpty();
                        $firma = $anulado
                            ? ['variant' => 'neutral', 'label' => __('Anulado')]
                            : ($firmaEstilos[$traslado->estado_firma] ?? ['variant' => 'neutral', 'label' => __('Sin formatos')]);
                    @endphp
                    <tr wire:key="traslado-{{ $traslado->id }}" wire:click="seleccionarTraslado({{ $traslado->id }})" @class(['cursor-pointer', '!bg-info-bg' => $trasladoSeleccionado === $traslado->id])>
                        <td class="whitespace-nowrap">{{ optional($traslado->fecha)->format('d/m/Y') }}</td>
                        <td>
                            <span class="font-mono">{{ $traslado->equipo?->serial }}</span>
                            <span class="block text-[13px] text-ink-muted">{{ $traslado->equipo?->tipoEquipo?->nombre }}{{ $traslado->equipo?->codigo_activo ? ' · '.$traslado->equipo->codigo_activo : '' }}</span>
                        </td>
                        <td class="max-w-[360px] text-[13px]">{{ $traslado->descripcion }}</td>
                        <td class="text-[13px]">{{ $traslado->usuario?->name }}</td>
                        <td><x-ui.badge :variant="$firma['variant']">{{ $firma['label'] }}</x-ui.badge></td>
                        <td class="text-right">
                            <x-ui.button variant="secondary" size="sm" class="whitespace-nowrap">
                                {{ $trasladoSeleccionado === $traslado->id ? __('Ocultar formatos') : __('Formatos y firmas') }}
                            </x-ui.button>
                        </td>
                    </tr>
                    @if ($trasladoSeleccionado === $traslado->id)
                        <tr wire:key="traslado-docs-{{ $traslado->id }}" class="hover:!bg-transparent">
                            <td colspan="6" class="bg-app-bg">
                                <livewire:movimientos.documentos-evento :evento-id="$traslado->id" :key="'docs-listado-'.$traslado->id" />
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-ink-muted">
                            {{ __('No hay traslados registrados con esa búsqueda.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if ($traslados->hasPages())
                <x-slot name="pagination">
                    {{ $traslados->links() }}
                </x-slot>
            @endif
        </x-ui.table>
    @else
        <x-ui.table>
            <x-slot name="filters">
                @include('livewire.equipos.partials.filtros', ['conDadosDeBaja' => false])
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
                        <td>{!! $asignacion?->persona ? e($asignacion->persona->nombre) : '<span class="text-ink-muted">—</span>' !!}</td>
                        <td>{!! $asignacion?->dependencia ? e($asignacion->dependencia->nombre) : '<span class="text-ink-muted">—</span>' !!}</td>
                        <td>{!! $asignacion?->sede ? e($asignacion->sede->nombre) : '<span class="text-ink-muted">—</span>' !!}</td>
                        <td><x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge></td>
                        <td class="text-right" onclick="event.stopPropagation()">
                            <x-ui.button variant="primary" size="sm" class="whitespace-nowrap" :href="route('movimientos.traslado', $equipo)">
                                {{ __('Trasladar') }}
                            </x-ui.button>
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

            @if ($equipos->hasPages())
                <x-slot name="pagination">
                    {{ $equipos->links() }}
                </x-slot>
            @endif
        </x-ui.table>
    @endif
</div>
