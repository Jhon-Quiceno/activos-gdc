{{--
    Listado de bajas de un período (RF-27), para la dependencia que tramita la
    baja administrativa. Lo genera BajasListado::exportarPdf(). Oficio
    horizontal, blanco y negro (RNF-13).
--}}
@php
    $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/livewire/movimientos/pdf/logo-gobernacion-horizontal.png')));
    $periodo = ($desde !== '' ? \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') : __('el inicio'))
        .' '.__('al').' '.($hasta !== '' ? \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') : $generadoEl->format('d/m/Y'));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ __('Listado de bajas') }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8.5px; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; text-align: left; }
        th { background: #d9d9d9; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 22%; border-right: none;"><img src="{{ $logo }}" alt="{{ __('Gobernación de Córdoba') }}" style="height: 44px;"></td>
            <td style="border-left: none; text-align: center; font-size: 12px; font-weight: bold;">
                {{ __('LISTADO DE BAJAS DE EQUIPOS TECNOLÓGICOS') }}<br>
                <span style="font-size: 9px; font-weight: normal;">{{ __('Período: :periodo · :count bajas', ['periodo' => $periodo, 'count' => $bajas->count()]) }}</span>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <th>{{ __('Fecha') }}</th>
            <th>{{ __('N.º activo') }}</th>
            <th>{{ __('Equipo') }}</th>
            <th>{{ __('Serial') }}</th>
            <th>{{ __('Código') }}</th>
            <th>{{ __('Motivo') }}</th>
            <th>{{ __('Diagnóstico') }}</th>
            <th>{{ __('Estado') }}</th>
            <th>{{ __('Registró') }}</th>
        </tr>
        @forelse ($bajas as $baja)
            <tr>
                <td>{{ $baja->fecha?->format('d/m/Y') }}</td>
                <td class="mono">{{ $baja->equipo?->numero_activo }}</td>
                <td>{{ trim($baja->equipo?->tipoEquipo?->nombre.' · '.$baja->equipo?->marca?->nombre.' '.$baja->equipo?->modeloEquipo?->nombre) }}</td>
                <td class="mono">{{ $baja->equipo?->serial }}</td>
                <td class="mono">{{ $baja->equipo?->codigo_activo ?? '—' }}</td>
                <td>{{ $baja->diagnostico?->motivoBaja?->nombre ?? '—' }}</td>
                <td>{{ $baja->diagnostico?->causa ?? '—' }}</td>
                <td>{{ \App\Livewire\Movimientos\BajasListado::textoEstado($baja) }}</td>
                <td>{{ $baja->usuario?->name }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align: center;">{{ __('No hay bajas en este período.') }}</td></tr>
        @endforelse
    </table>

    <p style="margin-top: 8px; font-size: 7.5px; color: #444;">
        {{ __('Generado por :usuario el :fecha desde el Sistema de Hoja de Vida de Activos.', ['usuario' => $generadoPor, 'fecha' => $generadoEl->format('d/m/Y H:i')]) }}
    </p>
</body>
</html>
