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

    $vinculacionLabels = \App\Models\Persona::VINCULACIONES;

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

    $figuraLabels = \App\Livewire\Equipos\Soporte\TextosEquipo::FIGURAS_TERCERO;

    // Mismas claves que usa la pantalla (hoja-de-vida.blade.php) para
    // `equipos.caracteristicas`, según la familia del tipo de equipo.
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

    $datosEquipo = [];

    if (trim((string) $equipo->descripcion) !== '') {
        $datosEquipo[__('Descripción')] = $equipo->descripcion;
    }

    foreach (($equipo->caracteristicas ?? []) as $clave => $valor) {
        $datosEquipo[$caracteristicaLabels[$clave] ?? $clave] = $valor;
    }

    if ($equipo->propiedad === 'tercero' && $equipo->figura_tercero) {
        $datosEquipo[__('Figura del tercero')] = __($figuraLabels[$equipo->figura_tercero] ?? $equipo->figura_tercero);
    }

    if (trim((string) $equipo->codigo_activo_justificacion) !== '') {
        $datosEquipo[__('Justificación del código de activo')] = $equipo->codigo_activo_justificacion;
    }

    if (trim((string) $equipo->observaciones) !== '') {
        $datosEquipo[__('Observaciones')] = $equipo->observaciones;
    }

    $asignacion = $equipo->asignacionActual;
    $persona = $asignacion?->persona;
    $sede = $asignacion?->piso?->sede;
    $configuracion = $equipo->configuracionComputo;

    // RN-12: solo los últimos 4 dígitos (CC ****6226).
    $documento = $persona?->numero_documento
        ? trim(($persona->tipo_documento ?? '').' '.str_repeat('*', 4).mb_substr($persona->numero_documento, -4))
        : $placeholder;

    $fecha = fn ($valor) => $valor ? $valor->format('d/m/Y H:i') : $placeholder;
    $dia = fn ($valor) => $valor ? $valor->format('d/m/Y') : $placeholder;

    // RF-52: adquisición, con avisos.
    $vidaUtil = $equipo->vida_util_meses ?? $equipo->tipoEquipo?->vida_util_meses;
    $avisos = array_filter([
        $equipo->garantiaPorVencer() ? __('Garantía por vencer') : null,
        $equipo->vidaUtilSuperada() ? __('Vida útil superada') : null,
    ]);

    // RF-56: valoración vigente (GT-M4).
    $valoracion = $equipo->valoraciones->firstWhere('vigente', true);
    $siNo = \App\Livewire\Equipos\ValorarActivo::SI_NO;
    $importancias = \App\Models\ValoracionActivo::IMPORTANCIAS;

    // Logo oficial de la Gobernación (PNG recortado y reducido, junto a esta vista).
    // Va incrustado en base64 para que dompdf no dependa de rutas ni de su chroot.
    $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/livewire/equipos/pdf/logo-gobernacion.png')));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ __('Hoja de vida · :serial', ['serial' => $equipo->serial]) }}</title>
    <style>
        @page { margin: 125px 40px 60px 40px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1C2733; }
        header { position: fixed; top: -105px; left: 0; right: 0; }
        .logo { height: 80px; }
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
                <td style="width: 22%; padding: 4px;"><img src="{{ $logo }}" alt="{{ __('Gobernación de Córdoba') }}" class="logo"></td>
                <td>
                    <span class="titulo">{{ __('Hoja de vida del equipo') }}</span><br>
                    {{ __('Dirección TIC · Gobernación de Córdoba') }}
                </td>
                <td style="width: 26%; text-align: left;">
                    {{ __('N.º de activo: :n', ['n' => $equipo->numero_activo ?? $placeholder]) }}<br>
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
            <td>{{ $equipo->modeloEquipo?->nombre ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Serial') }}</td>
            <td class="mono">{{ $equipo->serial }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('N.º de activo') }}</td>
            <td class="mono">{{ $equipo->numero_activo ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Código de activo') }}</td>
            <td class="mono">{{ $equipo->codigo_activo ?? __('Sin código de activo') }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Propiedad') }}</td>
            <td>
                {{ $equipo->propiedad === 'tercero' ? __('Tercero') : __('Gobernación') }}
                @if($equipo->propiedad === 'tercero' && $equipo->proveedorTercero)
                    ({{ $equipo->proveedorTercero->razon_social }})
                @endif
            </td>
            <td class="etiqueta">{{ __('Proceso') }}</td>
            <td>{{ $equipo->proceso?->nombre ?? $placeholder }}</td>
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
            <td>{{ $persona?->nombreCompleto() ?? __('Sin asignar') }}</td>
            <td class="etiqueta">{{ __('Documento') }}</td>
            <td class="mono">{{ $documento }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Cargo') }}</td>
            <td>{{ $persona?->cargoTexto() ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Vinculación') }}</td>
            <td>{{ $persona?->tipo_vinculacion ? __($vinculacionLabels[$persona->tipo_vinculacion] ?? $persona->tipo_vinculacion) : $placeholder }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Dependencia') }}</td>
            <td>{{ $asignacion?->dependencia?->ruta() ?? $persona?->dependencia?->nombre ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Sede / piso') }}</td>
            <td>
                {{ $sede?->nombre ?? $placeholder }}
                @if($asignacion?->piso)
                    · {{ $asignacion->piso->etiqueta() }}
                @endif
                {{-- RN-28: la dirección de una sede reservada (Casa Refugio) no se imprime. --}}
                @if($sede?->direccionVisible())
                    <br><span class="muted">{{ $sede->direccionVisible() }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Adquisición (RF-52) --}}
    <table>
        <tr><td colspan="4" class="seccion">{{ __('Adquisición') }}</td></tr>
        <tr>
            <td class="etiqueta">{{ __('Forma de ingreso') }}</td>
            <td>{{ $equipo->forma_ingreso ? __(\App\Models\Equipo::FORMAS_INGRESO[$equipo->forma_ingreso] ?? $equipo->forma_ingreso) : $placeholder }}</td>
            <td class="etiqueta">{{ __('Fecha de adquisición') }}</td>
            <td>{{ $dia($equipo->fecha_adquisicion) }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Valor') }}</td>
            <td>{{ \App\Livewire\Equipos\Soporte\TextosEquipo::pesos($equipo->valor_adquisicion) ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Proveedor') }}</td>
            <td>{{ $equipo->proveedor?->razon_social ?? $placeholder }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Contrato / factura') }}</td>
            <td>{{ $equipo->numero_contrato ?? $placeholder }} / {{ $equipo->numero_factura ?? $placeholder }}</td>
            <td class="etiqueta">{{ __('Fin de garantía / vida útil') }}</td>
            <td>
                {{ $dia($equipo->fecha_fin_garantia) }} / {{ $vidaUtil ? __(':n meses', ['n' => $vidaUtil]) : $placeholder }}
                @if($avisos)
                    <br><strong>{{ implode(' · ', $avisos) }}</strong>
                @endif
            </td>
        </tr>
    </table>

    {{-- Valoración vigente (GT-M4, RF-56) --}}
    @unless($equipo->tipoEquipo?->esSoporte())
        <table>
            <tr><td colspan="4" class="seccion">{{ __('Valoración del activo (Manual GT-M4)') }}</td></tr>
            @if($valoracion)
                <tr>
                    <td class="etiqueta">{{ __('C · I · D = total') }}</td>
                    <td>{{ $valoracion->confidencialidad }} · {{ $valoracion->integridad }} · {{ $valoracion->disponibilidad }} = <strong>{{ $valoracion->total }}</strong></td>
                    <td class="etiqueta">{{ __('Importancia') }}</td>
                    <td>
                        <strong>{{ __($importancias[$valoracion->importancia] ?? $valoracion->importancia) }}</strong>
                        @if($valoracion->requiere_gestion_riesgo) · {{ __('pasa a gestión de riesgos') }} @endif
                    </td>
                </tr>
                <tr>
                    <td class="etiqueta">{{ __('Datos personales') }}</td>
                    <td>
                        {{ __($siNo[$valoracion->contiene_datos_personales] ?? $valoracion->contiene_datos_personales) }}
                        @if($valoracion->contiene_datos_personales === 'si')
                            ({{ __(\App\Models\ValoracionActivo::CLASIFICACIONES_DATOS[$valoracion->clasificacion_datos] ?? $valoracion->clasificacion_datos) }})
                        @endif
                    </td>
                    <td class="etiqueta">{{ __('Infraestructura crítica / continuidad') }}</td>
                    <td>{{ __($siNo[$valoracion->infraestructura_critica] ?? '') }} / {{ __($siNo[$valoracion->continuidad_negocio] ?? '') }}</td>
                </tr>
                <tr>
                    <td class="etiqueta">{{ __('Propietario / custodio') }}</td>
                    <td>{{ $valoracion->propietario_cargo }} / {{ $valoracion->custodio_cargo }}</td>
                    <td class="etiqueta">{{ __('Valorado') }}</td>
                    <td>{{ $dia($valoracion->fecha) }} · {{ $valoracion->usuario?->name ?? $placeholder }}</td>
                </tr>
            @else
                <tr><td colspan="4" class="muted">{{ __('Este activo aún no tiene valoración.') }}</td></tr>
            @endif
        </table>
    @endunless

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
                <td class="etiqueta">{{ __('Ofimática') }}</td>
                <td>{{ $configuracion->ofimatica ?? $placeholder }}</td>
                <td class="etiqueta">{{ __('Nombre de red / dominio') }}</td>
                <td>{{ $configuracion->nombre_red ?? $placeholder }} / {{ $configuracion->dominio ?? $placeholder }}</td>
            </tr>
            <tr>
                <td class="etiqueta">{{ __('Dirección IP') }}</td>
                <td class="mono">{{ $configuracion->direccion_ip ?? $placeholder }}</td>
                <td class="etiqueta">{{ __('Dirección MAC') }}</td>
                <td class="mono">{{ $configuracion->direccion_mac ?? $placeholder }}</td>
            </tr>
        </table>
    @endif

    {{-- Características del tipo, figura del tercero, justificación de RN-03 y observaciones --}}
    @if($datosEquipo)
        <table>
            <tr><td colspan="4" class="seccion">{{ __('Datos del equipo') }}</td></tr>
            @foreach(array_chunk(array_keys($datosEquipo), 2) as $par)
                <tr>
                    @foreach($par as $clave)
                        <td class="etiqueta">{{ $clave }}</td>
                        <td>{{ $datosEquipo[$clave] }}</td>
                    @endforeach
                    @if(count($par) === 1)
                        <td class="etiqueta"></td>
                        <td></td>
                    @endif
                </tr>
            @endforeach
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

                    @if($evento->tipo === 'valoracion' && ! empty($evento->valores['despues']))
                        @php($v = $evento->valores['despues'])
                        <div class="detalle">
                            C {{ $v['confidencialidad'] ?? '—' }} · I {{ $v['integridad'] ?? '—' }} · D {{ $v['disponibilidad'] ?? '—' }}
                            = {{ $v['total'] ?? '—' }} ({{ __($importancias[$v['importancia'] ?? ''] ?? ($v['importancia'] ?? '—')) }})
                            @if(! empty($v['requiere_gestion_riesgo'])) · {{ __('pasa a gestión de riesgos') }} @endif
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
