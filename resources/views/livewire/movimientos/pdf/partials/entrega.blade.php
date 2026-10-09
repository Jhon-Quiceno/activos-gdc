{{-- Cuerpo del formato de entrega (RF-28). Lo usan el PDF y la vista previa. --}}
<div class="fmt">
    @include('livewire.movimientos.pdf.partials.cabecera')

    <table>
        <tr>
            <td class="fmt-label">{{ __('Fecha de entrega') }}</td><td>{{ \App\Livewire\Movimientos\Soporte\FormatosPdf::fecha($fecha) }}</td>
            <td class="fmt-label">{{ __('Motivo') }}</td><td>{{ $motivo }}</td>
        </tr>
    </table>

    @include('livewire.movimientos.pdf.partials.persona', ['tituloPersona' => __('Funcionario que recibe')])

    @forelse ($equipos as $equipo)
        @include('livewire.movimientos.pdf.partials.equipo', [
            'equipo' => $equipo,
            'tituloEquipo' => $equipos->count() > 1
                ? __('Equipo :n de :total', ['n' => $loop->iteration, 'total' => $equipos->count()])
                : __('Datos del equipo'),
        ])
    @empty
        <table><tr><td class="fmt-centro">{{ __('El funcionario no tiene equipos a cargo.') }}</td></tr></table>
    @endforelse

    <table class="fmt-bloque">
        <tr><td class="fmt-seccion">{{ __('Observaciones') }}</td></tr>
        <tr><td class="fmt-caja">{{ $observaciones }}</td></tr>
    </table>

    <p class="fmt-legal">
        {{ __('Con la firma de este formato, el funcionario declara que recibe los equipos descritos en el estado indicado y se hace responsable de su custodia y buen uso, conforme a las políticas de la Dirección TIC de la Gobernación de Córdoba. Cualquier traslado, daño o pérdida debe informarse a la Dirección TIC.') }}
    </p>

    @include('livewire.movimientos.pdf.partials.firmas', ['firmas' => [
        ['rol' => __('Recibe'), 'nombre' => $persona?->nombre, 'detalle' => __('Funcionario responsable'), 'cedula' => $persona?->cedula],
        ['rol' => __('Entrega'), 'nombre' => $tecnico?->name, 'detalle' => __('Área de sistemas · Dirección TIC'), 'cedula' => null],
    ]])
</div>
