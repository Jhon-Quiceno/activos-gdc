<table class="fmt-firmas">
    <tr>
        @foreach ($firmas as $firma)
            <td>
                <div class="fmt-linea-firma">
                    <strong>{{ $firma['rol'] }}</strong><br>
                    {{ $firma['nombre'] ?: __('Nombre: ______________________________') }}<br>
                    <span class="fmt-muted">{{ $firma['detalle'] }}</span><br>
                    {{ __('C.C.') }} {{ $firma['cedula'] ?: '____________________' }}
                </div>
            </td>
        @endforeach
    </tr>
</table>
