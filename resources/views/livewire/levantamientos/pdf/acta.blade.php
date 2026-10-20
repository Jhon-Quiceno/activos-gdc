{{--
    Acta de identificación y aceptación de activos de información (GT-M4 4.1.2,
    RF-59). La genera GestorLevantamientos::acta(). Oficio horizontal, blanco y
    negro (RNF-13). La sede va solo con su nombre: ninguna dirección se imprime,
    así la de una sede reservada (Casa Refugio) nunca sale en un PDF (RN-28).
--}}
@php
    $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/livewire/movimientos/pdf/logo-gobernacion-horizontal.png')));
    $importancias = \App\Models\ValoracionActivo::IMPORTANCIAS;
    $firmante = fn ($persona) => $persona ? trim($persona->nombreCompleto().($persona->cargoTexto() ? ' · '.$persona->cargoTexto() : '')) : '';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ __('Acta de aceptación de activos') }} · {{ $levantamiento->codigo }}</title>
    <style>
        @page { margin: 1.5cm 1.5cm 1.5cm 1.5cm; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td, th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; text-align: left; }
        th { background: #d9d9d9; font-weight: bold; }
        .titulo { font-size: 12px; font-weight: bold; text-transform: uppercase; text-align: center; }
        .etiqueta { background: #f0f0f0; font-weight: bold; width: 14%; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; }
        .centro { text-align: center; }
        .texto { margin: 6px 0 10px; text-align: justify; font-size: 9px; }
        .firmas td { border: none; width: 33%; padding: 0 12px; vertical-align: bottom; }
        .linea { border-top: 1px solid #000; margin-top: 46px; padding-top: 3px; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 22%; border-right: none;"><img src="{{ $logo }}" alt="{{ __('Gobernación de Córdoba') }}" style="height: 46px;"></td>
            <td class="titulo" style="border-left: none;">
                {{ __('Acta de identificación y aceptación de activos de información') }}<br>
                <span style="font-size: 9px; font-weight: normal; text-transform: none;">{{ __('Manual GT-M4 · Proceso Gestión Tecnológica y de Sistemas de Información') }}</span>
            </td>
            <td class="centro" style="width: 16%;">{{ $levantamiento->codigo }}<br>{{ $fecha->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="etiqueta">{{ __('Dependencia') }}</td>
            <td>{{ $levantamiento->dependencia?->ruta() ?? $levantamiento->dependencia?->nombre }}</td>
            <td class="etiqueta">{{ __('Proceso') }}</td>
            <td>{{ $levantamiento->proceso?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="etiqueta">{{ __('Sede') }}</td>
            <td>{{ $levantamiento->sede?->nombre }}</td>
            <td class="etiqueta">{{ __('Inicio del levantamiento') }}</td>
            <td>{{ $levantamiento->fecha_inicio?->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th>{{ __('N.º activo') }}</th>
            <th>{{ __('Activo') }}</th>
            <th>{{ __('Serial') }}</th>
            <th>{{ __('Código') }}</th>
            <th>{{ __('Ubicación') }}</th>
            <th>{{ __('Verificación') }}</th>
            <th class="centro">C</th>
            <th class="centro">I</th>
            <th class="centro">D</th>
            <th class="centro">{{ __('Total') }}</th>
            <th>{{ __('Importancia') }}</th>
            <th>{{ __('Riesgos') }}</th>
        </tr>
        @foreach ($equipos as $equipo)
            @php($v = $equipo->valoracionVigente)
            <tr>
                <td class="mono">{{ $equipo->numero_activo }}</td>
                <td>{{ trim($equipo->tipoEquipo?->nombre.' · '.$equipo->marca?->nombre.' '.$equipo->modeloEquipo?->nombre) }}</td>
                <td class="mono">{{ $equipo->serial }}</td>
                <td class="mono">{{ $equipo->codigo_activo ?? '—' }}</td>
                <td>{{ trim(($equipo->asignacionActual?->dependencia?->nombre ?? '').' · '.($equipo->asignacionActual?->piso?->etiqueta() ?? '')) }}</td>
                <td>{{ $equipo->verificacion === 'verificado' ? __('Verificado') : __('Pendiente') }}</td>
                @if ($v)
                    <td class="centro">{{ $v->confidencialidad }}</td>
                    <td class="centro">{{ $v->integridad }}</td>
                    <td class="centro">{{ $v->disponibilidad }}</td>
                    <td class="centro">{{ $v->total }}</td>
                    <td>{{ __($importancias[$v->importancia] ?? $v->importancia) }}</td>
                    <td>{{ $v->requiere_gestion_riesgo ? __('Sí') : __('No') }}</td>
                @elseif ($equipo->tipoEquipo?->esSoporte())
                    <td colspan="6" class="centro">{{ __('Activo de soporte: no se valora (RN-25)') }}</td>
                @else
                    <td colspan="6" class="centro">{{ __('Sin valoración') }}</td>
                @endif
            </tr>
        @endforeach
    </table>

    <p class="texto">
        {{ trans_choice('El propietario y el custodio aceptan :count activo identificado y valorado en este levantamiento, y se comprometen a mantener actualizado su inventario y a aplicar los controles definidos para preservar su confidencialidad, integridad y disponibilidad (Manual GT-M4, 4.1.2).|El propietario y el custodio aceptan los :count activos identificados y valorados en este levantamiento, y se comprometen a mantener actualizado su inventario y a aplicar los controles definidos para preservar su confidencialidad, integridad y disponibilidad (Manual GT-M4, 4.1.2).', $equipos->count(), ['count' => $equipos->count()]) }}
    </p>

    <table class="firmas">
        <tr>
            <td><div class="linea">{{ __('Propietario del activo (director o líder del proceso)') }}<br>{{ $firmante($levantamiento->propietario) }}</div></td>
            <td><div class="linea">{{ __('Custodio del activo') }}<br>{{ $firmante($levantamiento->custodio) }}</div></td>
            <td><div class="linea">{{ __('Dirección TIC (orientador del SGSI)') }}<br>{{ $tecnico?->name }}</div></td>
        </tr>
    </table>
</body>
</html>
