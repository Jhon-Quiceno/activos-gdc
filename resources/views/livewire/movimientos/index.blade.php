@php
    use App\Livewire\Movimientos\Soporte\Cedula;

    $vinculaciones = ['planta' => __('Planta'), 'contratista' => __('Contratista')];
    $selectClass = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-6">
    @if ($mensaje)
        <div class="rounded-lg border border-line bg-success-bg px-4 py-3 text-[14px] text-success-text" role="status">
            {{ $mensaje }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.kpi-card :value="$personas->total()" :label="__('Personas responsables')" />
        <x-ui.kpi-card :value="$sinAsignar" :label="__('Equipos sin asignar (bodega)')" accent="neutral">
            <x-slot name="footer">
                <a href="{{ route('movimientos.traslados.index', ['estado' => 'sin_asignar']) }}" class="font-semibold text-primary hover:underline">{{ __('Ver listado') }}</a>
            </x-slot>
        </x-ui.kpi-card>
        <x-ui.card class="flex flex-col justify-center gap-2">
            <x-ui.button variant="primary" :href="route('movimientos.traslados.index')">{{ __('Registrar traslado') }}</x-ui.button>
            <x-ui.button variant="secondary" :href="route('movimientos.pendientes')">{{ __('Pendientes de firma') }}</x-ui.button>
        </x-ui.card>
    </div>

    @if ($mostrarFormulario)
        <x-ui.card>
            <p class="section-title">{{ __('Nueva persona responsable') }}</p>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input class="sm:col-span-2" name="nombre" :label="__('Nombre completo')" wire:model="nombre" required />
                <x-ui.input name="cedula" :label="__('Cédula')" wire:model="cedula" inputmode="numeric" required />
                <x-ui.input name="cargo" :label="__('Cargo')" wire:model="cargo" required />
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }}</label>
                    <select wire:model="dependenciaId" class="{{ $selectClass }}">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($dependencias as $dependencia)
                            <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('dependenciaId')" class="mt-1" />
                </div>
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de vinculación') }}</label>
                    <select wire:model="tipoVinculacion" class="{{ $selectClass }}">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($vinculaciones as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('tipoVinculacion')" class="mt-1" />
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-3">
                <x-ui.button variant="secondary" wire:click="$set('mostrarFormulario', false)">{{ __('Cancelar') }}</x-ui.button>
                <x-ui.button variant="primary" wire:click="crearPersona">{{ __('Guardar persona') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    @if ($seleccionada)
        <x-ui.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="section-title">{{ $seleccionada->nombre }}</p>
                    <p class="mt-1 text-[13px] text-ink-muted">
                        {{ __('C.C.') }} {{ Cedula::enmascarar($seleccionada->cedula) }}
                        · {{ $seleccionada->cargo ?? __('Sin cargo') }}
                        · {{ $seleccionada->dependencia?->nombre ?? __('Sin dependencia') }}
                        · {{ $vinculaciones[$seleccionada->tipo_vinculacion] ?? __('Sin vinculación') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button variant="primary" size="sm" wire:click="descargarConsolidado({{ $seleccionada->id }})" :disabled="$equiposACargo->isEmpty()">
                        {{ __('Formato de entrega consolidado (PDF)') }}
                    </x-ui.button>
                    <x-ui.button variant="ghost" size="sm" wire:click="$set('personaSeleccionada', null)">{{ __('Cerrar') }}</x-ui.button>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-[14px]">
                    <thead>
                        <tr class="border-b border-line text-[13px] text-ink-label">
                            <th class="py-2 pr-3 font-semibold">{{ __('Serial') }}</th>
                            <th class="py-2 pr-3 font-semibold">{{ __('Código de activo') }}</th>
                            <th class="py-2 pr-3 font-semibold">{{ __('Tipo') }}</th>
                            <th class="py-2 pr-3 font-semibold">{{ __('Marca y modelo') }}</th>
                            <th class="py-2 pr-3 font-semibold">{{ __('Ubicación') }}</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($equiposACargo as $equipo)
                            <tr wire:key="a-cargo-{{ $equipo->id }}">
                                <td class="py-2 pr-3 font-mono">{{ $equipo->serial }}</td>
                                <td class="py-2 pr-3 font-mono">{{ $equipo->codigo_activo ?? __('Sin código') }}</td>
                                <td class="py-2 pr-3">{{ $equipo->tipoEquipo?->nombre }}</td>
                                <td class="py-2 pr-3">{{ $equipo->marca?->nombre }}{{ $equipo->modelo ? ' '.$equipo->modelo : '' }}</td>
                                <td class="py-2 pr-3">
                                    {{ $equipo->asignacionActual?->sede?->nombre ?? '—' }}@if($equipo->asignacionActual?->piso) · {{ __('Piso :numero', ['numero' => $equipo->asignacionActual->piso->numero]) }}@endif
                                </td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('equipos.show', $equipo) }}" class="text-[13px] font-semibold text-primary hover:underline">{{ __('Hoja de vida') }}</a>
                                    <span class="text-ink-muted">·</span>
                                    <a href="{{ route('movimientos.traslado', $equipo) }}" class="text-[13px] font-semibold text-primary hover:underline">{{ __('Trasladar') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-ink-muted">{{ __('Esta persona no tiene equipos a cargo.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <x-ui.table>
        <x-slot name="filters">
            <div class="relative w-full max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="busqueda"
                    placeholder="{{ __('Buscar por nombre, cédula, cargo o dependencia...') }}"
                    class="h-11 w-full rounded-lg border-line-input pl-9 text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                >
            </div>

            <x-ui.button variant="secondary" size="sm" class="ml-auto" wire:click="$toggle('mostrarFormulario')">
                {{ __('Nueva persona') }}
            </x-ui.button>
        </x-slot>

        <thead>
            <tr>
                <th>{{ __('Nombre') }}</th>
                <th>{{ __('Cédula') }}</th>
                <th>{{ __('Cargo') }}</th>
                <th>{{ __('Dependencia') }}</th>
                <th>{{ __('Vinculación') }}</th>
                <th>{{ __('Equipos a cargo') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($personas as $persona)
                <tr wire:key="persona-{{ $persona->id }}" wire:click="seleccionar({{ $persona->id }})" class="cursor-pointer {{ $personaSeleccionada === $persona->id ? 'bg-info-bg' : '' }}">
                    <td class="font-semibold">{{ $persona->nombre }}</td>
                    <td class="font-mono">{{ Cedula::enmascarar($persona->cedula) }}</td>
                    <td>{{ $persona->cargo ?? '—' }}</td>
                    <td>{{ $persona->dependencia?->nombre ?? '—' }}</td>
                    <td>{{ $vinculaciones[$persona->tipo_vinculacion] ?? '—' }}</td>
                    <td>
                        <x-ui.badge :variant="$persona->equipos_a_cargo > 0 ? 'info' : 'neutral'">{{ $persona->equipos_a_cargo }}</x-ui.badge>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-10 text-center text-ink-muted">{{ __('No se encontraron personas con ese criterio de búsqueda.') }}</td>
                </tr>
            @endforelse
        </tbody>

        @if ($personas->hasPages())
            <x-slot name="pagination">
                {{ $personas->links() }}
            </x-slot>
        @endif
    </x-ui.table>
</div>
