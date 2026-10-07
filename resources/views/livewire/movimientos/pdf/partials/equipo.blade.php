{{-- Datos de un equipo y, si es de cómputo, sus componentes actuales. --}}
@php
    $propiedad = $equipo->propiedad === 'tercero'
        ? __('Tercero').($equipo->propietario_tercero ? ' ('.$equipo->propietario_tercero.')' : '')
        : __('Gobernación');
    $componentes = $equipo->relationLoaded('componentes')
        ? $equipo->componentes->whereNull('fecha_retiro')
        : collect();
@endphp
<table class="fmt-bloque">
    <tr><td colspan="4" class="fmt-seccion">{{ $tituloEquipo ?? __('Datos del equipo') }}</td></tr>
    <tr>
        <td class="fmt-label">{{ __('Tipo') }}</td><td>{{ $equipo->tipoEquipo?->nombre }}</td>
        <td class="fmt-label">{{ __('Marca') }}</td><td>{{ $equipo->marca?->nombre }}</td>
    </tr>
    <tr>
        <td class="fmt-label">{{ __('Modelo') }}</td><td>{{ $equipo->modelo ?: '—' }}</td>
        <td class="fmt-label">{{ __('Serial') }}</td><td class="fmt-mono">{{ $equipo->serial }}</td>
    </tr>
    <tr>
        <td class="fmt-label">{{ __('Código de activo') }}</td><td class="fmt-mono">{{ $equipo->codigo_activo ?: __('Sin código') }}</td>
        <td class="fmt-label">{{ __('Propiedad') }}</td><td>{{ $propiedad }}</td>
    </tr>
    @if ($equipo->estado_funcionamiento)
        <tr>
            <td class="fmt-label">{{ __('Estado de funcionamiento') }}</td>
            <td colspan="3">{{ $equipo->estado_funcionamiento }}</td>
        </tr>
    @endif
</table>

@if (($mostrarComponentes ?? true) && $componentes->isNotEmpty())
    <table>
        <tr><td colspan="4" class="fmt-seccion">{{ __('Componentes y periféricos') }}</td></tr>
        <tr>
            <th class="fmt-label">{{ __('Componente') }}</th>
            <th class="fmt-label">{{ __('Característica') }}</th>
            <th class="fmt-label">{{ __('Marca / serial') }}</th>
            <th class="fmt-label">{{ __('Estado') }}</th>
        </tr>
        @foreach ($componentes as $componente)
            <tr>
                <td>{{ $componente->tipoComponente?->nombre ?? __('Componente') }}</td>
                <td>{{ $componente->capacidad_caracteristica ?: '—' }}</td>
                <td>{{ $componente->marca ?: '—' }} @if($componente->serial) · <span class="fmt-mono">{{ $componente->serial }}</span>@endif</td>
                <td>{{ $componente->estado ? ucfirst($componente->estado) : '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif
