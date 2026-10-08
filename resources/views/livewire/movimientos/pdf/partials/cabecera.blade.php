@php
    $rutaLogo = public_path('images/logoGob.svg');
    $logo = null;
    if (is_file($rutaLogo)) {
        $logo = ($modoPdf ?? false)
            ? 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($rutaLogo))
            : asset('images/logoGob.svg');
    }
@endphp
<table>
    <tr>
        <td class="fmt-centro" style="width: 26%; vertical-align: middle;">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ __('Gobernación de Córdoba') }}" class="fmt-logo">
            @else
                <strong>{{ __('GOBERNACIÓN DE CÓRDOBA') }}</strong>
            @endif
        </td>
        <td class="fmt-centro" style="vertical-align: middle;">
            <div class="fmt-titulo">{{ $titulo }}</div>
            <div>{{ __('Dirección TIC · Gobernación de Córdoba') }}</div>
        </td>
        <td style="width: 24%; vertical-align: middle;">
            <div><strong>{{ __('Consecutivo') }}:</strong> <span class="fmt-mono">{{ $consecutivo }}</span></div>
            <div><strong>{{ __('Versión') }}:</strong> 2.0</div>
            <div><strong>{{ __('Fecha') }}:</strong> {{ \App\Livewire\Movimientos\Soporte\FormatosPdf::fecha($fecha) }}</div>
        </td>
    </tr>
</table>
