{{--
    Hoja de vida exportable a PDF (RF-33, CU-10). Reporte imprimible, sin firma.

    RF-33 estaba en la Fase 2 (análisis, sección 13; plan, sección 6). Se adelantó
    en la Fase 1 con autorización de Jhon (líder) el 7 de octubre de 2026.

    La genera barryvdh/laravel-dompdf desde App\Livewire\Equipos\HojaDeVida::exportarPdf().
    dompdf no procesa Tailwind ni Vite: por eso esta vista es un HTML aislado con
    estilos en línea. Tamaño carta y legible en blanco y negro (RNF-13). La cédula
    va enmascarada porque es una exportación, no la ficha en pantalla (RN-12).
--}}
@php
    $placeholder = '—';

    $cicloLabels = [
        'en_servicio' => __('En servicio'),
        'sin_asignar' => __('Sin asignar'),
        'dado_de_baja' => __('Dado de baja'),
    ];

    $verificacionLabels = [
        'verificado' => __('Verificado'),
        'pendiente_de_verificar' => __('Pendiente de verificar'),
    ];

    $vinculacionLabels = [
        'planta' => __('Planta'),
        'contratista' => __('Contratista'),
    ];

    $firmaLabels = [
        'pendiente_de_firma' => __('Pendiente de firma'),
        'completo' => __('Documentos completos'),
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

    $asignacion = $equipo->asignacionActual;
    $persona = $asignacion?->persona;
    $configuracion = $equipo->configuracionComputo;

    // RN-12: solo los últimos 4 dígitos (****6226).
    $cedula = $persona?->cedula
        ? str_repeat('*', 4).mb_substr($persona->cedula, -4)
        : $placeholder;

    $fecha = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : $placeholder;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ __('Hoja de vida · :serial', ['serial' => $equipo->serial]) }}</title>
    <style>
        @page { margin: 90px 40px 60px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1C2733; }
        header { position: fixed; top: -70px; left: 0; right: 0; }
        footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #5B6B7C; border-top: 1px solid #999; padding-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        td, th { border: 1px solid #333; padding: 4px 6px; vertical-align: top; text-align: left; }
        .cabecera td { text-align: center; vertical-align: middle; }
        .titulo { font-size: 13px; font-weight: bold; }
        .seccion { background: #1E4D8C; color: #fff; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .etiqueta { background: #EEF2F7; font-weight: bold; color: #3A4856; width: 22%; }
        th { background: #EEF2F7; color: #3A4856; }
        .mono { font-family: "DejaVu Sans Mono", monospace; }
        .muted { color: #5B6B7C; }
        .anulado { text-decoration: line-through; color: #5B6B7C; }
        .marca-anulado { font-weight: bold; color: #000; }
        .detalle { margin-top: 3px; color: #3A4856; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <header>
        <table class="cabecera">
            <tr>
                <td style="width: 22%;" class="muted">{{ __('[LOGO GOBERNACIÓN]') }}</td>
                <td>
                    <span class="titulo">{{ __('Hoja de vida del equipo') }}</span><br>
                    {{ __('Dirección TIC · Gobernación de Córdoba') }}
                </td>
                <td style="width: 26%; text-align: left;">
                    {{ __('Serial: :serial', ['serial' => $equipo->serial]) }}<br>
                    {{ __('Generado: :fecha', ['fecha' => $generadoEl->format('d/m/Y H:i')]) }}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        {{ __('Reporte de consulta sin firma (RF-33). Generado por :usuario el :fecha. La información vigente es la del sistema.', [
            'usuario' => $generadoPor,
            'fecha' => $generadoEl->format('d/m/Y H:i'),
        ]) }}
    </footer>

    {{-- Datos del equipo --}}
    <table>
        <tr><td colspan="4" class="seccion">{{ __('Datos del equipo') }}</td></tr>
        <tr>
            <td class="etiqueta">{{ __('Tipo') }}</td>
            <td>{{ $equipo->tipoEquipo?->nombre ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Marca') }}</td>
            <td>{{ $equipo->marca?->nombre ?? $placeholder }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Modelo') }}</td>
            <td>{{ $equipo->modelo ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Serial') }}</td>
            <td class="mono">{{ $equipo->serial }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Código de activo') }}</td>
            <td class="mono">{{ $equipo->codigo_activo ?? __('Sin código de activo') }}</td>
            <td class="etiqueta">{{ __('Propiedad') }}</td>
            <td>
                {{ $equipo->propiedad === 'tercero' ? __('Tercero') : __('Gobernación') }}
                @if($equipo->propiedad === 'tercero' && $equipo->propietario_tercero)
                    ({{ $equipo->propietario_tercero }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Estado') }}</td>
            <td>{{ $cicloLabels[$equipo->estado_ciclo_vida] ?? $equipo->estado_ciclo_vida }}</td>
            <td class="etiqueta">{{ __('Verificación') }}</td>
            <td>{{ $verificacionLabels[$equipo->verificacion] ?? $equipo->verificacion }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Estado de funcionamiento') }}</td>
            <td colspan="3">{{ $equipo->estado_funcionamiento ?? $placeholder }}</td>
        </tr>
    </table>

    {{-- Responsable y ubicación --}}
    <table>
        <tr><td colspan="4" class="seccion">{{ __('Responsable y ubicación') }}</td></tr>
        <tr>
            <td class="etiqueta">{{ __('Responsable') }}</td>
            <td>{{ $persona?->nombre ?? __('Sin asignar') }}</td>
            <td class="etiqueta">{{ __('Cédula') }}</td>
            <td class="mono">{{ $cedula }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Cargo') }}</td>
            <td>{{ $persona?->cargo ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Vinculación') }}</td>
            <td>{{ $persona?->tipo_vinculacion ? ($vinculacionLabels[$persona->tipo_vinculacion] ?? $persona->tipo_vinculacion) : $placeholder }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Dependencia') }}</td>
            <td>{{ $asignacion?->dependencia?->nombre ?? $persona?->dependencia?->nombre ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Sede / piso') }}</td>
            <td>
                {{ $asignacion?->sede?->nombre ?? $placeholder }}
                @if($asignacion?->piso)
                    · {{ __('Piso :numero', ['numero' => $asignacion->piso->numero]) }}
                @endif
            </td>
        </tr>
    </table>

    {{-- Software (solo cómputo) --}}
    @if($configuracion)
        <table>
            <tr><td colspan="4" class="seccion">{{ __('Software') }}</td></tr>
            <tr>
                <td class="etiqueta">{{ __('Sistema operativo') }}</td>
                <td>{{ $configuracion->sistemaOperativo?->nombre ?? $placeholder }}</td>
                <td class="etiqueta">{{ __('Antivirus') }}</td>
                <td>
                    {{ $configuracion->tiene_antivirus ? __('Sí') : __('No') }}
                    @if($configuracion->tiene_antivirus && $configuracion->antivirus_producto)
                        ({{ $configuracion->antivirus_producto }})
                    @endif
                </td>
            </tr>
            <tr>
                <td class="etiqueta">{{ __('Nombre de red') }}</td>
                <td colspan="3">{{ $configuracion->nombre_red ?? $placeholder }}</td>
            </tr>
        </table>
    @endif

    {{-- Componentes actuales --}}
    <table>
        <tr><td colspan="5" class="seccion">{{ __('Configuración y componentes actuales') }}</td></tr>
        <tr>
            <th>{{ __('Componente') }}</th>
            <th>{{ __('Característica') }}</th>
            <th>{{ __('Marca') }}</th>
            <th>{{ __('Serial') }}</th>
            <th>{{ __('Estado') }}</th>
        </tr>
        @forelse($equipo->componentes as $componente)
            <tr>
                <td>{{ $componente->tipoComponente?->nombre ?? $placeholder }}</td>
                <td>{{ $componente->capacidad_caracteristica ?? $placeholder }}</td>
                <td>{{ $componente->marca ?? $placeholder }}</td>
                <td class="mono">{{ $componente->serial ?? $placeholder }}</td>
                <td>{{ $componente->estado ?? $placeholder }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">{{ __('Este equipo no tiene componentes registrados actualmente.') }}</td></tr>
        @endforelse
    </table>

    {{-- Historial --}}
    <table>
        <tr><td colspan="4" class="seccion">{{ __('Historial de eventos') }}</td></tr>
        <tr>
            <th style="width: 6%;">#</th>
            <th style="width: 16%;">{{ __('Fecha') }}</th>
            <th style="width: 20%;">{{ __('Evento') }}</th>
            <th>{{ __('Descripción') }}</th>
        </tr>
        @forelse($equipo->eventos as $evento)
            @php
                $anulacion = $evento->anulaciones->first();
                $cambio = $evento->cambioComponente;
                $diagnostico = $evento->diagnostico;
            @endphp
            <tr>
                <td>{{ $evento->id }}</td>
                <td>{{ $fecha($evento->fecha) }}</td>
                <td>
                    <span @class(['anulado' => $anulacion])>{{ __(\App\Livewire\Equipos\HojaDeVida::TIPO_LABELS[$evento->tipo] ?? $evento->tipo) }}</span>
                    @if($anulacion)
                        <br><span class="marca-anulado">[{{ __('ANULADO') }}]</span>
                    @endif
                    @if(isset($firmaLabels[$evento->estado_firma]))
                        <br><span class="muted">{{ $firmaLabels[$evento->estado_firma] }}</span>
                    @endif
                </td>
                <td>
                    <span @class(['anulado' => $anulacion])>{{ $evento->descripcion ?? $placeholder }}</span>

                    @if($cambio)
                        <div class="detalle">
                            {{ __('Acción') }}: {{ $accionLabels[$cambio->accion] ?? $cambio->accion }}
                            @if($cambio->serial_retirado) · {{ __('Retirado') }}: <span class="mono">{{ $cambio->serial_retirado }}</span> @endif
                            @if($cambio->serial_instalado) · {{ __('Instalado') }}: <span class="mono">{{ $cambio->serial_instalado }}</span> @endif
                            @if($cambio->destino_retirado) · {{ __('Destino') }}: {{ $destinoLabels[$cambio->destino_retirado] ?? $cambio->destino_retirado }} @endif
                            @if($cambio->motivo)<br>{{ __('Motivo') }}: {{ $cambio->motivo }}@endif
                        </div>
                    @endif

                    @if($diagnostico)
                        <div class="detalle">
                            {{ __('Estado encontrado') }}: {{ $diagnostico->estado_encontrado }}
                            @if($diagnostico->causa)<br>{{ $diagnostico->es_baja ? __('Diagnóstico') : __('Causa') }}: {{ $diagnostico->causa }}@endif
                            @if($diagnostico->motivoBaja)<br>{{ __('Motivo de baja') }}: {{ $diagnostico->motivoBaja->nombre }}@endif
                            @if($diagnostico->recomendaciones)<br>{{ __('Recomendaciones') }}: {{ $diagnostico->recomendaciones }}@endif
                        </div>
                    @endif

                    @if($anulacion)
                        <div class="detalle">{{ __('Anulado por :usuario el :fecha (evento #:id).', [
                            'usuario' => $anulacion->usuario?->name ?? $placeholder,
                            'fecha' => $fecha($anulacion->fecha),
                            'id' => $anulacion->id,
                        ]) }}</div>
                    @endif

                    <div class="muted">{{ __('Por') }} {{ $evento->usuario?->name ?? $placeholder }}</div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">{{ __('Sin eventos registrados.') }}</td></tr>
        @endforelse
    </table>
</body>
</html>
