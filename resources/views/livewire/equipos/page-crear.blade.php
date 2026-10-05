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
            {{-- 1. Tipo de equipo --}}
            <x-ui.card>
                <p class="section-title">{{ __('1. Tipo de equipo') }}</p>

                @foreach ($familiaLabels as $familiaValor => $familiaEtiqueta)
                    @if ($tiposPorFamilia->has($familiaValor))
                        <div class="mt-4 first:mt-3">
                            <p class="text-[12px] font-semibold uppercase tracking-wide text-ink-muted">{{ $familiaEtiqueta }}</p>
                            <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach ($tiposPorFamilia[$familiaValor] as $tipo)
                                    <x-ui.card
                                        clickable
                                        :accent="$tipoEquipoId === $tipo->id ? 'primary' : null"
                                        wire:click="seleccionarTipo({{ $tipo->id }})"
                                        wire:key="tipo-{{ $tipo->id }}"
                                        class="text-center"
                                    >
                                        <p class="text-[14px] font-semibold text-ink">{{ $tipo->nombre }}</p>
                                    </x-ui.card>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <x-input-error :messages="$errors->get('tipoEquipoId')" class="mt-3" />
            </x-ui.card>

            {{-- 2. Identificación --}}
            <x-ui.card>
                <p class="section-title">{{ __('2. Identificación') }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="serial" :label="__('Serial del fabricante') . ' *'" wire:model="serial" required />

                    <div>
                        <x-ui.input
                            name="codigoActivo"
                            :label="__('Código de activo')"
                            wire:model="codigoActivo"
                            placeholder="I1-000000"
                            :disabled="$sinCodigoActivo"
                        />
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
                                <option value="Comodato">{{ __('Comodato') }}</option>
                                <option value="Convenio">{{ __('Convenio') }}</option>
                                <option value="Proveedor">{{ __('Proveedor') }}</option>
                                <option value="Otra">{{ __('Otra') }}</option>
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
            @elseif ($familiaSeleccionada === 'video')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Características del monitor') }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="tamanoPulgadas" :label="__('Tamaño en pulgadas')" wire:model="tamanoPulgadas" />
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Conexiones') }}</label>
                            <select wire:model="conexionMonitor" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="HDMI">HDMI</option>
                                <option value="VGA">VGA</option>
                                <option value="DisplayPort">DisplayPort</option>
                                <option value="HDMI y VGA">{{ __('HDMI y VGA') }}</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
            @elseif ($familiaSeleccionada === 'impresion')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Características de la impresora') }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Funciones') }}</label>
                            <select wire:model="funcionesImpresora" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="Solo impresión">{{ __('Solo impresión') }}</option>
                                <option value="Multifuncional">{{ __('Multifuncional') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de impresión') }}</label>
                            <select wire:model="tipoImpresion" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="Monocromática">{{ __('Monocromática') }}</option>
                                <option value="Color">{{ __('Color') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Conexión') }}</label>
                            <select wire:model="conexionImpresora" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="Red">{{ __('Red') }}</option>
                                <option value="USB">USB</option>
                                <option value="Wi-Fi">Wi-Fi</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
            @elseif ($familiaSeleccionada === 'digitalizacion')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Características del escáner') }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de escáner') }}</label>
                            <select wire:model="tipoEscaner" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="ADF">ADF</option>
                                <option value="Cama plana">{{ __('Cama plana') }}</option>
                                <option value="Ambos">{{ __('Ambos') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Conexión') }}</label>
                            <select wire:model="conexionEscaner" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="Red">{{ __('Red') }}</option>
                                <option value="USB">USB</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
            @elseif ($familiaSeleccionada === 'energia')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Características del equipo de energía') }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo') }}</label>
                            <select wire:model="tipoEnergia" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="UPS">UPS</option>
                                <option value="Estabilizador">{{ __('Estabilizador') }}</option>
                            </select>
                        </div>
                        <x-ui.input name="capacidadVa" :label="__('Capacidad en VA')" wire:model="capacidadVa" />
                        <x-ui.input name="numTomas" type="number" :label="__('N.° de tomas')" wire:model="numTomas" />
                    </div>
                </x-ui.card>
            @elseif ($familiaSeleccionada === 'conectividad')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Características del equipo de conectividad') }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.input name="numPuertos" type="number" :label="__('N.° de puertos')" wire:model="numPuertos" />
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('¿Administrable?') }}</label>
                            <select wire:model="administrable" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="si">{{ __('Sí') }}</option>
                                <option value="no">{{ __('No') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Velocidad') }}</label>
                            <select wire:model="velocidad" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="100Mbps">100Mbps</option>
                                <option value="1Gbps">1Gbps</option>
                                <option value="10Gbps">10Gbps</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
            @elseif ($familiaSeleccionada === 'proyeccion')
                <x-ui.card>
                    <p class="section-title">{{ __('4. Características del proyector') }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="lumenes" :label="__('Lúmenes')" wire:model="lumenes" />
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Resolución') }}</label>
                            <select wire:model="resolucion" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="1920x1080">1920x1080</option>
                                <option value="1280x800">1280x800</option>
                                <option value="1024x768">1024x768</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>
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
                        <x-ui.input name="responsableCedula" :label="__('Cédula')" wire:model="responsableCedula" />
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
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }}</label>
                            <select wire:model="pisoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($pisos as $piso)
                                    <option value="{{ $piso->id }}">{{ __('Piso :numero', ['numero' => $piso->numero]) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pisoId')" class="mt-1" />
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
