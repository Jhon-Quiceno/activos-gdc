@php
    use App\Livewire\Movimientos\Soporte\PendientesDeFirma;

    $selectClass = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary sm:w-52';
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :value="$resumen['total']" :label="__('Eventos pendientes de firma')" accent="primary" />
        <x-ui.kpi-card :value="$resumen['traslados']" :label="__('Traslados')" accent="info" />
        <x-ui.kpi-card :value="$resumen['bajas']" :label="__('Bajas')" accent="neutral" />
        <x-ui.kpi-card :value="$resumen['mas_30']" :label="__('Con más de 30 días')" accent="danger" />
    </div>

    @if ($eventoSeleccionado)
        <div id="firmas" class="scroll-mt-24">
            <div class="mb-2 flex justify-end">
                <x-ui.button variant="ghost" size="sm" wire:click="cerrarPanel">{{ __('Cerrar panel de firmas') }}</x-ui.button>
            </div>
            <livewire:movimientos.documentos-evento :evento-id="$eventoSeleccionado" :key="'docs-pendiente-'.$eventoSeleccionado" />
        </div>
    @endif

    <x-ui.table>
        <x-slot name="filters">
            <div class="w-full sm:w-auto">
                <label for="pend-buscar" class="text-[13px] font-semibold text-ink-label">{{ __('Equipo') }}</label>
                <input
                    id="pend-buscar"
                    type="search"
                    wire:model.live.debounce.400ms="busqueda"
                    placeholder="{{ __('Serial o código de activo') }}"
                    class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary sm:w-56"
                >
            </div>

            <div class="w-full sm:w-auto">
                <label for="pend-tipo" class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de evento') }}</label>
                <select id="pend-tipo" wire:model.live="tipo" class="{{ $selectClass }}">
                    <option value="">{{ __('Todos los tipos') }}</option>
                    @foreach ($tipos as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label for="pend-antiguedad" class="text-[13px] font-semibold text-ink-label">{{ __('Antigüedad') }}</label>
                <select id="pend-antiguedad" wire:model.live="antiguedad" class="{{ $selectClass }}">
                    <option value="">{{ __('Cualquier antigüedad') }}</option>
                    @foreach ($antiguedades as $valor => [$etiqueta])
                        <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                    @endforeach
                </select>
            </div>

            @if ($hayFiltros)
                <x-ui.button variant="ghost" size="sm" class="self-end" wire:click="limpiarFiltros">{{ __('Limpiar filtros') }}</x-ui.button>
            @endif

            <div class="flex w-full items-center gap-3 sm:ml-auto sm:w-auto">
                <span class="text-[14px] text-ink-muted">
                    {{ trans_choice(':count evento pendiente|:count eventos pendientes', $eventos->total(), ['count' => $eventos->total()]) }}
                </span>
                <x-ui.button variant="secondary" size="sm" wire:click="exportarExcel" wire:loading.attr="disabled" wire:target="exportarExcel">
                    {{ __('Exportar a Excel') }}
                </x-ui.button>
            </div>
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
                    $faltantes = $pendientes->faltantes($evento);
                    $dias = PendientesDeFirma::dias($evento);
                    $equipoEtiqueta = $evento->equipo?->codigo_activo ?: $evento->equipo?->serial;
                @endphp
                <tr wire:key="evento-{{ $evento->id }}" @class(['bg-info-bg' => $eventoSeleccionado === $evento->id])>
                    <td>
                        <p class="font-semibold text-ink">{{ PendientesDeFirma::etiquetaTipo($evento->tipo) }}</p>
                        <p class="text-[13px] text-ink-muted">#{{ $evento->id }}</p>
                    </td>
                    <td>
                        @if ($evento->equipo)
                            <a href="{{ route('equipos.show', $evento->equipo) }}" class="font-mono font-semibold text-primary hover:underline">{{ $equipoEtiqueta }}</a>
                            <p class="text-[13px] text-ink-muted">{{ $evento->equipo->tipoEquipo?->nombre }}</p>
                        @else
                            <span class="text-ink-muted">{{ __('Sin identificar') }}</span>
                        @endif
                    </td>
                    <td>
                        @forelse ($faltantes as $faltante)
                            <p>{{ $faltante['etiqueta'] }}</p>
                        @empty
                            <span class="text-ink-muted">—</span>
                        @endforelse
                    </td>
                    <td>
                        @forelse ($faltantes as $faltante)
                            <p>{{ $faltante['firma'] ?? __('Sin responsable') }}</p>
                        @empty
                            <span class="text-ink-muted">—</span>
                        @endforelse
                    </td>
                    <td>{{ $evento->usuario?->name ?? '—' }}</td>
                    <td>{{ $evento->fecha->format('d/m/Y') }}</td>
                    <td>
                        <x-ui.badge :variant="$dias > 30 ? 'danger' : 'warning'">
                            {{ trans_choice(':count día|:count días', $dias, ['count' => $dias]) }}
                        </x-ui.badge>
                    </td>
                    <td class="text-right">
                        {{-- Enlace directo: abre el panel de firmas de este evento (también se puede compartir). --}}
                        <a
                            href="{{ route('movimientos.pendientes', ['evento' => $evento->id]) }}#firmas"
                            wire:click.prevent="gestionar({{ $evento->id }})"
                            class="inline-flex h-9 items-center whitespace-nowrap rounded-lg bg-primary px-3.5 text-[14px] font-semibold text-white shadow-sm transition hover:bg-primary-hover"
                        >
                            {{ $eventoSeleccionado === $evento->id ? __('Ocultar') : __('Subir firmado') }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-10 text-center text-ink-muted">
                        {{ $hayFiltros ? __('No hay movimientos pendientes de firma con ese filtro.') : __('No hay movimientos pendientes de firma.') }}
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
