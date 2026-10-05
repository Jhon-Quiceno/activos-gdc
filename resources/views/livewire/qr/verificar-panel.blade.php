{{--
    Vista propia del componente Livewire App\Livewire\Qr\VerificarPanel.

    Se llama "verificar-panel" (y no "verificar") por la misma razón que
    usuarios-panel.blade.php: la ruta `qr.verificar` ya usa el nombre de vista
    'livewire.qr.verificar' para el documento HTML completo (ver verificar.blade.php).
--}}
@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
        'dado_de_baja' => ['variant' => 'danger', 'label' => __('Dado de baja')],
    ];

    $verificacionEstilos = [
        'verificado' => ['variant' => 'success', 'label' => __('Verificado')],
        'pendiente_de_verificar' => ['variant' => 'warning', 'label' => __('Pendiente de verificar')],
    ];
@endphp

<div class="min-h-screen bg-app-bg pb-8">
    {{-- Header propio del mockup móvil --}}
    <header class="flex items-center justify-between bg-navy px-4 py-4 text-white">
        <h1 class="text-[17px] font-semibold">{{ __('Verificar en sitio') }}</h1>
        <span class="text-[13px] text-white/80">{{ __('Palacio Naín · P5') }}</span>
    </header>

    <div class="space-y-4 p-4">
        @if (session('status'))
            <div class="rounded-lg bg-success-bg px-4 py-3 text-[13px] text-success-text">
                {{ session('status') }}
            </div>
        @endif

        {{-- Buscador --}}
        <x-ui.card>
            <label class="text-[13px] font-semibold text-ink-label">{{ __('Buscar equipo') }}</label>
            <div class="relative mt-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="busqueda"
                    placeholder="{{ __('Serial o código de activo') }}"
                    class="h-11 w-full rounded-lg border-line-input pl-9 text-[14px] text-ink placeholder:text-ink-muted focus:border-primary focus:ring-primary"
                >
            </div>

            @if (trim($busqueda) !== '' && ! $equipo)
                <p class="mt-2 text-[13px] text-ink-muted">{{ __('No se encontró ningún equipo con ese serial o código de activo.') }}</p>
            @endif
        </x-ui.card>

        @if ($equipo)
            @php
                $asignacion = $equipo->asignacionActual;
                $ciclo = $cicloEstilos[$equipo->estado_ciclo_vida] ?? ['variant' => 'neutral', 'label' => $equipo->estado_ciclo_vida];
                $verif = $verificacionEstilos[$equipo->verificacion] ?? ['variant' => 'neutral', 'label' => $equipo->verificacion];
            @endphp

            {{-- Resultado --}}
            <x-ui.card>
                <p class="font-semibold text-ink">{{ $equipo->tipoEquipo?->nombre }}</p>
                <p class="text-[13px] text-ink-muted">
                    {{ $equipo->marca?->nombre }}
                    @if ($equipo->modelo) · {{ $equipo->modelo }} @endif
                </p>

                <div class="mt-2 flex flex-wrap gap-2">
                    <x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge>
                    <x-ui.badge :variant="$verif['variant']">{{ $verif['label'] }}</x-ui.badge>
                </div>

                <p class="mt-3 text-[13px] text-ink-muted">
                    {{ $equipo->codigo_activo ?? __('Sin código de activo') }}
                    @if ($asignacion?->persona)
                        · {{ $asignacion->persona->nombre }}
                    @endif
                    @if ($asignacion?->dependencia)
                        · {{ $asignacion->dependencia->nombre }}
                    @endif
                    @if (! $asignacion)
                        · {{ __('Sin asignar') }}
                    @endif
                </p>
            </x-ui.card>

            {{-- Formulario rápido --}}
            <x-ui.card>
                <p class="section-title">{{ __('Verificación rápida') }}</p>

                <div class="mt-3 space-y-4">
                    <x-ui.input
                        name="serialFabricante"
                        :label="__('Serial del fabricante') . ' *'"
                        wire:model="serialFabricante"
                        placeholder="{{ __('Escribe o escanea el serial') }}"
                        required
                    />

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('RAM') }}</label>
                            <select wire:model="memoriaRam" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="4GB">4GB</option>
                                <option value="8GB">8GB</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Disco') }}</label>
                            <select wire:model="tipoDisco" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                <option value="HDD 500GB">HDD 500GB</option>
                                <option value="SSD 256GB">SSD 256GB</option>
                            </select>
                        </div>
                    </div>

                    <x-ui.input
                        name="procesador"
                        :label="__('Procesador')"
                        wire:model="procesador"
                        placeholder="{{ __('Ej. Intel Core i3') }}"
                    />

                    <label class="flex items-center gap-2 text-[14px] text-ink">
                        <input type="checkbox" wire:model="responsableCorrecto" class="rounded border-line-input text-primary focus:ring-primary">
                        {{ __('El responsable es el correcto') }}
                    </label>

                    <label class="flex items-center gap-2 text-[14px] text-ink">
                        <input type="checkbox" wire:model="tomarFoto" class="rounded border-line-input text-primary focus:ring-primary">
                        {{ __('Tomar foto de la etiqueta') }}
                    </label>
                </div>
            </x-ui.card>

            <x-ui.button variant="success" wire:click="marcarVerificado" class="w-full justify-center">
                {{ __('Marcar como verificado') }}
            </x-ui.button>
        @endif
    </div>
</div>
