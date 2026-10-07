{{--
    Vista del componente Livewire App\Livewire\Equipos\HojaDeVida (RF-09).
    La página de la ruta `equipos.show` (show.blade.php) solo la envuelve en el layout.
--}}
@php
    $cicloEstilos = [
        'en_servicio' => ['variant' => 'success', 'label' => __('En servicio')],
        'sin_asignar' => ['variant' => 'neutral', 'label' => __('Sin asignar')],
        'dado_de_baja' => ['variant' => 'danger', 'label' => __('Dado de baja')],
    ];
    $ciclo = $cicloEstilos[$equipo->estado_ciclo_vida] ?? ['variant' => 'neutral', 'label' => $equipo->estado_ciclo_vida];

    $verificacionEstilos = [
        'verificado' => ['variant' => 'success', 'label' => __('Verificado')],
        'pendiente_de_verificar' => ['variant' => 'warning', 'label' => __('Pendiente de verificar')],
    ];
    $verificacion = $verificacionEstilos[$equipo->verificacion] ?? ['variant' => 'neutral', 'label' => $equipo->verificacion];

    $propiedadLabel = $equipo->propiedad === 'tercero' ? __('Propiedad: Tercero') : __('Propiedad: Gobernación');

    // RN-10: un equipo dado de baja conserva su hoja de vida pero no admite
    // movimientos nuevos; solo anulación o aclaración.
    $dadoDeBaja = $equipo->estado_ciclo_vida === 'dado_de_baja';

    $asignacion = $equipo->asignacionActual;

    $vinculacionLabels = [
        'planta' => __('Planta'),
        'contratista' => __('Contratista'),
    ];

    $responsableUbicacion = [
        __('Responsable') => $asignacion?->persona?->nombre ?? __('Sin asignar'),
        __('Cédula') => $asignacion?->persona?->cedula ?? '—',
        __('Cargo') => $asignacion?->persona?->cargo ?? '—',
        __('Dependencia') => $asignacion?->dependencia?->nombre ?? $asignacion?->persona?->dependencia?->nombre ?? '—',
        __('Vinculación') => $asignacion?->persona?->tipo_vinculacion
            ? ($vinculacionLabels[$asignacion->persona->tipo_vinculacion] ?? $asignacion->persona->tipo_vinculacion)
            : '—',
        __('Sede') => $asignacion?->sede?->nombre ?? '—',
        __('Piso') => $asignacion?->piso?->numero ? __('Piso :numero', ['numero' => $asignacion->piso->numero]) : '—',
    ];

    $configuracion = $equipo->configuracionComputo;
    $softwareItems = $configuracion ? [
        __('Sistema operativo') => $configuracion->sistemaOperativo?->nombre ?? '—',
        __('Antivirus') => $configuracion->tiene_antivirus
            ? ($configuracion->antivirus_producto ? __('Sí (:producto)', ['producto' => $configuracion->antivirus_producto]) : __('Sí'))
            : __('No'),
        __('Nombre de red') => $configuracion->nombre_red ?? '—',
    ] : [];

    $estadoComponenteEstilos = [
        'bueno' => 'success',
        'regular' => 'warning',
        'dañado' => 'danger',
        'malo' => 'danger',
        'no funcional' => 'danger',
    ];

    $firmaEstilos = [
        'pendiente_de_firma' => ['variant' => 'warning', 'label' => __('Pendiente de firma')],
        'completo' => ['variant' => 'success', 'label' => __('Documentos completos')],
    ];

    $accionLabels = [
        'agregar' => __('Agregar'),
        'cambiar' => __('Cambiar'),
        'quitar' => __('Quitar'),
    ];

    $destinoLabels = [
        'bodega' => __('Bodega'),
        'otro_equipo' => __('Otro equipo'),
        'descarte' => __('Descarte'),
    ];
@endphp

<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">
            {{ session('status') }}
        </div>
    @endif

    <p class="text-[13px] text-ink-muted">
        <a href="{{ route('equipos.index') }}" wire:navigate class="hover:text-ink">{{ __('Equipos') }}</a>
        / {{ $equipo->serial }}
    </p>

    <x-ui.card>
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="min-w-0">
                <div class="flex items-center gap-2 text-[14px] text-ink-muted">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.129V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                    <span>{{ $equipo->tipoEquipo?->nombre }} · {{ $equipo->marca?->nombre }} {{ $equipo->modelo }}</span>
                </div>

                <h1 class="mt-1 font-display text-[26px] font-semibold leading-tight text-ink">
                    {{ $equipo->serial }}
                    @if($equipo->codigo_activo)
                        <span class="text-ink-muted">· {{ $equipo->codigo_activo }}</span>
                    @endif
                </h1>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge>
                    <x-ui.badge :variant="$verificacion['variant']">{{ $verificacion['label'] }}</x-ui.badge>
                    <x-ui.badge variant="neutral">{{ $propiedadLabel }}</x-ui.badge>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @unless($dadoDeBaja)
                    <x-ui.button variant="primary" :href="route('movimientos.traslado', $equipo)">
                        {{ __('Trasladar / reasignar') }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('movimientos.componente', $equipo)">
                        {{ __('Cambio de componente') }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('movimientos.diagnostico', $equipo)">
                        {{ __('Diagnóstico') }}
                    </x-ui.button>
                    <x-ui.button variant="danger" :href="route('movimientos.baja', $equipo)">
                        {{ __('Registrar baja') }}
                    </x-ui.button>
                @endunless
                {{-- TODO: dompdf (RF-33, Fase 2) --}}
                <x-ui.button variant="secondary" type="button">
                    {{ __('Exportar hoja de vida (PDF)') }}
                </x-ui.button>
            </div>
        </div>

        @if($dadoDeBaja)
            <p class="mt-4 rounded-lg bg-danger-bg px-4 py-3 text-[14px] text-danger-text">
                {{ __('Este equipo está dado de baja: conserva su hoja de vida, pero no admite movimientos nuevos. Solo se pueden anular o aclarar eventos.') }}
            </p>
        @endif
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
        <div class="space-y-4">
            <x-ui.card>
                <p class="section-title">{{ __('Responsable y ubicación') }}</p>
                <x-ui.definition-list :items="$responsableUbicacion" class="mt-2" />
            </x-ui.card>

            <x-ui.card>
                <p class="section-title">{{ __('Software') }}</p>
                @if($configuracion)
                    <x-ui.definition-list :items="$softwareItems" class="mt-2" />
                @else
                    <p class="mt-3 text-[14px] text-ink-muted">{{ __('No aplica para este tipo de equipo.') }}</p>
                @endif
            </x-ui.card>

            {{--
                Espacio reservado para la etiqueta QR (RF-08, RF-48; bloque de Manuel).
                Contrato propuesto: reemplazar esta tarjeta por un componente del bloque
                QR que reciba el equipo, p. ej. <livewire:qr.etiqueta-equipo :equipo="$equipo" />,
                y que use solo $equipo->qr_uuid para armar el enlace corto /e/{uuid}
                (RN-17, RN-19), con el botón «Imprimir etiqueta QR».
            --}}
            <x-ui.card>
                <p class="section-title">{{ __('Etiqueta QR') }}</p>
                <div class="mt-3 flex items-center gap-4">
                    <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-lg border-2 border-dashed border-line text-[13px] font-semibold text-ink-muted">
                        QR
                    </div>
                    <p class="text-[13px] text-ink-muted">
                        {{ __('Aquí se mostrará el código QR del equipo y la opción para imprimir su etiqueta.') }}
                    </p>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card :padding="false">
            <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
                <p class="section-title">{{ __('Configuración y componentes') }}</p>
                @unless($dadoDeBaja)
                    <x-ui.button variant="secondary" size="sm" :href="route('movimientos.componente', $equipo)">
                        {{ __('Cambio de componente') }}
                    </x-ui.button>
                @endunless
            </div>

            <x-ui.table>
                <thead>
                    <tr>
                        <th>{{ __('Componente') }}</th>
                        <th>{{ __('Característica') }}</th>
                        <th>{{ __('Marca') }}</th>
                        <th>{{ __('Serial') }}</th>
                        <th>{{ __('Estado') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipo->componentes as $componente)
                        <tr wire:key="componente-{{ $componente->id }}">
                            <td>{{ $componente->tipoComponente?->nombre }}</td>
                            <td>{{ $componente->capacidad_caracteristica ?? '—' }}</td>
                            <td>{{ $componente->marca ?? '—' }}</td>
                            <td class="font-mono">{{ $componente->serial ?? '—' }}</td>
                            <td>
                                @if($componente->estado)
                                    <x-ui.badge :variant="$estadoComponenteEstilos[mb_strtolower($componente->estado)] ?? 'neutral'">
                                        {{ $componente->estado }}
                                    </x-ui.badge>
                                @else
                                    <span class="text-ink-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-ink-muted">
                                {{ __('Este equipo no tiene componentes registrados actualmente.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </x-ui.card>
    </div>

    <x-ui.card>
        <p class="section-title">{{ __('Historial') }}</p>
        <p class="mt-1 text-[13px] text-ink-muted">
            {{ __('Los eventos no se editan; solo se anulan o se aclaran con justificación.') }}
        </p>

        {{--
            Mismo marcado y clases que <x-ui.timeline>, pero con el detalle de cada
            evento y la acción de corrección, que ese componente compartido no admite.
        --}}
        <div class="mt-4">
            @forelse($equipo->eventos as $evento)
                @php
                    $anulacion = $evento->anulaciones->first();
                    $detalleCambio = $evento->cambioComponente;
                    $detalleDiagnostico = $evento->diagnostico;
                    $firma = $firmaEstilos[$evento->estado_firma] ?? null;
                @endphp

                <div class="timeline-item group" wire:key="evento-{{ $evento->id }}">
                    <span @class(['timeline-dot transition-transform duration-200 group-hover:scale-125', '!bg-ink-muted' => $anulacion])></span>

                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-ink-muted">
                                {{ optional($evento->fecha)->translatedFormat('d M Y, H:i') }} · #{{ $evento->id }}
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <p @class(['font-display font-semibold text-ink transition-colors duration-150 group-hover:text-primary', 'line-through text-ink-muted' => $anulacion])>
                                    {{ __(\App\Livewire\Equipos\HojaDeVida::TIPO_LABELS[$evento->tipo] ?? $evento->tipo) }}
                                </p>
                                @if($anulacion)
                                    <x-ui.badge variant="danger">{{ __('Anulado') }}</x-ui.badge>
                                @endif
                                @if($firma)
                                    <x-ui.badge :variant="$firma['variant']">{{ $firma['label'] }}</x-ui.badge>
                                @endif
                            </div>
                        </div>

                        @if($this->admiteCorreccion($evento))
                            <x-ui.button variant="ghost" size="sm" wire:click="abrirCorreccion({{ $evento->id }})">
                                {{ $this->puedeAnularse($evento) ? __('Anular / aclarar') : __('Aclarar') }}
                            </x-ui.button>
                        @endif
                    </div>

                    @if($evento->descripcion)
                        <p @class(['mt-1 text-[14px] text-ink-muted', 'line-through' => $anulacion])>{{ $evento->descripcion }}</p>
                    @endif

                    @if($detalleCambio)
                        <dl class="mt-2 grid grid-cols-1 gap-x-6 gap-y-1 rounded-lg bg-app-bg px-4 py-3 text-[13px] sm:grid-cols-2">
                            <div><dt class="inline font-semibold text-ink-label">{{ __('Acción') }}:</dt> <dd class="inline text-ink">{{ $accionLabels[$detalleCambio->accion] ?? $detalleCambio->accion }}</dd></div>
                            @if($detalleCambio->serial_retirado || $detalleCambio->componenteRetirado)
                                <div><dt class="inline font-semibold text-ink-label">{{ __('Retirado') }}:</dt> <dd class="inline text-ink">{{ $detalleCambio->componenteRetirado?->tipoComponente?->nombre }} <span class="font-mono">{{ $detalleCambio->serial_retirado ?? '—' }}</span></dd></div>
                            @endif
                            @if($detalleCambio->serial_instalado || $detalleCambio->componenteInstalado)
                                <div><dt class="inline font-semibold text-ink-label">{{ __('Instalado') }}:</dt> <dd class="inline text-ink">{{ $detalleCambio->componenteInstalado?->tipoComponente?->nombre }} <span class="font-mono">{{ $detalleCambio->serial_instalado ?? '—' }}</span></dd></div>
                            @endif
                            @if($detalleCambio->destino_retirado)
                                <div><dt class="inline font-semibold text-ink-label">{{ __('Destino de lo retirado') }}:</dt> <dd class="inline text-ink">{{ $destinoLabels[$detalleCambio->destino_retirado] ?? $detalleCambio->destino_retirado }}</dd></div>
                            @endif
                            @if($detalleCambio->motivo)
                                <div class="sm:col-span-2"><dt class="inline font-semibold text-ink-label">{{ __('Motivo') }}:</dt> <dd class="inline text-ink">{{ $detalleCambio->motivo }}</dd></div>
                            @endif
                        </dl>
                    @endif

                    @if($detalleDiagnostico)
                        <dl class="mt-2 grid grid-cols-1 gap-y-1 rounded-lg bg-app-bg px-4 py-3 text-[13px]">
                            <div><dt class="inline font-semibold text-ink-label">{{ __('Estado encontrado') }}:</dt> <dd class="inline text-ink">{{ $detalleDiagnostico->estado_encontrado }}</dd></div>
                            @if($detalleDiagnostico->causa)
                                <div><dt class="inline font-semibold text-ink-label">{{ $detalleDiagnostico->es_baja ? __('Diagnóstico') : __('Causa') }}:</dt> <dd class="inline text-ink">{{ $detalleDiagnostico->causa }}</dd></div>
                            @endif
                            @if($detalleDiagnostico->motivoBaja)
                                <div><dt class="inline font-semibold text-ink-label">{{ __('Motivo de baja') }}:</dt> <dd class="inline text-ink">{{ $detalleDiagnostico->motivoBaja->nombre }}</dd></div>
                            @endif
                            @if($detalleDiagnostico->recomendaciones)
                                <div><dt class="inline font-semibold text-ink-label">{{ __('Recomendaciones') }}:</dt> <dd class="inline text-ink">{{ $detalleDiagnostico->recomendaciones }}</dd></div>
                            @endif
                        </dl>
                    @endif

                    @if($anulacion)
                        <p class="mt-2 text-[13px] text-danger-text">
                            {{ __('Anulado por :usuario el :fecha (evento #:id).', [
                                'usuario' => $anulacion->usuario?->name ?? '—',
                                'fecha' => optional($anulacion->fecha)->translatedFormat('d M Y, H:i'),
                                'id' => $anulacion->id,
                            ]) }}
                        </p>
                    @endif

                    @if($evento->usuario)
                        <p class="mt-1 text-[13px] text-ink-muted">{{ __('Por') }} {{ $evento->usuario->name }}</p>
                    @endif
                </div>
            @empty
                <p class="text-[14px] text-ink-muted">{{ __('Sin eventos registrados.') }}</p>
            @endforelse
        </div>
    </x-ui.card>

    <x-ui.modal name="corregir-evento" :title="__('Corregir evento')">
        @if($eventoSeleccionado)
            <p class="text-[14px] text-ink-muted">
                {{ __('Evento #:id · :tipo · :fecha', [
                    'id' => $eventoSeleccionado->id,
                    'tipo' => __(\App\Livewire\Equipos\HojaDeVida::TIPO_LABELS[$eventoSeleccionado->tipo] ?? $eventoSeleccionado->tipo),
                    'fecha' => optional($eventoSeleccionado->fecha)->translatedFormat('d M Y, H:i'),
                ]) }}
            </p>

            <div class="mt-4">
                @if($this->puedeAnularse($eventoSeleccionado))
                    <x-ui.segmented-control
                        :options="['anulacion' => __('Anular'), 'aclaracion' => __('Aclarar')]"
                        :selected="$modo"
                        model="modo"
                    />
                @else
                    <p class="rounded-lg bg-info-bg px-3 py-2 text-[13px] text-info-text">
                        @if($eventoSeleccionado->tipo === 'baja')
                            {{ __('Una baja se anula desde Movimientos, porque además devuelve el equipo a su estado anterior. Aquí puedes dejar una aclaración.') }}
                        @else
                            {{ __('Este evento cambió datos del equipo, así que no se anula desde aquí: si fue un error, registra el movimiento correcto y deja aquí una aclaración.') }}
                        @endif
                    </p>
                @endif
                <x-input-error :messages="$errors->get('modo')" class="mt-1" />
            </div>

            <p class="mt-4 text-[13px] text-ink-muted">
                @if($modo === 'anulacion')
                    {{ __('El evento quedará marcado como anulado. No se borra: sigue en el historial.') }}
                @else
                    {{ __('Se agrega una nota de corrección al historial. El evento original no cambia.') }}
                @endif
            </p>

            <div class="mt-3">
                <label for="justificacion" class="text-[13px] font-semibold text-ink-label">{{ __('Justificación') }} *</label>
                <textarea
                    id="justificacion"
                    wire:model="justificacion"
                    rows="4"
                    class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
                    placeholder="{{ __('Explica qué estaba mal y cuál es el dato correcto.') }}"
                ></textarea>
                <x-input-error :messages="$errors->get('justificacion')" class="mt-1" />
            </div>
        @endif

        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'corregir-evento')">
                {{ __('Cancelar') }}
            </x-ui.button>
            <x-ui.button :variant="$modo === 'anulacion' ? 'danger' : 'primary'" wire:click="registrarCorreccion">
                {{ $modo === 'anulacion' ? __('Anular evento') : __('Registrar aclaración') }}
            </x-ui.button>
        </x-slot>
    </x-ui.modal>
</div>
