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
                <p class="mt-1 text-[14px] text-ink-muted">{{ $reporte['descripcion'] }}</p>
            </x-ui.card>
        @endforeach
    </div>

    <x-ui.card>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-[16px] font-semibold text-ink">{{ $reportes[$reporteActivo]['titulo'] }}</h2>
                <p class="mt-1 text-[14px] text-ink-muted">{{ $reportes[$reporteActivo]['descripcion'] }}</p>
            </div>

            <div class="flex items-center gap-3">
                <x-ui.button variant="secondary" size="sm" type="button" wire:click="exportarExcel">
                    {{ __('Exportar Excel') }}
                </x-ui.button>
                <x-ui.button variant="secondary" size="sm" type="button" wire:click="exportarPdf">
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
                @elseif ($filtroEspecifico)
                    <label class="text-[13px] font-semibold text-ink-label">{{ __($filtroEspecifico['etiqueta']) }}</label>
                    <select wire:model.live="filtroSistemaOperativo" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __($filtroEspecifico['todas']) }}</option>
                        @foreach ($filtroEspecifico['opciones'] as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
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

        <div x-data="{ abiertos: false }" class="mt-4">
            <div class="flex items-center gap-4">
                <button type="button" @click="abiertos = !abiertos" class="text-[14px] font-semibold text-primary">
                    <span x-show="!abiertos">{{ __('Más filtros') }}</span>
                    <span x-show="abiertos" x-cloak>{{ __('Menos filtros') }}</span>
                </button>
                <button type="button" wire:click="limpiarFiltros" class="text-[14px] text-ink-muted">
                    {{ __('Limpiar filtros') }}
                </button>
            </div>

            <div x-show="abiertos" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }}</label>
                    <select wire:model.live="filtroPiso" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($pisos as $piso)
                            <option value="{{ $piso->id }}">{{ __('Piso') }} {{ $piso->numero }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de equipo') }}</label>
                    <select wire:model.live="filtroTipo" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Marca') }}</label>
                    <select wire:model.live="filtroMarca" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Propiedad') }}</label>
                    <select wire:model.live="filtroPropiedad" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach ($propiedades as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Responsable') }}</label>
                    <select wire:model.live="filtroPersona" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($personas as $persona)
                            <option value="{{ $persona->id }}">{{ $persona->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Vinculación') }}</label>
                    <select wire:model.live="filtroVinculacion" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach ($vinculaciones as $vinculacion)
                            <option value="{{ $vinculacion }}">{{ $vinculacion }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __($etiquetaFecha) }} {{ __('desde') }}</label>
                    <input type="date" wire:model.live="filtroDesde" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                </div>

                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __($etiquetaFecha) }} {{ __('hasta') }}</label>
                    <input type="date" wire:model.live="filtroHasta" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                </div>
            </div>
        </div>
    </x-ui.card>

    @if ($registros)
        <x-ui.table>
            <x-slot name="filters">
                <span class="ml-auto text-[14px] text-ink-muted">
                    {{ trans_choice(':count registro|:count registros', $registros->total(), ['count' => $registros->total()]) }}
                </span>
            </x-slot>

            <thead>
                <tr>
                    @foreach ($encabezados as $encabezado)
                        <th>{{ $encabezado }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr wire:key="{{ $reporteActivo }}-{{ $registro->id }}">
                        @foreach ($columnas as $valor)
                            <td>{{ $valor($registro) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($encabezados) }}" class="py-10 text-center text-ink-muted">
                            {{ __('No se encontraron registros con ese criterio.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if ($registros->hasPages())
                <x-slot name="pagination">
                    {{ $registros->links() }}
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