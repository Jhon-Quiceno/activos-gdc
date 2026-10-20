{{--
    Paz y salvo de equipos (RF-23). Lo genera App\Livewire\Movimientos\Soporte\PazYSalvo::pdf().
    Blanco y negro (RNF-13). El documento va completo porque es un documento
    para firmar (RN-12); la dirección de las sedes no se imprime (RN-28).
--}}
@php
    $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/livewire/movimientos/pdf/logo-gobernacion-horizontal.png')));
    $entregasSinFirmar ??= collect();
    $aPazYSalvo = $equipos->isEmpty() && $entregasSinFirmar->isEmpty();
    $documento = $persona->numero_documento ? (($persona->tipo_documento === 'CC' ? 'C.C.' : $persona->tipo_documento).' '.$persona->numero_documento) : '—';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ __('Paz y salvo de equipos') }} · {{ $consecutivo }}</title>
    @include('livewire.movimientos.pdf.partials.estilos')
</head>
<body style="margin: 0;">
<div class="fmt">
    <img src="{{ $logo }}" alt="{{ __('Gobernación de Córdoba') }}" class="fmt-logo">

    <table>
        <tr>
            <td colspan="3" class="fmt-centro"><span class="fmt-titulo">{{ __('Paz y salvo de equipos tecnológicos') }}</span><br>{{ __('Dirección de Tecnología, Informática y Comunicaciones (TICS)') }}</td>
            <td class="fmt-centro">{{ $consecutivo }}<br>{{ $fecha->format('d/m/Y') }}</td>
        </tr>
        <tr><td colspan="4" class="fmt-seccion">{{ __('Datos del funcionario') }}</td></tr>
        <tr>
            <td class="fmt-label">{{ __('Nombre') }}</td>
            <td>{{ $persona->nombreCompleto() }}</td>
            <td class="fmt-label">{{ __('Documento') }}</td>
            <td class="fmt-mono">{{ $documento }}</td>
        </tr>
        <tr>
            <td class="fmt-label">{{ __('Cargo') }}</td>
            <td>{{ $persona->cargoTexto() ?? '—' }}</td>
            <td class="fmt-label">{{ __('Dependencia') }}</td>
            <td>{{ $persona->dependencia?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td class="fmt-label">{{ __('Vinculación') }}</td>
            <td>{{ __(\App\Models\Persona::VINCULACIONES[$persona->tipo_vinculacion] ?? '—') }}{{ $persona->numero_contrato ? ' · '.__('contrato :n', ['n' => $persona->numero_contrato]) : '' }}</td>
            <td class="fmt-label">{{ __('Fin de la vinculación') }}</td>
            <td>{{ $persona->fecha_fin_vinculacion?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    @if ($aPazYSalvo)
        <p class="fmt-legal">
            {{ __('La Dirección TIC hace constar que :nombre no tiene equipos tecnológicos de la Gobernación de Córdoba a su cargo en el Sistema de Hoja de Vida de Activos, por lo que se encuentra a PAZ Y SALVO por este concepto a la fecha.', ['nombre' => $persona->nombreCompleto()]) }}
        </p>
    @elseif ($equipos->isEmpty())
        <p class="fmt-legal">
            {{ __('NO SE EXPIDE PAZ Y SALVO: :nombre entregó equipos que todavía no ha firmado como entregados. Debe firmar el formato de baja de cada traslado antes de firmar este documento.', ['nombre' => $persona->nombreCompleto()]) }}
        </p>
    @else
        <p class="fmt-legal">
            {{ trans_choice('NO SE EXPIDE PAZ Y SALVO: :nombre tiene :count equipo a su cargo. Debe entregarlo al área de sistemas o trasladarlo a otro responsable antes de firmar este documento.|NO SE EXPIDE PAZ Y SALVO: :nombre tiene :count equipos a su cargo. Debe entregarlos al área de sistemas o trasladarlos a otro responsable antes de firmar este documento.', $equipos->count(), ['nombre' => $persona->nombreCompleto(), 'count' => $equipos->count()]) }}
        </p>

        <table>
            <tr><td colspan="5" class="fmt-seccion">{{ __('Equipos a cargo') }}</td></tr>
            <tr>
                <th>{{ __('N.º activo') }}</th>
                <th>{{ __('Tipo, marca y modelo') }}</th>
                <th>{{ __('Serial') }}</th>
                <th>{{ __('Código de activo') }}</th>
                <th>{{ __('Ubicación') }}</th>
            </tr>
            @foreach ($equipos as $equipo)
                <tr>
                    <td class="fmt-mono">{{ $equipo->numero_activo }}</td>
                    <td>{{ trim($equipo->tipoEquipo?->nombre.' · '.$equipo->marca?->nombre.' '.$equipo->modeloEquipo?->nombre) }}</td>
                    <td class="fmt-mono">{{ $equipo->serial }}</td>
                    <td class="fmt-mono">{{ $equipo->codigo_activo ?? '—' }}</td>
                    <td>{{ trim(($equipo->asignacionActual?->piso?->sede?->nombre ?? '—').' · '.($equipo->asignacionActual?->piso?->etiqueta() ?? '')) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($entregasSinFirmar->isNotEmpty())
        <table>
            <tr><td colspan="4" class="fmt-seccion">{{ __('Entregas pendientes de firma') }}</td></tr>
            <tr>
                <th>{{ __('N.º activo') }}</th>
                <th>{{ __('Tipo, marca y modelo') }}</th>
                <th>{{ __('Serial') }}</th>
                <th>{{ __('Traslado del') }}</th>
            </tr>
            @foreach ($entregasSinFirmar as $evento)
                <tr>
                    <td class="fmt-mono">{{ $evento->equipo?->numero_activo }}</td>
                    <td>{{ trim($evento->equipo?->tipoEquipo?->nombre.' · '.$evento->equipo?->marca?->nombre.' '.$evento->equipo?->modeloEquipo?->nombre) }}</td>
                    <td class="fmt-mono">{{ $evento->equipo?->serial }}</td>
                    <td>{{ $evento->fecha?->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="fmt-firmas">
        <tr>
            <td>
                <div class="fmt-linea-firma">{{ __('Dirección TIC') }}<br>{{ $tecnico?->name }}</div>
            </td>
            <td>
                <div class="fmt-linea-firma">{{ __('Funcionario') }}<br>{{ $persona->nombreCompleto() }} · {{ $documento }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
