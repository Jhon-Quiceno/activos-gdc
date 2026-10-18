@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
    ];
    $firmaEstilos = [
        'pendiente_de_firma' => ['variant' => 'warning', 'label' => __('Pendiente de firma')],
        'completo' => ['variant' => 'danger', 'label' => __('Dado de baja')],
    ];
    $claseFiltro = 'h-10 rounded-lg border-line-input text-[13px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-6">
    <x-ui.segmented-control
        :options="['equipos' => __('Equipos'), 'registradas' => __('Bajas registradas')]"
        :selected="$vista"
        model="vista"
    />

    @if ($vista === 'registradas')
        {{-- Historial de bajas: desde aquí se descarga el formato de baja y se sube firmado. --}}
        <x-ui.table>
            <x-slot name="filters">
                <div class="relative w-full max-w-sm">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="busquedaBajas"
                        placeholder="{{ __('Buscar por serial, código o diagnóstico...') }}"
                        class="h-11 w-full rounded-lg border-line-input pl-9 text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                    >
                </div>

                <select wire:model.live="estadoBaja" class="{{ $claseFiltro }}" aria-label="{{ __('Estado de la baja') }}">
                    <option value="">{{ __('Estado: todos') }}</option>
                    <option value="pendiente_de_firma">{{ __('Pendiente de firma') }}</option>
                    <option value="completo">{{ __('Dado de baja (firmada)') }}</option>
                    <option value="anulada">{{ __('Anulada') }}</option>
                </select>

                <select wire:model.live="motivo" class="{{ $claseFiltro }}" aria-label="{{ __('Motivo de baja') }}">
                    <option value="">{{ __('Motivo: todos') }}</option>
                    @foreach ($motivos as $opcion)
                        <option value="{{ $opcion->id }}">{{ $opcion->nombre }}</option>
                    @endforeach
                </select>

                <span class="ml-auto text-[14px] text-ink-muted">
                    {{ trans_choice(':count baja|:count bajas', $bajas->total(), ['count' => $bajas->total()]) }}
                </span>
            </x-slot>

            <thead>
                <tr>
                    <th>{{ __('Fecha') }}</th>
                    <th>{{ __('Equipo') }}</th>
                    <th>{{ __('Motivo') }}</th>
                    <th>{{ __('Diagnóstico') }}</th>
                    <th>{{ __('Registró') }}</th>
                    <th>{{ __('Estado') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bajas as $baja)
                    @php
                        $estado = $baja->anulaciones->isNotEmpty()
                            ? ['variant' => 'neutral', 'label' => __('Anulada')]
                            : ($firmaEstilos[$baja->estado_firma] ?? ['variant' => 'neutral', 'label' => __('Sin formatos')]);
                    @endphp
                    <tr wire:key="baja-{{ $baja->id }}" wire:click="seleccionarBaja({{ $baja->id }})" @class(['cursor-pointer', '!bg-info-bg' => $bajaSeleccionada === $baja->id])>
                        <td class="whitespace-nowrap">{{ optional($baja->fecha)->format('d/m/Y') }}</td>
                        <td>
                            <span class="font-mono">{{ $baja->equipo?->serial }}</span>
                            <span class="block text-[13px] text-ink-muted">{{ $baja->equipo?->tipoEquipo?->nombre }}{{ $baja->equipo?->codigo_activo ? ' · '.$baja->equipo->codigo_activo : '' }}</span>
                        </td>
                        <td class="text-[13px]">{{ $baja->diagnostico?->motivoBaja?->nombre ?? '—' }}</td>
                        <td class="max-w-[320px] text-[13px]">{{ str($baja->diagnostico?->causa ?? $baja->descripcion)->limit(120) }}</td>
                        <td class="text-[13px]">{{ $baja->usuario?->name }}</td>
                        <td><x-ui.badge :variant="$estado['variant']">{{ $estado['label'] }}</x-ui.badge></td>
                        <td class="text-right">
                            <x-ui.button variant="secondary" size="sm" class="whitespace-nowrap">
                                {{ $bajaSeleccionada === $baja->id ? __('Ocultar formato') : __('Formato y firma') }}
                            </x-ui.button>
                        </td>
                    </tr>
                    @if ($bajaSeleccionada === $baja->id)
                        <tr wire:key="baja-docs-{{ $baja->id }}" class="hover:!bg-transparent">
                            <td colspan="7" class="bg-app-bg">
                                <livewire:movimientos.documentos-evento :evento-id="$baja->id" :key="'docs-bajas-'.$baja->id" />
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-ink-muted">
                            {{ __('No hay bajas registradas con esa búsqueda.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if ($bajas->hasPages())
                <x-slot name="pagination">
                    {{ $bajas->links() }}
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
                            <x-ui.button variant="danger" size="sm" class="whitespace-nowrap" :href="route('movimientos.baja', $equipo)">
                                {{ __('Dar de baja') }}
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
