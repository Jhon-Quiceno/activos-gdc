@php
    $totalEquipos = \App\Models\Equipo::count();

    $pendientesVerificar = \App\Models\Equipo::where('verificacion', 'pendiente_de_verificar')->count();

    $porcentajePendientes = $totalEquipos > 0
        ? (int) round(($pendientesVerificar / $totalEquipos) * 100)
        : 0;

    // --- KPIs del encabezado (prototipo: Total de equipos / En servicio / Sin asignar / Dados de baja) ---
    $enServicio = \App\Models\Equipo::where('estado_ciclo_vida', 'en_servicio')->count();
    $sinAsignar = \App\Models\Equipo::where('estado_ciclo_vida', 'sin_asignar')->count();
    $dadosDeBaja = \App\Models\Equipo::where('estado_ciclo_vida', 'dado_de_baja')->count();
    $totalPuestos = \App\Models\PuestoTrabajo::count();
    $totalSedes = \App\Models\Sede::count();

    $tipoEventoLabels = [
        'alta' => __('Alta'),
        'traslado_responsable' => __('Traslado de responsable'),
        'cambio_componente' => __('Cambio de componente'),
        'diagnostico' => __('Diagnóstico'),
        'baja' => __('Baja'),
        'actualizacion_datos' => __('Actualización de datos'),
        'anulacion_aclaracion' => __('Anulación / aclaración'),
    ];

    $eventosRecientes = \App\Models\Evento::with(['equipo', 'usuario'])
        ->latest('fecha')
        ->take(5)
        ->get()
        ->map(fn (\App\Models\Evento $evento) => [
            'date' => optional($evento->fecha)->translatedFormat('d M Y, H:i'),
            'type' => trim(($tipoEventoLabels[$evento->tipo] ?? $evento->tipo)
                . ($evento->equipo?->codigo_activo ? ' — ' . $evento->equipo->codigo_activo : '')),
            'description' => $evento->descripcion,
            'author' => $evento->usuario?->name,
        ]);

    // --- Calidad del inventario (RF-37) ---
    $sinCodigoActivo = \App\Models\Equipo::whereNull('codigo_activo')->count();

    $sinResponsable = \App\Models\Equipo::whereDoesntHave('asignacionActual', function ($q) {
        $q->whereNotNull('persona_id');
    })->count();

    $sinCedula = \App\Models\Equipo::whereHas('asignacionActual.persona', function ($q) {
        $q->whereNull('cedula');
    })->count();

    $codigosRepetidos = \App\Models\Equipo::whereNotNull('codigo_activo')
        ->whereIn('codigo_activo', function ($q) {
            $q->select('codigo_activo')->from('equipos')
                ->whereNotNull('codigo_activo')
                ->groupBy('codigo_activo')
                ->havingRaw('COUNT(*) > 1');
        })->count();

    $pctDe = function (int $n) use ($totalEquipos) {
        return $totalEquipos > 0 ? (int) round(($n / $totalEquipos) * 100) : 0;
    };

    $pctPendientes = $pctDe($pendientesVerificar);
    $pctSinCodigo = $pctDe($sinCodigoActivo);
    $pctSinResponsable = $pctDe($sinResponsable);
    $pctSinCedula = $pctDe($sinCedula);
    $pctRepetidos = $pctDe($codigosRepetidos);

    $calidadInventario = [
        ['label' => __('Pendientes de verificar serial'), 'value' => $pendientesVerificar . ' (' . $pctPendientes . '%)', 'percent' => $pctPendientes, 'color' => 'bg-warning-text'],
        ['label' => __('Sin código de activo'), 'value' => $sinCodigoActivo . ' (' . $pctSinCodigo . '%)', 'percent' => $pctSinCodigo, 'color' => 'bg-primary'],
        ['label' => __('Sin responsable'), 'value' => $sinResponsable . ' (' . $pctSinResponsable . '%)', 'percent' => $pctSinResponsable, 'color' => 'bg-primary'],
        ['label' => __('Sin cédula del responsable'), 'value' => $sinCedula . ' (' . $pctSinCedula . '%)', 'percent' => $pctSinCedula, 'color' => 'bg-primary'],
        ['label' => __('Códigos de activo repetidos'), 'value' => $codigosRepetidos . ' (' . $pctRepetidos . '%)', 'percent' => $pctRepetidos, 'color' => 'bg-danger-hover'],
    ];

    // --- Pendientes de firma (RF-30/RF-31) ---
    $faltaPorTipo = [
        'baja' => __('Formato de baja'),
        'traslado_responsable' => __('Formato de entrega'),
        'alta' => __('Formato de entrega'),
    ];

    $pendientesFirmaLista = \App\Models\Evento::with('equipo')
        ->where('estado_firma', 'pendiente_de_firma')
        ->latest('fecha')
        ->take(3)
        ->get();

    // --- Equipos por tipo ---
    $equiposPorTipo = \App\Models\Equipo::query()
        ->select('tipo_equipo_id', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
        ->groupBy('tipo_equipo_id')
        ->with('tipoEquipo:id,nombre')
        ->orderByDesc('total')
        ->take(7)
        ->get();

    $maxPorTipo = $equiposPorTipo->max('total') ?: 1;

    $equiposPorTipo = $equiposPorTipo->map(fn ($fila) => [
        'label' => $fila->tipoEquipo?->nombre ?? __('Sin tipo'),
        'value' => $fila->total,
        'percent' => (int) round(($fila->total / $maxPorTipo) * 100),
        'color' => 'bg-primary',
    ]);

    // --- Equipos por sede (vía la asignación abierta) ---
    $equiposPorSede = \Illuminate\Support\Facades\DB::table('asignaciones')
        ->join('sedes', 'sedes.id', '=', 'asignaciones.sede_id')
        ->whereNull('asignaciones.fecha_fin')
        ->select('sedes.nombre', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
        ->groupBy('sedes.nombre')
        ->orderByDesc('total')
        ->take(7)
        ->get();

    $maxPorSede = $equiposPorSede->max('total') ?: 1;

    $equiposPorSede = $equiposPorSede->map(fn ($fila) => [
        'label' => $fila->nombre,
        'value' => $fila->total,
        'percent' => (int) round(($fila->total / $maxPorSede) * 100),
        'color' => 'bg-success',
    ]);
@endphp

<x-layouts.app-shell title="Inicio">
    <div class="space-y-6">
        <x-ui.page-header
            :title="__('Hola, :name', ['name' => explode(' ', auth()->user()->name)[0]])"
            :subtitle="__('Estado del parque tecnológico al :fecha', ['fecha' => now()->translatedFormat('j \d\e F \d\e Y')])"
        >
            <x-slot name="actions">
                <x-ui.button :href="route('equipos.crear')" variant="primary">
                    <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('Registrar equipo') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 nav:grid-cols-4">
            <x-ui.kpi-card :value="$totalEquipos" label="{{ __('Total de equipos') }}" accent="primary">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.129V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                </x-slot>
                <x-slot name="footer">
                    {{ __(':puestos puestos · :sedes sedes', ['puestos' => $totalPuestos, 'sedes' => $totalSedes]) }}
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card :value="$enServicio" label="{{ __('En servicio') }}" accent="success">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card :value="$sinAsignar" label="{{ __('Sin asignar') }}" accent="warning">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card :value="$dadosDeBaja" label="{{ __('Dados de baja') }}" accent="danger">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>
        </div>

        <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
            <x-ui.card>
                <p class="section-title">{{ __('Calidad del inventario') }}</p>
                <div class="mt-4">
                    <x-ui.bar-list variant="stacked" :items="$calidadInventario" />
                </div>
            </x-ui.card>

            <x-ui.card :padding="false">
                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <p class="section-title">{{ __('Pendientes de firma') }}</p>
                    <a href="{{ route('movimientos.pendientes') }}" wire:navigate class="text-[14px] font-semibold text-primary hover:text-primary-hover">
                        {{ __('Ver todos') }}
                    </a>
                </div>

                @if($pendientesFirmaLista->isEmpty())
                    <p class="px-5 pb-5 text-[14px] text-ink-muted">{{ __('No hay movimientos pendientes de firma.') }}</p>
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th>{{ __('Evento') }}</th>
                                <th>{{ __('Equipo') }}</th>
                                <th>{{ __('Falta') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendientesFirmaLista as $evento)
                                <tr>
                                    <td>{{ $tipoEventoLabels[$evento->tipo] ?? $evento->tipo }}</td>
                                    <td class="font-mono">{{ $evento->equipo?->codigo_activo ?? $evento->equipo?->serial }}</td>
                                    <td>{{ $faltaPorTipo[$evento->tipo] ?? __('Documento firmado') }}</td>
                                    <td class="text-right">
                                        <x-ui.button :href="route('movimientos.pendientes')" size="sm" variant="secondary">{{ __('Subir') }}</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
            <x-ui.card>
                <p class="section-title">{{ __('Equipos por tipo') }}</p>
                <div class="mt-4">
                    <x-ui.bar-list :items="$equiposPorTipo" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <p class="section-title">{{ __('Equipos por sede') }}</p>
                <div class="mt-4">
                    <x-ui.bar-list label-width="170px" :items="$equiposPorSede" />
                </div>
            </x-ui.card>
        </div>

        <x-ui.card :padding="false">
            <div class="border-b border-line px-5 py-4">
                <p class="section-title">{{ __('Actividad reciente') }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Últimos eventos registrados en la hoja de vida de los equipos.') }}</p>
            </div>

            <div class="px-5 py-4">
                <x-ui.timeline :events="$eventosRecientes" />
            </div>
        </x-ui.card>
    </div>
</x-layouts.app-shell>
