{{--
    Vista de App\Livewire\Importacion\Index: carga de datos al modelo oficial
    (RF-38 a RF-41, RF-55, §12 del análisis v4.1).
--}}
@php
    $claseSelect = 'h-10 rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
    $ayudas = [
        'equipos' => __('Una fila por equipo con las columnas de la plantilla oficial. Los catálogos (tipo, marca, sede, dependencia…) van como listas desplegables para que no entre texto libre.'),
        'personas' => __('Planta de Talento Humano (Central o Educación). Solo se leen documento, nombre, cargo, grado, dependencia real, jefe, provisión, correo institucional y fecha de retiro: salario, RH, dirección y el resto de columnas se descartan al leer y nunca se guardan (RNF-19).'),
        'valoraciones' => __('Matriz FOR.01 diligenciada por un proceso. Cada fila se cruza por «ID ACTIVO» (número de activo) o código de activo, y se valora con las reglas del Manual GT-M4.'),
        'adquisicion' => __('Archivo de Almacén con código de activo, fecha, valor, proveedor, contrato y factura. Solo se cambia lo que trae el archivo, y cada cambio queda en la hoja de vida con su valor anterior.'),
        'inventario' => __('Excel del inventario 2026 (Microsoft Forms). Se convierte a la plantilla oficial (una fila por equipo) y se carga como cualquier carga de equipos. Puedes descargarlo convertido para corregirlo en Excel antes.'),
    ];
@endphp

<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">{{ session('status') }}</div>
    @endif

    {{-- Tipo de carga --}}
    <div class="flex flex-wrap gap-2" role="tablist">
        @foreach ($tipos as $valor => $etiqueta)
            <button
                type="button"
                role="tab"
                wire:click="$set('tipo', '{{ $valor }}')"
                @class([
                    'h-9 rounded-full border px-4 text-[13px] font-semibold transition-colors duration-150',
                    'border-primary bg-primary text-white' => $tipo === $valor,
                    'border-line-input bg-white text-ink hover:border-primary hover:text-primary' => $tipo !== $valor,
                ])
            >{{ __($etiqueta) }}</button>
        @endforeach
    </div>

    {{-- 1. Archivo --}}
    <x-ui.card>
        <p class="section-title">{{ __('1. Archivo') }}</p>
        <p class="mt-1 text-[14px] text-ink-muted">{{ $ayudas[$tipo] }}</p>

        <div class="mt-4 flex flex-wrap items-end gap-4">
            <div>
                <label for="archivo" class="text-[13px] font-semibold text-ink-label">{{ __('Excel o CSV') }}</label>
                <input id="archivo" type="file" wire:model="archivo" accept=".xlsx,.xls,.csv" class="mt-1 block text-[14px] text-ink">
                <div wire:loading wire:target="archivo" class="mt-1 text-[13px] text-ink-muted">{{ __('Leyendo el archivo…') }}</div>
            </div>

            @if ($tipo === 'personas')
                <div>
                    <label for="planta" class="text-[13px] font-semibold text-ink-label">{{ __('Planta') }}</label>
                    <select id="planta" wire:model.live="planta" class="mt-1 block {{ $claseSelect }}">
                        <option value="central">{{ __('Central') }}</option>
                        <option value="educacion">{{ __('Educación') }}</option>
                    </select>
                </div>
                <div>
                    <label for="ordenNombre" class="text-[13px] font-semibold text-ink-label">{{ __('Si el nombre viene en una sola columna') }}</label>
                    <select id="ordenNombre" wire:model.live="ordenNombre" class="mt-1 block {{ $claseSelect }}">
                        @foreach ($ordenesNombre as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ __($etiqueta) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($tipo === 'equipos')
                <x-ui.button variant="secondary" type="button" wire:click="descargarPlantilla">{{ __('Descargar plantilla oficial') }}</x-ui.button>
            @endif

            @if ($tipo === 'inventario' && $archivo)
                <x-ui.button variant="secondary" type="button" wire:click="descargarInventarioConvertido">{{ __('Descargar convertido a la plantilla') }}</x-ui.button>
            @endif

            @if ($archivo || $resultados)
                <x-ui.button variant="ghost" type="button" wire:click="reiniciar">{{ __('Empezar de nuevo') }}</x-ui.button>
            @endif
        </div>

        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
    </x-ui.card>

    @if ($resumen)
        {{-- Resultado de la simulación o de la carga --}}
        <div @class(['rounded-lg px-4 py-3 text-[14px]', 'bg-info-bg text-info-text' => $simulado, 'bg-success-bg text-success-text' => ! $simulado])>
            <p class="font-semibold">{{ $simulado ? __('Simulación: así quedaría la carga (no se guardó nada).') : __('Carga confirmada.') }}</p>
            <ul class="mt-1 flex flex-wrap gap-x-5">
                @foreach ($resumen as $clave => $cantidad)
                    <li>{{ __(ucfirst(str_replace('_', ' ', $clave))) }}: <span class="font-semibold">{{ $cantidad }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($resultados)
        {{-- 2. Revisión --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            <x-ui.kpi-card :value="$kpis['filas']" :label="$tipo === 'inventario' ? __('Equipos detectados') : __('Filas leídas')" />
            <x-ui.kpi-card :value="$kpis['listas']" :label="__('Se cargan')" accent="success" />
            <x-ui.kpi-card :value="$kpis['actualizaciones']" :label="__('Se actualizan')" accent="info" />
            <x-ui.kpi-card :value="$kpis['advertencias']" :label="__('Con advertencias')" accent="warning" />
            <x-ui.kpi-card :value="$kpis['errores']" :label="__('Con errores (no se cargan)')" accent="danger" />
        </div>

        @if ($tipo === 'inventario')
            <p class="text-[13px] text-ink-muted">
                {{ __(':leidas filas del formulario · :excluidas excluidas por ser equipo personal (RN-01).', ['leidas' => $lectura['leidas'], 'excluidas' => $lectura['excluidas']]) }}
            </p>
        @endif

        @if ($pendientes)
            {{-- RF-39: equivalencias de dependencia que faltan --}}
            <x-ui.card>
                <p class="section-title">{{ __('Dependencias sin equivalencia') }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Estos textos del archivo no coinciden con ninguna dependencia oficial. Elige a cuál corresponden: la equivalencia queda guardada para las próximas cargas y las filas se vuelven a revisar.') }}</p>

                <div class="mt-3 space-y-2">
                    @foreach ($pendientes as $i => $pendiente)
                        <div class="flex flex-wrap items-center gap-2" wire:key="pendiente-{{ $i }}">
                            <span class="min-w-[220px] text-[14px] font-semibold text-ink">«{{ $pendiente['texto'] }}»</span>
                            <span class="text-ink-muted" aria-hidden="true">→</span>
                            <select wire:model="pendientes.{{ $i }}.dependencia_id" class="{{ $claseSelect }} max-w-[360px]" aria-label="{{ __('Dependencia oficial') }}">
                                <option value="">{{ __('Selecciona la dependencia oficial…') }}</option>
                                @foreach ($dependencias as $dependencia)
                                    <option value="{{ $dependencia->id }}">{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $dependencia->profundidad) }}{{ $dependencia->nombre }}</option>
                                @endforeach
                            </select>
                            <x-ui.button variant="secondary" size="sm" type="button" wire:click="guardarEquivalencia({{ $i }})">{{ __('Guardar equivalencia') }}</x-ui.button>
                            <x-input-error :messages="$errors->get('pendientes.'.$i.'.dependencia_id')" class="w-full" />
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <x-ui.table>
            <x-slot name="filters">
                <select wire:model.live="vista" class="{{ $claseSelect }}" aria-label="{{ __('Mostrar') }}">
                    <option value="">{{ __('Mostrar: todas las filas') }}</option>
                    <option value="errores">{{ __('Solo con errores') }}</option>
                    <option value="advertencias">{{ __('Solo con advertencias') }}</option>
                </select>
                <span class="ml-auto text-[13px] text-ink-muted">
                    {{ $totalVisibles > 300 ? __('Se muestran 300 de :total filas.', ['total' => $totalVisibles]) : '' }}
                </span>
            </x-slot>

            <thead>
                <tr>
                    <th class="w-16">{{ __('Fila') }}</th>
                    <th>{{ __('Registro') }}</th>
                    <th class="w-32">{{ __('Estado') }}</th>
                    <th>{{ __('Detalle') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($visibles as $resultado)
                    <tr wire:key="fila-{{ $resultado['numero'] }}-{{ $loop->index }}">
                        <td class="font-mono text-ink-muted">{{ $resultado['numero'] }}</td>
                        <td>
                            {{ $resultado['resumen'] ?: '—' }}
                            @if (! empty($resultado['calculo']))
                                <span class="block text-[12px] text-ink-muted">{{ __('Total e importancia: :c', ['c' => $resultado['calculo']]) }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($resultado['errores'])
                                <x-ui.badge variant="danger">{{ __('No se carga') }}</x-ui.badge>
                            @elseif ($resultado['omitida'] ?? false)
                                <x-ui.badge variant="neutral">{{ __('Se omite') }}</x-ui.badge>
                            @elseif ($resultado['actualizacion'] ?? false)
                                <x-ui.badge variant="info">{{ __('Actualización') }}</x-ui.badge>
                            @elseif ($resultado['advertencias'])
                                <x-ui.badge variant="warning">{{ __('Con advertencias') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="success">{{ __('Lista') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="text-[13px]">
                            <ul class="space-y-0.5">
                                @foreach ($resultado['errores'] as $mensaje)
                                    <li class="text-danger-text">{{ $mensaje }}</li>
                                @endforeach
                                @foreach ($resultado['advertencias'] as $mensaje)
                                    <li class="text-warning-text">{{ $mensaje }}</li>
                                @endforeach
                                @foreach (($resultado['cambios'] ?? []) as $campo => $cambio)
                                    <li class="text-ink">{{ __(\Illuminate\Support\Str::ucfirst(str_replace('_', ' ', $campo))) }}: <span class="text-ink-muted line-through">{{ $cambio['antes'] ?? __('(vacío)') }}</span> → {{ $cambio['despues'] }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-ink-muted">{{ __('Ninguna fila con ese filtro.') }}</td></tr>
                @endforelse
            </tbody>
        </x-ui.table>

        {{-- 3. Simular y confirmar --}}
        <x-ui.card>
            <p class="section-title">{{ __('3. Simular y confirmar') }}</p>
            <p class="mt-1 text-[14px] text-ink-muted">
                {{ __('Solo se cargan las filas sin errores. La simulación hace todo el proceso y lo deshace al final, para que veas el resultado sin guardar nada.') }}
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button variant="secondary" type="button" wire:click="simular" wire:loading.attr="disabled">{{ __('Simular') }}</x-ui.button>
                <x-ui.button variant="primary" type="button" wire:click="confirmar" wire:loading.attr="disabled" wire:confirm="{{ __('¿Cargar las :n filas sin errores?', ['n' => $kpis['listas']]) }}">
                    {{ __('Confirmar carga') }}
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif
</div>
