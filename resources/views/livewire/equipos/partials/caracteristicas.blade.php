{{--
    Tarjeta «4. Características» según la familia del tipo de equipo (RF-03),
    para las familias que no son de cómputo. La usan el registro (page-crear)
    y la edición (page-editar); los campos viven en el trait
    App\Livewire\Equipos\Concerns\CaracteristicasPorFamilia.

    Espera la variable $familiaSeleccionada del componente.
--}}
@if ($familiaSeleccionada === 'video')
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
