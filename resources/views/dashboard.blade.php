@php
    $totalEquipos = \App\Models\Equipo::count();

    $movimientosMes = \App\Models\Evento::whereMonth('fecha', now()->month)
        ->whereYear('fecha', now()->year)
        ->count();

    $pendientesVerificar = \App\Models\Equipo::where('verificacion', 'pendiente_de_verificar')->count();

    $porcentajePendientes = $totalEquipos > 0
        ? (int) round(($pendientesVerificar / $totalEquipos) * 100)
        : 0;

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
        ['label' => __('Pendientes de verificar'), 'value' => $pendientesVerificar . ' (' . $pctPendientes . '%)', 'percent' => $pctPendientes, 'color' => 'bg-warning-text'],
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
        />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 nav:grid-cols-3">
            <x-ui.kpi-card :value="$totalEquipos" label="{{ __('Equipos registrados') }}" accent="primary">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.129V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card :value="$movimientosMes" label="{{ __('Movimientos del mes') }}" accent="info">
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 3m0 0l-4.5 4.5M21 3H7.5" />
                    </svg>
                </x-slot>
            </x-ui.kpi-card>

            <x-ui.kpi-card
                :value="$pendientesVerificar"
                label="{{ __('Pendientes por verificar') }}"
                :accent="$pendientesVerificar > 0 ? 'warning' : 'success'"
                :progress="$porcentajePendientes"
                :progress-label="__(':pct% del total de equipos', ['pct' => $porcentajePendientes])"
            >
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
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
