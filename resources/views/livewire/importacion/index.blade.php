<div class="space-y-6">
    {{-- Wizard de 4 pasos --}}
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($pasos as $numero => $etiqueta)
            @php
                $estado = $numero < $pasoActual ? 'done' : ($numero === $pasoActual ? 'activo' : 'pendiente');

                $chipClasses = match ($estado) {
                    'activo' => 'bg-primary text-white shadow-sm',
                    'done' => 'bg-success-bg text-success-text',
                    default => 'bg-neutral-bg text-ink-muted',
                };
            @endphp

            <button
                type="button"
                wire:click="irAPaso({{ $numero }})"
                class="flex items-center gap-2 rounded-full px-4 py-2 text-[14px] font-semibold transition-colors duration-150 {{ $chipClasses }}"
            >
                @if ($estado === 'done')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                @else
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[12px]
                        {{ $estado === 'activo' ? 'bg-white/20' : 'bg-white' }}">
                        {{ $numero }}
                    </span>
                @endif

                {{ $etiqueta }}
            </button>

            @if (! $loop->last)
                <span class="hidden text-ink-muted sm:inline" aria-hidden="true">&rarr;</span>
            @endif
        @endforeach
    </div>

    @if ($pasoActual === 1)
        <x-ui.card>
            <p class="section-title">{{ __('Archivo cargado') }}</p>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-line bg-app-bg px-4 py-3">
                <div>
                    <p class="font-mono text-[14px] text-ink">INVENTARIO DE LA INFRAESTRUCTURA TECNOLÓGICA 2026 (1-461).xlsx</p>
                    <p class="mt-1 text-[13px] text-ink-muted">{{ __('461 filas · cargado hoy') }}</p>
                </div>

                <x-ui.badge variant="success">{{ __('Cargado') }}</x-ui.badge>
            </div>

            <div class="mt-4">
                <x-ui.button variant="secondary" size="sm" type="button">{{ __('Reemplazar archivo') }}</x-ui.button>
            </div>
        </x-ui.card>
    @elseif ($pasoActual === 2)
        <x-ui.card>
            <p class="section-title">{{ __('Equivalencias') }}</p>
            <p class="mt-1 text-[13px] text-ink-muted">{{ __('Algunos valores del archivo no coinciden exactamente con el catálogo. Confirma o corrige el valor oficial.') }}</p>

            <div class="mt-4">
                @include('livewire.importacion.partials.equivalencias-table')
            </div>
        </x-ui.card>
    @elseif ($pasoActual === 3)
        {{-- KPIs de la importación --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 nav:grid-cols-4">
            <x-ui.kpi-card :value="$kpis['filas_leidas']" label="{{ __('Filas leídas') }}" accent="primary" />
            <x-ui.kpi-card :value="$kpis['equipos_detectados']" label="{{ __('Equipos detectados') }}" accent="info" />
            <x-ui.kpi-card :value="$kpis['con_advertencias']" label="{{ __('Con advertencias') }}" accent="warning" />
            <x-ui.kpi-card :value="$kpis['excluidos']" label="{{ __('Excluidos (personales)') }}" accent="neutral" />
        </div>

        {{-- Aviso de serial pendiente de verificar --}}
        <div class="flex items-start gap-3 rounded-lg border border-warning-text/20 bg-warning-bg px-4 py-3 text-[14px] text-warning-text">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p>
                {{ __('El inventario no trae el serial del equipo principal:') }}
                {{ __('todos los equipos entrarán como') }}
                <strong>{{ __('Pendiente de verificar') }}</strong>
                {{ __('hasta tomarlo en sitio.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
            <x-ui.card :padding="false">
                <div class="px-5 py-4">
                    <p class="section-title">{{ __('Advertencias por fila') }}</p>
                </div>

                <x-ui.table>
                    <thead>
                        <tr>
                            <th>{{ __('Fila') }}</th>
                            <th>{{ __('Valor') }}</th>
                            <th>{{ __('Detalle') }}</th>
                            <th>{{ __('Tipo') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($advertencias as $advertencia)
                            @php
                                $tipo = $tiposAdvertencia[$advertencia['tipo']] ?? ['variant' => 'neutral', 'label' => $advertencia['tipo']];
                            @endphp
                            <tr wire:key="advertencia-{{ $advertencia['fila'] }}-{{ $advertencia['tipo'] }}">
                                <td class="font-mono">{{ $advertencia['fila'] }}</td>
                                <td class="font-mono">{{ $advertencia['valor'] }}</td>
                                <td class="text-ink-muted">{{ $advertencia['detalle'] }}</td>
                                <td><x-ui.badge :variant="$tipo['variant']">{{ $tipo['label'] }}</x-ui.badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </x-ui.card>

            <x-ui.card :padding="false">
                <div class="px-5 py-4">
                    <p class="section-title">{{ __('Equivalencias') }}</p>
                </div>

                @include('livewire.importacion.partials.equivalencias-table')
            </x-ui.card>
        </div>
    @else
        <x-ui.card class="text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-info-bg text-info-text">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>

            <p class="mx-auto mt-4 max-w-md text-[14px] text-ink-muted">
                {{ __('930 equipos quedarán registrados con estado :estado. Este paso guardará la importación definitivamente (función de guardado fuera de alcance en esta versión).', ['estado' => __('Pendiente de verificar')]) }}
            </p>
        </x-ui.card>
    @endif

    {{-- Footer del wizard --}}
    <div class="flex items-center justify-between gap-4">
        <x-ui.button variant="secondary" type="button" wire:click="volver" @disabled($pasoActual === 1)>
            {{ __('Volver') }}
        </x-ui.button>

        <x-ui.button variant="primary" type="button" wire:click="continuar" @disabled($pasoActual === 4)>
            {{ $pasoActual < 4 ? __('Continuar a confirmar') : __('Confirmar importación') }}
        </x-ui.button>
    </div>
</div>
