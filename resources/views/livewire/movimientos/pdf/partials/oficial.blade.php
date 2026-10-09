{{--
    Formato oficial de la Dirección TIC: «FORMATO DE HOJA DE VIDA», versión 1.0
    del 02/09/2024 (archivo «HOJA DE VIDA BAJA NUEVO 20-12-2024.docx»).

    Es UN SOLO formato que se usa en varios contextos; solo cambia el contenido
    que se genera, nunca la estructura ni los espacios de firma:
    - retiro:      quien entrega el equipo en un traslado (antes «formato de baja»).
    - entrega:     quien recibe el equipo (antes «formato de entrega»).
    - baja:        baja definitiva del equipo.
    - diagnostico: diagnóstico técnico con formato.

    Lo usan el PDF (dompdf) y la vista previa imprimible. Con varios equipos
    (formato consolidado de un funcionario) sale una hoja por equipo.

    Espera los datos de FormatosPdf::datos() / datosConsolidado(), incluido $contexto.
--}}
@php
    $formatos = \App\Livewire\Movimientos\Soporte\FormatosPdf::class;
    $logo = 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path('views/livewire/movimientos/pdf/logo-gobernacion-horizontal.png')));
@endphp

@foreach ($equipos as $equipoFormato)
    @php
        $contenido = $formatos::contenido($contexto, $equipoFormato, $evento ?? null, $persona ?? null, $asignacion ?? null, $diagnostico ?? null);
    @endphp

    <div class="ofi" @if (! $loop->last) style="page-break-after: always;" @endif>
        <img src="{{ $logo }}" alt="{{ __('Gobernación de Córdoba') }}" class="ofi-logo">

        <table class="ofi-tabla">
            <colgroup>
                <col style="width: 25.6%;">
                <col style="width: 25.7%;">
                <col style="width: 35.3%;">
                <col style="width: 13.4%;">
            </colgroup>
            <tr>
                <td colspan="3" class="ofi-titulo">{{ __('FORMATO DE HOJA DE VIDA') }}</td>
                <td class="ofi-version">{{ __('Versión 1.0') }}<br>02/09/2024</td>
            </tr>
            <tr><td colspan="4" class="ofi-gris ofi-centro">{{ __('Identificación Y Especificaciones De Equipo') }}</td></tr>
            <tr><td colspan="4" class="ofi-gris ofi-centro">{{ __('DATOS DEL EQUIPO / PERIFÉRICO') }}</td></tr>
            <tr>
                <td class="ofi-etiqueta">{{ __('Fecha de revisión:') }}</td>
                <td colspan="3">{{ $formatos::fecha($fecha) }}</td>
            </tr>
            <tr>
                <td class="ofi-etiqueta">{{ __('Tipo de equipo:') }}</td>
                <td colspan="3">{{ $equipoFormato->tipoEquipo?->nombre }}</td>
            </tr>
            <tr>
                <td class="ofi-etiqueta">{{ __('Marca del equipo:') }}</td>
                <td>{{ $equipoFormato->marca?->nombre }}</td>
                <td colspan="2"><span class="ofi-etiqueta-linea">{{ __('Modelo:') }}</span> {{ $equipoFormato->modelo }}</td>
            </tr>
            <tr>
                <td class="ofi-etiqueta">{{ __('Placa Activo:') }}</td>
                <td>{{ $equipoFormato->codigo_activo ?? __('Sin placa') }}</td>
                <td colspan="2"><span class="ofi-etiqueta-linea">{{ __('Serial:') }}</span> {{ $equipoFormato->serial }}</td>
            </tr>
            <tr>
                <td class="ofi-etiqueta">{{ __('Funcionario Responsable:') }}</td>
                <td colspan="3">{{ $persona?->nombre ?? __('Sin responsable (bodega)') }}</td>
            </tr>
            <tr>
                <td class="ofi-etiqueta">{{ __('Cargo:') }}</td>
                <td colspan="3">{{ $persona?->cargo }}</td>
            </tr>
            <tr><td colspan="4" class="ofi-gris ofi-centro">{{ __('Diagnóstico') }}</td></tr>
            <tr><td colspan="4" class="ofi-caja-diagnostico">{!! nl2br(e($contenido['diagnostico'])) !!}</td></tr>
            <tr><td colspan="4" class="ofi-gris ofi-centro">{{ __('Recomendaciones / Sugerencias del área de sistemas') }}</td></tr>
            <tr><td colspan="4" class="ofi-caja-recomendaciones">{!! nl2br(e($contenido['recomendaciones'])) !!}</td></tr>
        </table>

        <p class="ofi-consecutivo">{{ __('Consecutivo :consecutivo', ['consecutivo' => $consecutivo]) }}</p>

        {{-- Mismo pie de firmas del formato oficial. --}}
        <table class="ofi-firmas">
            <tr>
                <td>
                    <div class="ofi-linea-firma"></div>
                    {{ __('Firma: Ingeniero de Soporte Técnico') }}<br>
                    <span class="ofi-nombre">{{ $tecnico?->name }}</span>
                </td>
                <td>
                    <div class="ofi-linea-firma"></div>
                    {{ __('Aprobado: funcionario responsable') }}<br>
                    <span class="ofi-nombre">{{ $persona?->nombre }}</span>
                    @if ($persona?->cedula)
                        <br><span class="ofi-nombre">{{ __('C.C. :cedula', ['cedula' => $persona->cedula]) }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
@endforeach
