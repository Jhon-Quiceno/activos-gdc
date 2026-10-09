{{--
    Vista propia del componente Livewire App\Livewire\Equipos\Crear.

    Se llama "page-crear" (y no "crear") a propósito: la ruta `equipos.crear`
    apunta a la vista 'livewire.equipos.crear' (ver routes/web.php), que es un
    simple wrapper <x-layouts.app-shell> + <livewire:equipos.crear /> (como
    page.blade.php hace para el listado). Si este archivo también se llamara
    "crear.blade.php" chocaría con esa vista. El render() del componente
    referencia este archivo explícitamente.
--}}
<div>
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            {{--
                1. Tipo de equipo: botones compactos, una línea por familia. Al elegir
                un tipo la sección se pliega a una sola línea con «Cambiar»
                (estado solo en el navegador, con Alpine).
            --}}
            <x-ui.card>
                {{-- La clave cambia al pasar de «sin tipo» a «con tipo» (y al limpiar el formulario), así Alpine reinicia el estado. --}}
                <div x-data="{ abierto: @js($tipoEquipoId === null) }" wire:key="selector-tipo-{{ $tipoEquipoId ? 'elegido' : 'vacio' }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="section-title">{{ __('1. Tipo de equipo') }}</p>

                        @if ($tipoSeleccionado = $tiposPorFamilia->flatten()->firstWhere('id', $tipoEquipoId))
                            <div x-show="! abierto" class="flex items-center gap-2 text-[14px]">
                                <x-ui.badge variant="info">{{ $tipoSeleccionado->nombre }}</x-ui.badge>
                                <span class="text-ink-muted">{{ $familiaLabels[$tipoSeleccionado->familia] ?? $tipoSeleccionado->familia }}</span>
                                <button type="button" x-on:click="abierto = true" class="font-semibold text-primary hover:underline">
                                    {{ __('Cambiar') }}
                                </button>
                            </div>
                        @endif
                    </div>

                    <div x-show="abierto" class="mt-3 space-y-2">
                        @foreach ($familiaLabels as $familiaValor => $familiaEtiqueta)
                            @if ($tiposPorFamilia->has($familiaValor))
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="w-[104px] shrink-0 text-[12px] font-semibold uppercase tracking-wide text-ink-muted">{{ $familiaEtiqueta }}</span>
                                    @foreach ($tiposPorFamilia[$familiaValor] as $tipo)
                                        <button
                                            type="button"
                                            wire:key="tipo-{{ $tipo->id }}"
                                            wire:click="seleccionarTipo({{ $tipo->id }})"
                                            x-on:click="abierto = false"
                                            @class([
                                                'h-8 rounded-full border px-3 text-[13px] font-semibold transition-colors duration-150',
                                                'border-primary bg-primary text-white' => $tipoEquipoId === $tipo->id,
                                                'border-line-input bg-white text-ink hover:border-primary hover:text-primary' => $tipoEquipoId !== $tipo->id,
                                            ])
                                        >
                                            {{ $tipo->nombre }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <x-input-error :messages="$errors->get('tipoEquipoId')" class="mt-3" />
            </x-ui.card>

            {{-- 2. Identificación --}}
            <x-ui.card>
                <p class="section-title">{{ __('2. Identificación') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="serial" :label="__('Serial del fabricante') . ' *'" wire:model.blur="serial" required />

                    <div>
                        <x-ui.input
                            name="codigoActivo"
                            :label="__('Código de activo') . ($sinCodigoActivo ? '' : ' *')"
                            wire:model.blur="codigoActivo"
                            placeholder="I1-000000"
                            :disabled="$sinCodigoActivo"
                        />
                        @unless ($sinCodigoActivo)
                            <p class="mt-1 text-[13px] text-ink-muted">{{ __('Formato I1-######. Se corrige solo: «I1 24147» queda «I1-24147».') }}</p>
                        @endunless
                        @if ($otroConMismoCodigo = $this->equipoConMismoCodigo())
                            {{-- RN-03: el código puede repetirse, pero con justificación. --}}
                            <div class="mt-2 rounded-lg bg-warning-bg px-3 py-2 text-[13px] text-warning-text">
                                {{ __('Este código ya está en otro equipo: :tipo con serial :serial. Si es correcto (por ejemplo, un All in One que comparte código con su pantalla), explica por qué.', [
                                    'tipo' => $otroConMismoCodigo->tipoEquipo?->nombre ?? __('equipo'),
                                    'serial' => $otroConMismoCodigo->serial,
                                ]) }}
                            </div>
                            <label for="codigoActivoJustificacion" class="mt-2 block text-[13px] font-semibold text-ink-label">{{ __('Justificación del código repetido') }} *</label>
                            <textarea
                                id="codigoActivoJustificacion"
                                wire:model="codigoActivoJustificacion"
                                rows="2"
                                class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
                            ></textarea>
                            <x-input-error :messages="$errors->get('codigoActivoJustificacion')" class="mt-1" />
                        @endif
                        <label class="mt-2 flex items-center gap-2 text-[13px] text-ink-muted">
                            <input type="checkbox" wire:model.live="sinCodigoActivo" class="rounded border-line-input text-primary focus:ring-primary">
                            {{ __('El equipo no tiene código de activo') }}
                        </label>
                    </div>
                </div>

                <p class="mt-3 text-[13px] text-ink-muted">
                    {{ __('El serial debe ser único. Si ya existe en otro equipo, el sistema lo avisa antes de guardar.') }}
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Marca') }} *</label>
                        <select wire:model="marcaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('marcaId')" class="mt-1" />
                    </div>

                    <x-ui.input name="modelo" :label="__('Modelo')" wire:model="modelo" />
                </div>

                <div class="mt-4 max-w-sm">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Estado de funcionamiento') }} *</label>
                    <select wire:model="estadoFuncionamiento" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Selecciona…') }}</option>
                        <option value="Bueno">{{ __('Bueno') }}</option>
                        <option value="Regular">{{ __('Regular') }}</option>
                        <option value="Malo, requiere cambio de componentes">{{ __('Malo, requiere cambio de componentes') }}</option>
                        <option value="No funcional">{{ __('No funcional') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('estadoFuncionamiento')" class="mt-1" />
                </div>
            </x-ui.card>

            {{-- 3. Propiedad --}}
            <x-ui.card>
                <p class="section-title">{{ __('3. Propiedad') }}</p>

                <div class="mt-3">
                    <x-ui.segmented-control
                        :options="['gobernacion' => __('Gobernación'), 'tercero' => __('Tercero')]"
                        :selected="$propiedad"
                        model="propiedad"
                    />
                </div>

                @if ($propiedad === 'tercero')
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="propietarioTercero" :label="__('Propietario') . ' *'" wire:model="propietarioTercero" required />

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Figura') }} *</label>
                            <select wire:model="figura" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="comodato">{{ __('Comodato') }}</option>
                                <option value="convenio">{{ __('Convenio') }}</option>
                                <option value="proveedor">{{ __('Proveedor') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('figura')" class="mt-1" />
                        </div>
                    </div>
                @endif
            </x-ui.card>

            {{-- 4. Sección condicional según la familia del tipo elegido --}}
            @if ($familiaSeleccionada === 'computo')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Configuración de cómputo') }}</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="procesador" :label="__('Procesador')" wire:model="procesador" />

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Memoria RAM') }}</label>
                            <select wire:model="memoriaRam" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="4GB">4GB</option>
                                <option value="8GB">8GB</option>
                                <option value="16GB">16GB</option>
                                <option value="32GB">32GB</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de disco') }}</label>
                            <select wire:model="tipoDisco" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="SSD">SSD</option>
                                <option value="HDD">HDD</option>
                                <option value="NVMe">NVMe</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Capacidad de disco') }}</label>
                            <select wire:model="capacidadDisco" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="256GB">256GB</option>
                                <option value="512GB">512GB</option>
                                <option value="1TB">1TB</option>
                                <option value="2TB">2TB</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Sistema operativo') }}</label>
                            <select wire:model="sistemaOperativoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($sistemasOperativos as $so)
                                    <option value="{{ $so->id }}">{{ $so->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('¿Tiene antivirus?') }}</label>
                            <select wire:model.live="tieneAntivirus" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="no">{{ __('No') }}</option>
                                <option value="si">{{ __('Sí') }}</option>
                            </select>
                        </div>

                        @if ($tieneAntivirus === 'si')
                            <x-ui.input name="antivirusProducto" :label="__('Producto antivirus')" wire:model="antivirusProducto" />
                        @endif

                        <x-ui.input name="nombreRed" :label="__('Nombre de red')" wire:model="nombreRed" />
                    </div>

                    <div class="mt-5">
                        <p class="text-[13px] font-semibold text-ink-label">{{ __('Periféricos') }}</p>
                        <div class="mt-2 overflow-x-auto rounded-lg border border-line">
                            <table class="min-w-full divide-y divide-line text-[14px]">
                                <thead class="bg-[#F8FAFC]">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-[13px] font-semibold text-ink-muted">{{ __('Periférico') }}</th>
                                        <th class="px-4 py-2.5 text-left text-[13px] font-semibold text-ink-muted">{{ __('¿Tiene?') }}</th>
                                        <th class="px-4 py-2.5 text-left text-[13px] font-semibold text-ink-muted">{{ __('Marca') }}</th>
                                        <th class="px-4 py-2.5 text-left text-[13px] font-semibold text-ink-muted">{{ __('Serial') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($perifericos as $indice => $fila)
                                        <tr wire:key="periferico-{{ $indice }}">
                                            <td class="px-4 py-2.5 text-ink">{{ $fila['nombre'] }}</td>
                                            <td class="px-4 py-2.5">
                                                <input type="checkbox" wire:model.live="perifericos.{{ $indice }}.tiene" class="rounded border-line-input text-primary focus:ring-primary">
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <input
                                                    type="text"
                                                    wire:model="perifericos.{{ $indice }}.marca"
                                                    @disabled(! $fila['tiene'])
                                                    class="h-9 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary disabled:bg-app-bg disabled:text-ink-muted"
                                                >
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <input
                                                    type="text"
                                                    wire:model="perifericos.{{ $indice }}.serial"
                                                    @disabled(! $fila['tiene'])
                                                    class="h-9 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary disabled:bg-app-bg disabled:text-ink-muted"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-ui.card>
            @else
                @include('livewire.equipos.partials.caracteristicas')
            @endif

            {{-- 5. Responsable y ubicación --}}
            <x-ui.card>
                <p class="section-title">{{ __('5. Responsable y ubicación') }}</p>

                <label class="mt-3 flex items-center gap-2 text-[14px] text-ink">
                    <input type="checkbox" wire:model.live="asignarResponsable" class="rounded border-line-input text-primary focus:ring-primary">
                    {{ __('Asignar a un responsable ahora') }}
                </label>

                @if ($asignarResponsable)
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="responsableNombre" :label="__('Responsable') . ' *'" wire:model="responsableNombre" required />
                        <x-ui.input name="responsableCedula" :label="__('Cédula')" wire:model.blur="responsableCedula" inputmode="numeric" />
                        <x-ui.input name="responsableCargo" :label="__('Cargo')" wire:model="responsableCargo" />

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Vinculación') }} *</label>
                            <select wire:model="vinculacion" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="planta">{{ __('Planta') }}</option>
                                <option value="contratista">{{ __('Contratista') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('vinculacion')" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }} *</label>
                            <select wire:model="dependenciaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($dependencias as $dependencia)
                                    <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('dependenciaId')" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Sede') }} *</label>
                            <select wire:model="sedeId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($sedes as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('sedeId')" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }} *</label>
                            <select wire:model="pisoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($pisos as $piso)
                                    <option value="{{ $piso->id }}">{{ __('Piso :numero', ['numero' => $piso->numero]) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pisoId')" class="mt-1" />
                        </div>
                    </div>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Sede de la bodega') }} *</label>
                            <select wire:model="sedeId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($sedes as $sede)
                                    <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('sedeId')" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }} *</label>
                            <select wire:model="pisoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($pisos as $piso)
                                    <option value="{{ $piso->id }}">{{ __('Piso :numero', ['numero' => $piso->numero]) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pisoId')" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }} *</label>
                            <select wire:model="dependenciaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($dependencias as $dependencia)
                                    <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('dependenciaId')" class="mt-1" />
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Observaciones') }}</label>
                    <textarea
                        wire:model="observaciones"
                        rows="3"
                        class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
                    ></textarea>
                </div>
            </x-ui.card>
        </div>

        {{-- Resumen --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-6">
                <x-ui.card>
                    <p class="section-title">{{ __('Resumen') }}</p>
                    <x-ui.definition-list :items="$resumen" class="mt-2" />

                    <div class="mt-4 rounded-lg bg-info-bg p-3 text-[13px] text-info-text">
                        <p class="font-semibold">{{ __('Al guardar') }}</p>
                        <ul class="mt-1 list-disc space-y-1 pl-4">
                            <li>{{ __('Se crea el evento Alta a nombre de :usuario.', ['usuario' => auth()->user()->name]) }}</li>
                            <li>{{ __('Queda Verificado, porque se registra con serial.') }}</li>
                        </ul>
                    </div>

                    <div class="mt-5 space-y-2">
                        <x-ui.button variant="primary" wire:click="guardar" class="w-full justify-center">
                            {{ __('Guardar equipo') }}
                        </x-ui.button>
                        <x-ui.button variant="secondary" wire:click="guardarYRegistrarOtro" class="w-full justify-center">
                            {{ __('Guardar y registrar otro') }}
                        </x-ui.button>
                        <x-ui.button variant="ghost" :href="route('equipos.index')" class="w-full justify-center">
                            {{ __('Cancelar') }}
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</div>
