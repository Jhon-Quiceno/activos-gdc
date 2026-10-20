{{-- Vista de App\Livewire\Levantamientos\Detalle (RF-59, CU-18). --}}
@php
    $estadoEstilos = ['en_curso' => 'info', 'pendiente_de_firma' => 'warning', 'aceptado' => 'success'];
    $importanciaEstilos = ['baja' => 'neutral', 'media' => 'info', 'alta' => 'warning', 'muy_alta' => 'danger'];
    $importancias = \App\Models\ValoracionActivo::IMPORTANCIAS;
    $aceptado = $levantamiento->estado === 'aceptado';
    $claseFiltro = 'h-10 rounded-lg border-line-input text-[13px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div class="space-y-6">
    <p class="text-[13px] text-ink-muted">
        <a href="{{ route('levantamientos.index') }}" wire:navigate class="hover:text-ink">{{ __('Levantamientos') }}</a> / {{ $levantamiento->codigo }}
    </p>

    @if (session('status'))
        <div class="rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">{{ session('status') }}</div>
    @endif
    <x-input-error :messages="$errors->get('equipo')" />

    <x-ui.card>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-display text-[24px] font-semibold text-ink">{{ $levantamiento->codigo }}</h1>
                <p class="text-[14px] text-ink-muted">
                    {{ $levantamiento->dependencia?->ruta() ?? $levantamiento->dependencia?->nombre }} · {{ $levantamiento->sede?->nombre }}
                    @if ($levantamiento->proceso) · {{ $levantamiento->proceso->nombre }} @endif
                </p>
                <p class="mt-1 text-[13px] text-ink-muted">
                    {{ __('Propietario: :p · Custodio: :c', ['p' => $levantamiento->propietario?->nombreCompleto() ?? '—', 'c' => $levantamiento->custodio?->nombreCompleto() ?? '—']) }}
                </p>
            </div>
            <x-ui.badge :variant="$estadoEstilos[$levantamiento->estado] ?? 'neutral'">
                {{ __(\App\Models\LevantamientoActivos::ESTADOS[$levantamiento->estado] ?? $levantamiento->estado) }}
            </x-ui.badge>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.kpi-card :value="$kpis['total']" :label="__('Equipos del levantamiento')" />
        <x-ui.kpi-card :value="$kpis['sin_valoracion']" :label="__('Sin valoración')" accent="warning" />
        <x-ui.kpi-card :value="$kpis['por_verificar']" :label="__('Por verificar')" accent="warning" />
        <x-ui.kpi-card :value="$kpis['valorados_aqui']" :label="__('Valorados en este levantamiento')" accent="success" />
    </div>

    <x-ui.table>
        <x-slot name="filters">
            <select wire:model.live="filtro" class="{{ $claseFiltro }}" aria-label="{{ __('Mostrar') }}">
                <option value="">{{ __('Todos los equipos') }}</option>
                <option value="sin_valoracion">{{ __('Sin valoración') }}</option>
                <option value="por_verificar">{{ __('Por verificar') }}</option>
            </select>
            <span class="ml-auto text-[13px] text-ink-muted">{{ __('Incluye las subdependencias de :d.', ['d' => $levantamiento->dependencia?->nombre]) }}</span>
        </x-slot>

        <thead>
            <tr>
                <th>{{ __('N.º') }}</th>
                <th>{{ __('Equipo') }}</th>
                <th>{{ __('Dependencia · piso') }}</th>
                <th>{{ __('Responsable') }}</th>
                <th>{{ __('Verificación') }}</th>
                <th>{{ __('Valoración') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($equipos as $equipo)
                @php($v = $equipo->valoracionVigente)
                <tr wire:key="lev-equipo-{{ $equipo->id }}">
                    <td class="font-mono text-ink-muted">{{ $equipo->numero_activo }}</td>
                    <td>
                        <a href="{{ route('equipos.show', $equipo) }}" class="font-semibold text-ink hover:text-primary">{{ $equipo->tipoEquipo?->nombre }}</a>
                        <span class="block text-[13px] text-ink-muted">{{ trim($equipo->marca?->nombre.' '.$equipo->modeloEquipo?->nombre) }} · <span class="font-mono">{{ $equipo->serial }}</span></span>
                    </td>
                    <td class="text-[13px]">{{ $equipo->asignacionActual?->dependencia?->nombre }} · {{ $equipo->asignacionActual?->piso?->etiqueta() }}</td>
                    <td class="text-[13px]">{{ $equipo->asignacionActual?->persona?->nombreCompleto() ?? __('Sin asignar') }}</td>
                    <td>
                        @if ($equipo->verificacion === 'verificado')
                            <x-ui.badge variant="success">{{ __('Verificado') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning">{{ __('Por verificar') }}</x-ui.badge>
                        @endif
                    </td>
                    <td>
                        @if ($v)
                            <x-ui.badge :variant="$importanciaEstilos[$v->importancia] ?? 'neutral'">{{ $v->total }} · {{ __($importancias[$v->importancia]) }}</x-ui.badge>
                            @if ($v->levantamiento_id === $levantamiento->id)
                                <span class="block text-[12px] text-ink-muted">{{ __('En este levantamiento') }}</span>
                            @endif
                        @elseif ($equipo->tipoEquipo?->esSoporte())
                            <span class="text-[13px] text-ink-muted">{{ __('Soporte: no se valora') }}</span>
                        @else
                            <x-ui.badge variant="danger">{{ __('Sin valoración') }}</x-ui.badge>
                        @endif
                    </td>
                    <td class="whitespace-nowrap text-right">
                        @unless ($aceptado)
                            @if ($equipo->verificacion !== 'verificado')
                                <x-ui.button variant="ghost" size="sm" wire:click="verificar({{ $equipo->id }})">{{ __('Verificar') }}</x-ui.button>
                            @endif
                            @unless ($equipo->tipoEquipo?->esSoporte())
                                <x-ui.button variant="secondary" size="sm" wire:click="valorar({{ $equipo->id }})">{{ $v ? __('Reclasificar') : __('Valorar') }}</x-ui.button>
                            @endunless
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-ink-muted">{{ __('No hay equipos de esta dependencia en esta sede (o ninguno con ese filtro).') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    {{-- Acta de identificación y aceptación (GT-M4 4.1.2) --}}
    <x-ui.card>
        <p class="section-title">{{ __('Acta de identificación y aceptación') }}</p>

        @if ($aceptado)
            <p class="mt-2 text-[14px] text-ink">
                {{ __('Aceptado el :fecha con el acta firmada.', ['fecha' => $levantamiento->fecha_aceptacion?->format('d/m/Y')]) }}
            </p>
            <x-ui.button variant="secondary" class="mt-3" wire:click="verActaFirmada">{{ __('Descargar acta firmada') }}</x-ui.button>
        @else
            <ol class="mt-2 list-decimal space-y-1 pl-5 text-[14px] text-ink-muted">
                <li>{{ __('Verifica y valora los equipos de la lista.') }}</li>
                <li>{{ __('Descarga el acta prellenada y hazla firmar por el propietario y el custodio.') }}</li>
                <li>{{ __('Sube el acta firmada: el levantamiento queda «Aceptado».') }}</li>
            </ol>

            @if ($kpis['sin_valoracion'] > 0)
                <p class="mt-3 rounded-lg bg-warning-bg px-3 py-2 text-[13px] text-warning-text">
                    {{ trans_choice('Hay :count equipo sin valoración: en el acta aparecerá como «Sin valoración».|Hay :count equipos sin valoración: en el acta aparecerán como «Sin valoración».', $kpis['sin_valoracion'], ['count' => $kpis['sin_valoracion']]) }}
                </p>
            @endif

            <div class="mt-4 flex flex-wrap items-end gap-4">
                <x-ui.button variant="secondary" wire:click="descargarActa" wire:loading.attr="disabled" wire:target="descargarActa">{{ __('Descargar acta prellenada (PDF)') }}</x-ui.button>

                @if ($levantamiento->estado === 'pendiente_de_firma')
                    <div>
                        <label for="actaFirmada" class="text-[13px] font-semibold text-ink-label">{{ __('Acta firmada (PDF, máx. :mb MB)', ['mb' => $maxMb]) }}</label>
                        <input id="actaFirmada" type="file" wire:model="actaFirmada" accept="application/pdf" class="mt-1 block text-[14px]">
                    </div>
                    <x-ui.button variant="primary" wire:click="subirActa" wire:loading.attr="disabled" wire:target="actaFirmada,subirActa">{{ __('Subir acta y aceptar') }}</x-ui.button>
                @endif
            </div>
            <x-input-error :messages="$errors->get('acta')" class="mt-2" />
            <x-input-error :messages="$errors->get('actaFirmada')" class="mt-2" />
        @endif
    </x-ui.card>

    {{-- RF-59: la valoración se hace con el formulario de Equipos, enlazada a este levantamiento. --}}
    @teleport('body')
    <x-ui.modal name="valorar-en-levantamiento" :title="$valorando ? __('Valorar :tipo :serial', ['tipo' => $valorando->tipoEquipo?->nombre, 'serial' => $valorando->serial]) : __('Valorar')">
        @if ($valorando)
            <livewire:equipos.valorar-activo :equipo="$valorando" :levantamiento-id="$levantamiento->id" ventana="valorar-en-levantamiento" :key="'valorar-lev-'.$valorando->id.'-'.($valorando->valoracionVigente?->id ?? 0)" />
        @endif
    </x-ui.modal>
    @endteleport
</div>
