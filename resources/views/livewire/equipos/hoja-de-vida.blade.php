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
    $persona = $asignacion?->persona;
    $sedeActual = $asignacion?->piso?->sede;

    // RF-18: documento con su tipo (CC, CE, PA…).
    $documento = $persona?->numero_documento
        ? trim(($persona->tipo_documento ?? '').' '.$persona->numero_documento)
        : '—';

    $responsableUbicacion = [
        __('Responsable') => $persona?->nombreCompleto() ?? __('Sin asignar'),
        __('Documento') => $documento,
        __('Cargo') => $persona?->cargoTexto() ?? '—',
        __('Dependencia') => $asignacion?->dependencia?->ruta() ?? $persona?->dependencia?->nombre ?? '—',
        __('Vinculación') => $persona?->tipo_vinculacion
            ? __(\App\Models\Persona::VINCULACIONES[$persona->tipo_vinculacion] ?? $persona->tipo_vinculacion)
            : '—',
        __('Sede') => $sedeActual
            ? $sedeActual->nombre.($sedeActual->direccionVisible() ? ' · '.$sedeActual->direccionVisible() : '')
            : '—',
        __('Piso') => $asignacion?->piso?->etiqueta() ?? '—',
    ];

    $configuracion = $equipo->configuracionComputo;
    $softwareItems = $configuracion ? array_filter([
        __('Sistema operativo') => $configuracion->sistemaOperativo?->nombre ?? '—',
        __('Antivirus') => $configuracion->tiene_antivirus
            ? ($configuracion->antivirus_producto ? __('Sí (:producto)', ['producto' => $configuracion->antivirus_producto]) : __('Sí'))
            : __('No'),
        __('Ofimática') => trim($configuracion->ofimatica.($configuracion->licencia_ofimatica ? ' · '.__('licencia').' '.$configuracion->licencia_ofimatica : '')) ?: null,
        __('Nombre de red') => $configuracion->nombre_red ?? '—',
        __('Dominio') => $configuracion->dominio,
        __('Dirección IP') => $configuracion->direccion_ip,
        __('Dirección MAC') => $configuracion->direccion_mac,
    ], fn ($valor) => $valor !== null) : [];

    // RF-52: adquisición, con avisos de garantía por vencer y vida útil superada.
    $pesos = fn ($valor) => \App\Livewire\Equipos\Soporte\TextosEquipo::pesos($valor);
    $vidaUtil = $equipo->vida_util_meses ?? $equipo->tipoEquipo?->vida_util_meses;
    $adquisicion = array_filter([
        __('Forma de ingreso') => $equipo->forma_ingreso ? __(\App\Models\Equipo::FORMAS_INGRESO[$equipo->forma_ingreso] ?? $equipo->forma_ingreso) : null,
        __('Fecha de adquisición') => $equipo->fecha_adquisicion?->format('d/m/Y'),
        __('Valor') => $pesos($equipo->valor_adquisicion),
        __('Proveedor') => $equipo->proveedor?->razon_social,
        __('N.º de contrato') => $equipo->numero_contrato,
        __('N.º de factura') => $equipo->numero_factura,
        __('Fin de la garantía') => $equipo->fecha_fin_garantia?->format('d/m/Y'),
        __('Vida útil') => $vidaUtil
            ? __(':n meses', ['n' => $vidaUtil]).($equipo->vida_util_meses === null ? ' '.__('(la del tipo)') : '')
            : null,
    ], fn ($valor) => $valor !== null);
    $garantiaPorVencer = $equipo->garantiaPorVencer();
    $garantiaVencida = $equipo->fecha_fin_garantia?->isPast() ?? false;
    $vidaUtilSuperada = $equipo->vidaUtilSuperada();

    // RF-56 a RF-58: valoración del activo (GT-M4).
    $valoracionVigente = $equipo->valoraciones->firstWhere('vigente', true);
    $importanciaEstilos = ['baja' => 'neutral', 'media' => 'info', 'alta' => 'warning', 'muy_alta' => 'danger'];
    $importancias = \App\Models\ValoracionActivo::IMPORTANCIAS;
    $esSoporte = (bool) $equipo->tipoEquipo?->esSoporte();

    $estadoComponenteEstilos = [
        'bueno' => 'success',
        'regular' => 'warning',
        'malo' => 'danger',
        'no_funcional' => 'danger',
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

    // RF-04: mismas opciones que el enum `equipos.figura_tercero`.
    $figuraLabels = \App\Livewire\Equipos\Soporte\TextosEquipo::FIGURAS_TERCERO;

    // Etiquetas de las claves que `equipos.caracteristicas` puede traer según la
    // familia del tipo de equipo (ver Crear::caracteristicas()).
    $caracteristicaLabels = [
        'tamano_pulgadas' => __('Tamaño en pulgadas'),
        'conexion' => __('Conexión'),
        'funciones' => __('Funciones'),
        'tipo_impresion' => __('Tipo de impresión'),
        'tipo_escaner' => __('Tipo de escáner'),
        'tipo' => __('Tipo'),
        'capacidad_va' => __('Capacidad en VA'),
        'numero_tomas' => __('N.° de tomas'),
        'numero_puertos' => __('N.° de puertos'),
        'administrable' => __('¿Administrable?'),
        'velocidad' => __('Velocidad'),
        'lumenes' => __('Lúmenes'),
        'resolucion' => __('Resolución'),
    ];

    // Datos del equipo que no tienen un sitio fijo en la ficha porque dependen
    // de la familia o de cómo se dio de alta: características propias del tipo,
    // figura del tercero (solo si es propiedad de un tercero), justificación de
    // un código de activo duplicado (RN-03) y observaciones libres.
    $datosEquipo = [];

    if (trim((string) $equipo->descripcion) !== '') {
        $datosEquipo[__('Descripción')] = $equipo->descripcion;
    }

    if ($equipo->proceso) {
        $datosEquipo[__('Proceso')] = $equipo->proceso->nombre;
    }

    foreach (($equipo->caracteristicas ?? []) as $clave => $valor) {
        $datosEquipo[$caracteristicaLabels[$clave] ?? $clave] = $valor;
    }

    if ($equipo->propiedad === 'tercero') {
        $datosEquipo[__('Propietario')] = $equipo->proveedorTercero?->razon_social ?? '—';

        if ($equipo->figura_tercero) {
            $datosEquipo[__('Figura del tercero')] = __($figuraLabels[$equipo->figura_tercero] ?? $equipo->figura_tercero);
        }
    }

    if (trim((string) $equipo->codigo_activo_justificacion) !== '') {
        $datosEquipo[__('Justificación del código de activo')] = $equipo->codigo_activo_justificacion;
    }

    if (trim((string) $equipo->observaciones) !== '') {
        $datosEquipo[__('Observaciones')] = $equipo->observaciones;
    }
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
                    <span>{{ $equipo->tipoEquipo?->nombre }} · {{ $equipo->marca?->nombre }} {{ $equipo->modeloEquipo?->nombre }}</span>
                </div>

                <h1 class="mt-1 font-display text-[26px] font-semibold leading-tight text-ink">
                    {{ $equipo->serial }}
                    @if($equipo->codigo_activo)
                        <span class="text-ink-muted">· {{ $equipo->codigo_activo }}</span>
                    @endif
                </h1>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    {{-- RN-27: consecutivo del FOR.01, asignado por el sistema. --}}
                    <x-ui.badge variant="info">{{ __('N.º de activo :n', ['n' => $equipo->numero_activo ?? '—']) }}</x-ui.badge>
                    <x-ui.badge :variant="$ciclo['variant']">{{ $ciclo['label'] }}</x-ui.badge>
                    <x-ui.badge :variant="$verificacion['variant']">{{ $verificacion['label'] }}</x-ui.badge>
                    <x-ui.badge variant="neutral">{{ $propiedadLabel }}</x-ui.badge>
                    @if($valoracionVigente)
                        <x-ui.badge :variant="$importanciaEstilos[$valoracionVigente->importancia] ?? 'neutral'">
                            {{ __('Importancia :nivel', ['nivel' => __($importancias[$valoracionVigente->importancia] ?? $valoracionVigente->importancia)]) }}
                        </x-ui.badge>
                    @endif
                    @if($garantiaPorVencer)
                        <x-ui.badge variant="warning">{{ __('Garantía por vencer') }}</x-ui.badge>
                    @endif
                    @if($vidaUtilSuperada)
                        <x-ui.badge variant="warning">{{ __('Vida útil superada') }}</x-ui.badge>
                    @endif
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
                    <x-ui.button variant="secondary" :href="route('equipos.editar', $equipo)">
                        {{ __('Editar datos') }}
                    </x-ui.button>
                    <x-ui.button variant="danger" :href="route('movimientos.baja', $equipo)">
                        {{ __('Registrar baja') }}
                    </x-ui.button>
                @endunless
                {{-- RF-33: adelantado de la Fase 2 con autorización de Jhon (7 oct 2026). --}}
                <x-ui.button variant="secondary" type="button" wire:click="exportarPdf" wire:loading.attr="disabled" wire:target="exportarPdf">
                    <span wire:loading.remove wire:target="exportarPdf">{{ __('Exportar hoja de vida (PDF)') }}</span>
                    <span wire:loading wire:target="exportarPdf">{{ __('Generando PDF…') }}</span>
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
                <p class="section-title">{{ __('Datos del equipo') }}</p>
                @if($datosEquipo)
                    <x-ui.definition-list :items="$datosEquipo" class="mt-2" />
                @else
                    <p class="mt-3 text-[14px] text-ink-muted">{{ __('Sin datos adicionales registrados.') }}</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <p class="section-title">{{ __('Responsable y ubicación') }}</p>
                <x-ui.definition-list :items="$responsableUbicacion" class="mt-2" />
            </x-ui.card>

            {{-- RF-52: datos de adquisición y avisos. --}}
            <x-ui.card>
                <p class="section-title">{{ __('Adquisición') }}</p>
                @if($garantiaPorVencer)
                    <p class="mt-3 rounded-lg bg-warning-bg px-3 py-2 text-[13px] text-warning-text">
                        {{ __('La garantía vence el :fecha (en :dias días).', ['fecha' => $equipo->fecha_fin_garantia->format('d/m/Y'), 'dias' => (int) now()->startOfDay()->diffInDays($equipo->fecha_fin_garantia)]) }}
                    </p>
                @elseif($garantiaVencida)
                    <p class="mt-3 rounded-lg bg-app-bg px-3 py-2 text-[13px] text-ink-muted">
                        {{ __('Garantía vencida desde el :fecha.', ['fecha' => $equipo->fecha_fin_garantia->format('d/m/Y')]) }}
                    </p>
                @endif
                @if($vidaUtilSuperada)
                    <p class="mt-3 rounded-lg bg-warning-bg px-3 py-2 text-[13px] text-warning-text">
                        {{ __('Superó su vida útil de :meses meses (adquirido el :fecha).', ['meses' => $vidaUtil, 'fecha' => $equipo->fecha_adquisicion->format('d/m/Y')]) }}
                    </p>
                @endif
                @if($adquisicion)
                    <x-ui.definition-list :items="$adquisicion" class="mt-2" />
                @else
                    <p class="mt-3 text-[14px] text-ink-muted">{{ __('Sin datos de adquisición registrados.') }}</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <p class="section-title">{{ __('Software') }}</p>
                @if($configuracion)
                    <x-ui.definition-list :items="$softwareItems" class="mt-2" />
                @else
                    <p class="mt-3 text-[14px] text-ink-muted">{{ __('No aplica para este tipo de equipo.') }}</p>
                @endif
            </x-ui.card>

            {{-- Etiqueta QR del equipo (RF-08, RF-48): componente del bloque QR. --}}
            <livewire:qr.etiqueta-equipo :equipo="$equipo" :key="'qr-'.$equipo->id" />
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
                                    <x-ui.badge :variant="$estadoComponenteEstilos[$componente->estado] ?? 'neutral'">
                                        {{ $componente->estadoTexto() }}
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

    {{-- RF-56 a RF-58: valoración del activo (Manual GT-M4) y su historial. --}}
    <x-ui.card>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="section-title">{{ __('Valoración del activo (GT-M4)') }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Confidencialidad, integridad y disponibilidad de 1 a 4. Cada reclasificación queda en el historial.') }}</p>
            </div>
            @if(! $dadoDeBaja && ! $esSoporte)
                <x-ui.button variant="primary" size="sm" type="button" x-on:click="$dispatch('open-modal', 'valorar-activo')">
                    {{ $valoracionVigente ? __('Reclasificar') : __('Valorar activo') }}
                </x-ui.button>
            @endif
        </div>

        @if($esSoporte)
            <p class="mt-3 rounded-lg bg-info-bg px-3 py-2 text-[13px] text-info-text">
                {{ __('Los activos de soporte (UPS, aires, plantas) no se valoran según el Manual GT-M4 (RN-25).') }}
            </p>
        @elseif($valoracionVigente)
            <div class="mt-4 rounded-lg border border-primary/40 bg-info-bg/40 px-4 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.badge variant="success">{{ __('Vigente') }}</x-ui.badge>
                    <span class="text-[14px] text-ink">
                        {{ __('C :c · I :i · D :d', ['c' => $valoracionVigente->confidencialidad, 'i' => $valoracionVigente->integridad, 'd' => $valoracionVigente->disponibilidad]) }}
                        = <span class="font-display text-[20px] font-semibold">{{ $valoracionVigente->total }}</span>
                    </span>
                    <x-ui.badge :variant="$importanciaEstilos[$valoracionVigente->importancia] ?? 'neutral'">
                        {{ __('Importancia :nivel', ['nivel' => __($importancias[$valoracionVigente->importancia] ?? $valoracionVigente->importancia)]) }}
                    </x-ui.badge>
                    @if($valoracionVigente->requiere_gestion_riesgo)
                        <x-ui.badge variant="danger">{{ __('Pasa a gestión de riesgos') }}</x-ui.badge>
                    @endif
                </div>
                <x-ui.definition-list class="mt-2" :items="array_filter([
                    __('Datos personales') => __(\App\Livewire\Equipos\ValorarActivo::SI_NO[$valoracionVigente->contiene_datos_personales] ?? $valoracionVigente->contiene_datos_personales)
                        .($valoracionVigente->contiene_datos_personales === 'si' ? ' · '.__(\App\Models\ValoracionActivo::CLASIFICACIONES_DATOS[$valoracionVigente->clasificacion_datos] ?? $valoracionVigente->clasificacion_datos) : ''),
                    __('Infraestructura crítica') => __(\App\Livewire\Equipos\ValorarActivo::SI_NO[$valoracionVigente->infraestructura_critica] ?? $valoracionVigente->infraestructura_critica),
                    __('Continuidad del negocio') => __(\App\Livewire\Equipos\ValorarActivo::SI_NO[$valoracionVigente->continuidad_negocio] ?? $valoracionVigente->continuidad_negocio),
                    __('Propietario (cargo)') => $valoracionVigente->propietario_cargo,
                    __('Custodio (cargo)') => $valoracionVigente->custodio_cargo,
                    __('Ubicación') => __(\App\Livewire\Equipos\ValorarActivo::TIPOS_UBICACION[$valoracionVigente->tipo_ubicacion] ?? $valoracionVigente->tipo_ubicacion),
                    __('Retención') => $valoracionVigente->tiempo_retencion,
                    __('Controles de monitoreo') => $valoracionVigente->controles_monitoreo,
                    __('Valorado') => __(':fecha por :usuario', ['fecha' => $valoracionVigente->fecha?->format('d/m/Y'), 'usuario' => $valoracionVigente->usuario?->name ?? '—']),
                ])" />
            </div>
        @else
            <p class="mt-3 text-[14px] text-ink-muted">{{ __('Este activo aún no tiene valoración.') }}</p>
        @endif

        {{-- RF-58: historial de reclasificaciones (la vigente arriba, destacada). --}}
        @if($equipo->valoraciones->count() > 1)
            <div class="mt-4 overflow-x-auto rounded-lg border border-line">
                <table class="min-w-full divide-y divide-line text-[13px]">
                    <thead class="bg-[#F8FAFC]">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-ink-muted">{{ __('Fecha') }}</th>
                            <th class="px-3 py-2 text-center font-semibold text-ink-muted">C</th>
                            <th class="px-3 py-2 text-center font-semibold text-ink-muted">I</th>
                            <th class="px-3 py-2 text-center font-semibold text-ink-muted">D</th>
                            <th class="px-3 py-2 text-center font-semibold text-ink-muted">{{ __('Total') }}</th>
                            <th class="px-3 py-2 text-left font-semibold text-ink-muted">{{ __('Importancia') }}</th>
                            <th class="px-3 py-2 text-left font-semibold text-ink-muted">{{ __('Riesgos') }}</th>
                            <th class="px-3 py-2 text-left font-semibold text-ink-muted">{{ __('Por') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($equipo->valoraciones as $valoracion)
                            <tr wire:key="valoracion-{{ $valoracion->id }}" @class(['bg-info-bg/40 font-semibold' => $valoracion->vigente, 'text-ink-muted' => ! $valoracion->vigente])>
                                <td class="px-3 py-2">
                                    {{ $valoracion->fecha?->format('d/m/Y') }}
                                    @if($valoracion->vigente)
                                        <x-ui.badge variant="success" class="ml-1">{{ __('Vigente') }}</x-ui.badge>
                                    @endif
                                    @if($valoracion->levantamiento)
                                        <span class="block text-[12px] font-normal">{{ __('Desde un levantamiento') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">{{ $valoracion->confidencialidad }}</td>
                                <td class="px-3 py-2 text-center">{{ $valoracion->integridad }}</td>
                                <td class="px-3 py-2 text-center">{{ $valoracion->disponibilidad }}</td>
                                <td class="px-3 py-2 text-center">{{ $valoracion->total }}</td>
                                <td class="px-3 py-2">{{ __($importancias[$valoracion->importancia] ?? $valoracion->importancia) }}</td>
                                <td class="px-3 py-2">{{ $valoracion->requiere_gestion_riesgo ? __('Sí') : __('No') }}</td>
                                <td class="px-3 py-2">{{ $valoracion->usuario?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card>
        <p class="section-title">{{ __('Historial') }}</p>
        <p class="mt-1 text-[13px] text-ink-muted">
            {{ __('Los eventos no se editan; solo se anulan o se aclaran con justificación.') }}
        </p>

        {{--
            Mismo marcado y clases que <x-ui.timeline>, pero con el detalle de cada
            evento y la acción de corrección, que ese componente compartido no admite.
        --}}
        @php
            // Aclaraciones enlazadas a su evento (valores.aclara_evento_id): se
            // muestran debajo del evento que corrigen en vez de sueltas. Las
            // anteriores a este cambio no traen el enlace y siguen apareciendo solas.
            $idsEventos = $equipo->eventos->pluck('id')->flip();
            $aclaracionesPorEvento = $equipo->eventos
                ->filter(fn ($e) => $e->tipo === 'anulacion_aclaracion'
                    && $e->evento_anulado_id === null
                    && isset($e->valores['aclara_evento_id'], $idsEventos[$e->valores['aclara_evento_id']]))
                ->sortBy('id')
                ->groupBy(fn ($e) => $e->valores['aclara_evento_id']);
            $idsAnidados = $aclaracionesPorEvento->flatten()->pluck('id')->flip();
        @endphp

        <div class="mt-4">
            @forelse($equipo->eventos as $evento)
                @continue(isset($idsAnidados[$evento->id]))
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

                        <div class="flex flex-wrap items-center gap-1">
                            @if(\App\Livewire\Equipos\HojaDeVida::tieneFormatos($evento))
                                <x-ui.button variant="ghost" size="sm" wire:click="verFormatos({{ $evento->id }})">
                                    {{ __('Formatos y firmas') }}
                                </x-ui.button>
                            @endif
                            @if($this->admiteCorreccion($evento))
                                <x-ui.button variant="ghost" size="sm" wire:click="abrirCorreccion({{ $evento->id }})">
                                    {{ $this->puedeAnularse($evento) ? __('Anular / aclarar') : __('Aclarar') }}
                                </x-ui.button>
                            @endif
                        </div>
                    </div>

                    @if($evento->descripcion)
                        <p @class(['mt-1 text-[14px] text-ink-muted', 'line-through' => $anulacion])>{{ $evento->descripcion }}</p>
                    @endif

                    {{-- RF-12: valor anterior y nuevo de una actualización de datos. --}}
                    @if($evento->tipo === 'actualizacion_datos' && ! empty($evento->valores['despues']))
                        <dl class="mt-2 space-y-1 rounded-lg bg-app-bg px-4 py-3 text-[13px]">
                            @foreach($evento->valores['despues'] as $campo => $nuevo)
                                <div>
                                    <dt class="inline font-semibold text-ink-label">{{ __(\App\Livewire\Equipos\Editar::ETIQUETAS[$campo] ?? $campo) }}:</dt>
                                    <dd class="inline text-ink">
                                        <span class="text-ink-muted line-through">{{ $evento->valores['antes'][$campo] ?? __('(vacío)') }}</span>
                                        → {{ $nuevo ?? __('(vacío)') }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    {{-- RF-58: valoración o reclasificación, con la anterior si la había. --}}
                    @if($evento->tipo === 'valoracion' && ! empty($evento->valores['despues']))
                        @php
                            $textoValoracion = fn (?array $v) => $v
                                ? __('C :c · I :i · D :d = :total (:importancia)', [
                                    'c' => $v['confidencialidad'] ?? '—',
                                    'i' => $v['integridad'] ?? '—',
                                    'd' => $v['disponibilidad'] ?? '—',
                                    'total' => $v['total'] ?? '—',
                                    'importancia' => __($importancias[$v['importancia'] ?? ''] ?? ($v['importancia'] ?? '—')),
                                ]).(! empty($v['requiere_gestion_riesgo']) ? ' · '.__('pasa a gestión de riesgos') : '')
                                : null;
                        @endphp
                        <dl class="mt-2 space-y-1 rounded-lg bg-app-bg px-4 py-3 text-[13px]">
                            @if(! empty($evento->valores['antes']))
                                <div><dt class="inline font-semibold text-ink-label">{{ __('Antes') }}:</dt> <dd class="inline text-ink-muted line-through">{{ $textoValoracion($evento->valores['antes']) }}</dd></div>
                            @endif
                            <div><dt class="inline font-semibold text-ink-label">{{ ! empty($evento->valores['antes']) ? __('Ahora') : __('Valoración') }}:</dt> <dd class="inline text-ink">{{ $textoValoracion($evento->valores['despues']) }}</dd></div>
                        </dl>
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

                    @foreach($aclaracionesPorEvento->get($evento->id, []) as $aclaracion)
                        <div class="mt-2 rounded-lg border-l-4 border-info-text bg-info-bg px-3 py-2 text-[13px]" wire:key="aclaracion-{{ $aclaracion->id }}">
                            <p class="font-semibold text-info-text">{{ __('Aclaración') }}</p>
                            <p class="text-ink">{{ $aclaracion->valores['texto'] ?? $aclaracion->descripcion }}</p>
                            <p class="mt-1 text-ink-muted">
                                {{ __(':usuario · :fecha · evento #:id', [
                                    'usuario' => $aclaracion->usuario?->name ?? '—',
                                    'fecha' => optional($aclaracion->fecha)->translatedFormat('d M Y, H:i'),
                                    'id' => $aclaracion->id,
                                ]) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="text-[14px] text-ink-muted">{{ __('Sin eventos registrados.') }}</p>
            @endforelse
        </div>
    </x-ui.card>

    {{--
        Las ventanas se «teletransportan» al <body>: el <main> del layout tiene una
        transformación (animación de entrada) y, dentro de un elemento transformado,
        position:fixed queda atado a él en vez de a la pantalla.
    --}}
    @teleport('body')
    {{-- Valoración GT-M4: componente reutilizable (también lo usa el levantamiento, RF-59). --}}
    <x-ui.modal name="valorar-activo" :title="$valoracionVigente ? __('Reclasificar el activo') : __('Valorar el activo')" max-width="2xl">
        @if(! $dadoDeBaja)
            <livewire:equipos.valorar-activo :equipo="$equipo" :key="'valorar-'.$equipo->id.'-'.($valoracionVigente?->id ?? 0)" />
        @endif
    </x-ui.modal>
    @endteleport

    @teleport('body')
    {{-- Formatos para firmar y documentos firmados del evento (panel de Movimientos). --}}
    <x-ui.modal name="formatos-evento" :title="__('Formatos y firmas')">
        @if($eventoFormatosId)
            <livewire:movimientos.documentos-evento :evento-id="$eventoFormatosId" :key="'docs-hoja-'.$eventoFormatosId" />
        @endif
    </x-ui.modal>
    @endteleport

    @teleport('body')
    <x-ui.modal name="corregir-evento" :title="__('Corregir evento')">
        @if($eventoSeleccionado)
            <p class="text-[14px] text-ink-muted">
                {{ __('Evento #:id · :tipo · :fecha', [
                    'id' => $eventoSeleccionado->id,
                    'tipo' => __(\App\Livewire\Equipos\HojaDeVida::TIPO_LABELS[$eventoSeleccionado->tipo] ?? $eventoSeleccionado->tipo),
                    'fecha' => optional($eventoSeleccionado->fecha)->translatedFormat('d M Y, H:i'),
                ]) }}
            </p>

            {{-- Los eventos son inmutables (RF-12, RN-06): nunca se editan ni se borran. --}}
            <div class="mt-3 rounded-lg border border-line px-3 py-2 text-[13px] text-ink-muted">
                <p>{{ __('Los eventos del historial no se pueden editar ni borrar. Para corregir uno:') }}</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-4">
                    <li><span class="font-semibold text-ink">{{ __('Aclarar') }}</span>: {{ __('el evento sigue valiendo, pero queda debajo una nota con la corrección (por ejemplo, el motivo correcto de un traslado).') }}</li>
                    <li><span class="font-semibold text-ink">{{ __('Anular') }}</span>: {{ __('el evento queda tachado y sin efecto. Solo para eventos que no cambiaron datos del equipo, como un diagnóstico.') }}</li>
                </ul>
            </div>

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
                    {{ __('La nota queda debajo de este evento en el historial. El evento original no cambia.') }}
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
    @endteleport
</div>
