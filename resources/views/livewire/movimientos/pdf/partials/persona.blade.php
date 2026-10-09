{{--
    Funcionario que firma. La cédula va completa a propósito: RN-12 solo exige
    enmascararla en listados y exportaciones masivas, no en los formatos.
--}}
@php
    $vinculaciones = ['planta' => __('Planta'), 'contratista' => __('Contratista')];
    $dependenciaNombre = $asignacion?->dependencia?->nombre ?? $persona?->dependencia?->nombre;
    $ubicacion = $asignacion?->sede
        ? $asignacion->sede->nombre.($asignacion->piso ? ' · '.__('Piso :numero', ['numero' => $asignacion->piso->numero]) : '')
        : null;
@endphp
<table>
    <tr><td colspan="4" class="fmt-seccion">{{ $tituloPersona }}</td></tr>
    @if ($persona)
        <tr>
            <td class="fmt-label">{{ __('Nombre') }}</td><td>{{ $persona->nombre }}</td>
            <td class="fmt-label">{{ __('Cédula') }}</td><td class="fmt-mono">{{ $persona->cedula ?: '—' }}</td>
        </tr>
        <tr>
            <td class="fmt-label">{{ __('Cargo') }}</td><td>{{ $persona->cargo ?: '—' }}</td>
            <td class="fmt-label">{{ __('Vinculación') }}</td><td>{{ $vinculaciones[$persona->tipo_vinculacion] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="fmt-label">{{ __('Dependencia') }}</td><td>{{ $dependenciaNombre ?: '—' }}</td>
            <td class="fmt-label">{{ __('Ubicación') }}</td><td>{{ $ubicacion ?: '—' }}</td>
        </tr>
    @else
        <tr>
            <td class="fmt-label">{{ __('Responsable') }}</td>
            <td colspan="3">{{ __('Sin responsable (equipo en bodega)') }}@if($ubicacion) · {{ $ubicacion }}@endif</td>
        </tr>
    @endif
</table>
