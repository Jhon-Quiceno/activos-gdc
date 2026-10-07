{{--
    Cuerpo del formato de baja (RF-29). Se usa para el lado «entrega» de un
    traslado, para el retiro del responsable y para la baja definitiva. Si el
    evento no tiene diagnóstico registrado, las cajas quedan en blanco para que
    el ingeniero de soporte las diligencie a mano.
--}}
<div class="fmt">
    @include('livewire.movimientos.pdf.partials.cabecera')

    <table>
        <tr>
            <td class="fmt-label">{{ __('Fecha de revisión') }}</td><td>{{ \App\Livewire\Movimientos\Soporte\FormatosPdf::fecha($fecha) }}</td>
            <td class="fmt-label">{{ __('Motivo') }}</td><td>{{ $motivo }}</td>
        </tr>
    </table>

    @foreach ($equipos as $equipo)
        @include('livewire.movimientos.pdf.partials.equipo', ['equipo' => $equipo, 'tituloEquipo' => __('Datos del equipo / periférico'), 'mostrarComponentes' => false])
    @endforeach

    @include('livewire.movimientos.pdf.partials.persona', ['tituloPersona' => __('Funcionario responsable que entrega')])

    @if ($diagnostico)
        <table class="fmt-bloque">
            <tr><td class="fmt-seccion">{{ __('Estado encontrado') }}</td></tr>
            <tr><td class="fmt-caja">{{ $diagnostico->estado_encontrado }}</td></tr>
        </table>

        <table class="fmt-bloque">
            <tr><td class="fmt-seccion">{{ __('Diagnóstico') }}</td></tr>
            <tr><td class="fmt-caja-alta">{{ $diagnostico->causa }}</td></tr>
        </table>
    @else
        {{-- Traslado o retiro: el ingeniero revisa el equipo al recibirlo y lo anota a mano. --}}
        <table class="fmt-bloque">
            <tr><td class="fmt-seccion">{{ __('Estado en que se entrega el equipo / diagnóstico') }}</td></tr>
            <tr><td class="fmt-caja-alta"></td></tr>
        </table>
    @endif

    <table class="fmt-bloque">
        <tr><td class="fmt-seccion">{{ __('Recomendaciones / sugerencias del área de sistemas') }}</td></tr>
        <tr><td class="{{ $diagnostico ? 'fmt-caja-alta' : 'fmt-caja' }}">{{ $diagnostico?->recomendaciones }}</td></tr>
    </table>

    @if ($observaciones && ! in_array($evento?->tipo, ['baja', 'diagnostico'], true))
        <table>
            <tr><td class="fmt-seccion">{{ __('Observaciones') }}</td></tr>
            <tr><td>{{ $observaciones }}</td></tr>
        </table>
    @endif

    <p class="fmt-legal">
        @if ($evento?->tipo === 'baja')
            {{ __('Con la firma de este formato se certifica el diagnóstico técnico del equipo y su salida definitiva de servicio. La baja queda registrada en la hoja de vida del equipo.') }}
        @else
            {{ __('Con la firma de este formato, el funcionario hace entrega del equipo descrito al área de sistemas y queda liberado de su custodia a partir de la fecha indicada.') }}
        @endif
    </p>

    @include('livewire.movimientos.pdf.partials.firmas', ['firmas' => [
        ['rol' => __('Ingeniero de soporte técnico'), 'nombre' => $tecnico?->name, 'detalle' => __('Dirección TIC'), 'cedula' => null],
        ['rol' => __('Funcionario responsable'), 'nombre' => $persona?->nombre, 'detalle' => $persona?->cargo ?: __('Quien entrega'), 'cedula' => $persona?->cedula],
    ]])
</div>
