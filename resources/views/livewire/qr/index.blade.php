{{-- Vista de App\Livewire\Qr\Index: impresión de etiquetas QR por lotes. --}}
@php
    $claseFiltro = 'h-10 rounded-lg border-line-input text-[13px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :value="$sinEtiqueta" :label="__('Equipos sin etiqueta impresa')" accent="warning" />
        <x-ui.card class="sm:col-span-2">
            <p class="text-[14px] text-ink">
                {{ __('Cada equipo tiene su código QR desde que se registra. Elige los equipos y abre la hoja de etiquetas para imprimirlas; queda registrado quién las imprimió y cuándo.') }}
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ui.button variant="primary" :href="$urlImprimir" target="_blank" :disabled="empty($seleccionados)">
                    {{ trans_choice('Imprimir :count etiqueta|Imprimir :count etiquetas', count($seleccionados), ['count' => count($seleccionados)]) }}
                </x-ui.button>
                <x-ui.button variant="secondary" size="sm" wire:click="seleccionarFiltrados">
                    {{ __('Seleccionar todos los filtrados') }}
                </x-ui.button>
                @if (! empty($seleccionados))
                    <x-ui.button variant="ghost" size="sm" wire:click="limpiarSeleccion">{{ __('Quitar selección') }}</x-ui.button>
                @endif
            </div>
            @if (count($seleccionados) >= \App\Livewire\Qr\Etiquetas::MAXIMO)
                <p class="mt-2 text-[13px] text-warning-text">{{ __('Una hoja admite hasta :max etiquetas; imprime el resto en otra tanda.', ['max' => \App\Livewire\Qr\Etiquetas::MAXIMO]) }}</p>
            @endif
        </x-ui.card>
    </div>

    <x-ui.table>
        <x-slot name="filters">
            @include('livewire.equipos.partials.filtros')

            <div class="flex w-full flex-wrap items-center gap-2">
                <select wire:model.live="etiqueta" class="{{ $claseFiltro }}" aria-label="{{ __('Etiqueta') }}">
                    <option value="">{{ __('Etiqueta: todas') }}</option>
                    <option value="sin">{{ __('Sin etiqueta impresa') }}</option>
                    <option value="con">{{ __('Con etiqueta impresa') }}</option>
                </select>
            </div>
        </x-slot>

        <thead>
            <tr>
                <th class="w-10"><span class="sr-only">{{ __('Elegir') }}</span></th>
                <th>{{ __('Serial') }}</th>
                <th>{{ __('Código de activo') }}</th>
                <th>{{ __('Tipo') }}</th>
                <th>{{ __('Responsable') }}</th>
                <th>{{ __('Sede') }}</th>
                <th>{{ __('Etiqueta') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($equipos as $equipo)
                <tr wire:key="qr-equipo-{{ $equipo->id }}">
                    <td>
                        <input type="checkbox" value="{{ $equipo->id }}" wire:model.live="seleccionados" class="rounded border-line-input text-primary focus:ring-primary" aria-label="{{ __('Elegir :serial', ['serial' => $equipo->serial]) }}">
                    </td>
                    <td class="font-mono"><a href="{{ route('equipos.show', $equipo) }}" class="hover:text-primary hover:underline">{{ $equipo->serial }}</a></td>
                    <td class="font-mono">{{ $equipo->codigo_activo ?? __('Sin código') }}</td>
                    <td>{{ $equipo->tipoEquipo?->nombre }}</td>
                    <td>{!! $equipo->asignacionActual?->persona ? e($equipo->asignacionActual->persona->nombre) : '<span class="text-ink-muted">—</span>' !!}</td>
                    <td>{!! $equipo->asignacionActual?->sede ? e($equipo->asignacionActual->sede->nombre) : '<span class="text-ink-muted">—</span>' !!}</td>
                    <td>
                        @if ($equipo->etiquetas_qr_count > 0)
                            <x-ui.badge variant="success">{{ trans_choice('Impresa :count vez|Impresa :count veces', $equipo->etiquetas_qr_count, ['count' => $equipo->etiquetas_qr_count]) }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning">{{ __('Sin imprimir') }}</x-ui.badge>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-ink-muted">{{ __('No se encontraron equipos con esa búsqueda y filtros.') }}</td>
                </tr>
            @endforelse
        </tbody>

        @if ($equipos->hasPages())
            <x-slot name="pagination">
                {{ $equipos->links() }}
            </x-slot>
        @endif
    </x-ui.table>
</div>
