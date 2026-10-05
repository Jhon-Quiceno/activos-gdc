@php
    $equipo = \App\Models\Equipo::query()
        ->with([
            'tipoEquipo',
            'marca',
            'asignacionActual.persona.dependencia',
            'asignacionActual.sede',
            'asignacionActual.piso',
            'asignacionActual.dependencia',
            'configuracionComputo.sistemaOperativo',
            'componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id'),
            'eventos' => fn ($q) => $q->with('usuario')->orderByDesc('fecha'),
        ])
        ->findOrFail($equipo);

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

    $tipoEventoLabels = [
        'alta' => __('Alta'),
        'traslado_responsable' => __('Traslado de responsable'),
        'cambio_componente' => __('Cambio de componente'),
        'diagnostico' => __('Diagnóstico'),
        'baja' => __('Baja'),
        'actualizacion_datos' => __('Actualización de datos'),
        'anulacion_aclaracion' => __('Anulación / aclaración'),
    ];

    $firmaLabels = [
        'pendiente_de_firma' => __('Pendiente de firma'),
        'completo' => __('Documentos completos'),
    ];

    $historial = $equipo->eventos->map(function (\App\Models\Evento $evento) use ($tipoEventoLabels, $firmaLabels) {
        $descripcion = $evento->descripcion;

        if ($evento->estado_firma && $evento->estado_firma !== 'no_aplica') {
            $etiquetaFirma = $firmaLabels[$evento->estado_firma] ?? $evento->estado_firma;
            $descripcion = trim(($descripcion ? $descripcion . ' — ' : '') . $etiquetaFirma);
        }

        return [
            'date' => optional($evento->fecha)->translatedFormat('d M Y, H:i'),
            'type' => $tipoEventoLabels[$evento->tipo] ?? $evento->tipo,
            'description' => $descripcion,
            'author' => $evento->usuario?->name,
        ];
    });
@endphp

<x-layouts.app-shell :title="$equipo->codigo_activo ?? $equipo->serial">
    <div class="space-y-6">
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
                    <x-ui.button variant="primary" :href="route('movimientos.traslado', $equipo)">
                        {{ __('Trasladar / reasignar') }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('movimientos.componente', $equipo)">
                        {{ __('Cambio de componente') }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" :href="route('movimientos.diagnostico', $equipo)">
                        {{ __('Diagnóstico') }}
                    </x-ui.button>
                    @if($equipo->estado_ciclo_vida !== 'dado_de_baja')
                        <x-ui.button variant="danger" :href="route('movimientos.baja', $equipo)">
                            {{ __('Registrar baja') }}
                        </x-ui.button>
                    @endif
                    {{-- TODO: dompdf --}}
                    <x-ui.button variant="secondary" type="button">
                        {{ __('Exportar hoja de vida (PDF)') }}
                    </x-ui.button>
                </div>
            </div>
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
            </div>

            <x-ui.card :padding="false">
                <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
                    <p class="section-title">{{ __('Configuración y componentes') }}</p>
                    <x-ui.button variant="secondary" size="sm" :href="route('movimientos.componente', $equipo)">
                        {{ __('Cambio de componente') }}
                    </x-ui.button>
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
                {{ __('Los eventos no se editan; solo se anulan con justificación.') }}
            </p>

            <div class="mt-4">
                <x-ui.timeline :events="$historial" />
            </div>
        </x-ui.card>
    </div>
</x-layouts.app-shell>
