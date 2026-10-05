<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ($reportes as $clave => $reporte)
            <x-ui.card
                wire:click="seleccionarReporte('{{ $clave }}')"
                wire:key="reporte-card-{{ $clave }}"
                clickable
                :accent="$reporteActivo === $clave ? 'primary' : null"
            >
                <p class="text-[15px] font-semibold text-ink">{{ $reporte['titulo'] }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">{{ $reporte['descripcion'] }}</p>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-[18px] font-semibold text-ink">{{ $reportes[$reporteActivo]['titulo'] }}</h2>
                <p class="mt-1 text-[14px] text-ink-muted">{{ $reportes[$reporteActivo]['descripcion'] }}</p>
            </div>

            <div class="flex items-center gap-3">
                {{-- TODO: el bloque de Reportes conecta estos botones con maatwebsite/excel y barryvdh/laravel-dompdf. --}}
                <x-ui.button variant="secondary" size="sm" type="button">
                    {{ __('Exportar Excel') }}
                </x-ui.button>
                <x-ui.button variant="secondary" size="sm" type="button">
                    {{ __('Exportar PDF') }}
                </x-ui.button>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Sede') }}</label>
                <select wire:model.live="filtroSede" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Todas') }}</option>
                    @foreach ($sedes as $sede)
                        <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }}</label>
                <select wire:model.live="filtroDependencia" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Todas') }}</option>
                    @foreach ($dependencias as $dependencia)
                        <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Estado') }}</label>
                <select wire:model.live="filtroEstado" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach ($estados as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                @if ($reporteActivo === 'obsolescencia_so')
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Sistema operativo') }}</label>
                    <select wire:model.live="filtroSistemaOperativo" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todos los obsoletos') }}</option>
                        @foreach ($sistemasOperativosObsoletos as $so)
                            <option value="{{ $so->id }}">{{ $so->nombre }}</option>
                        @endforeach
                    </select>
                @else
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Filtro específico') }}</label>
                    <select disabled class="mt-1 h-11 w-full rounded-lg border-line-input bg-app-bg text-[14px] text-ink-muted">
                        <option>{{ __('No disponible todavía') }}</option>
                    </select>
                @endif
            </div>
        </div>
    </x-ui.card>

    @if ($reporteActivo === 'obsolescencia_so')
        <x-ui.table>
            <x-slot name="filters">
                <span class="ml-auto text-[14px] text-ink-muted">
                    {{ trans_choice(':count equipo obsoleto|:count equipos obsoletos', $equipos->total(), ['count' => $equipos->total()]) }}
                </span>
            </x-slot>

            <thead>
                <tr>
                    <th>{{ __('Serial') }}</th>
                    <th>{{ __('Código de activo') }}</th>
                    <th>{{ __('Tipo') }}</th>
                    <th>{{ __('Sistema operativo') }}</th>
                    <th>{{ __('Responsable') }}</th>
                    <th>{{ __('Dependencia') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipos as $equipo)
                    <tr wire:key="obsolescencia-{{ $equipo->id }}">
                        <td class="font-mono">{{ $equipo->serial }}</td>
                        <td>{{ $equipo->codigo_activo ?? __('Sin código de activo') }}</td>
                        <td>{{ $equipo->tipoEquipo?->nombre }}</td>
                        <td>{{ $equipo->configuracionComputo?->sistemaOperativo?->nombre }}</td>
                        <td>{{ $equipo->asignacionActual?->persona?->nombre ?? __('Sin asignar') }}</td>
                        <td>{{ $equipo->asignacionActual?->dependencia?->nombre ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-ink-muted">
                            {{ __('No se encontraron equipos obsoletos con ese criterio.') }}
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
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <th>{{ __('Resultado') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-10 text-center text-ink-muted">
                        {{ __('Este reporte se implementa en el bloque de Reportes.') }}
                    </td>
                </tr>
            </tbody>
        </x-ui.table>
    @endif
</div>
