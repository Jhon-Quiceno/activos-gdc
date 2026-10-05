{{--
    Página de la ruta `movimientos.formato-entrega` (Route::view con {evento}).

    Route::view() no hace binding implícito de modelo: {evento} llega como el
    valor crudo de la URL (string), no como instancia de Evento. Por eso se
    resuelve a mano, igual que ya hace livewire/equipos/show.blade.php y las
    vistas de traslado.blade.php / baja.blade.php.

    Esta pantalla NO usa <x-layouts.app-shell>: es un documento imprimible
    aislado (ver resources/views/layouts/documento.blade.php).
--}}
@php
    $evento = \App\Models\Evento::with([
        'equipo.tipoEquipo',
        'equipo.marca',
        'equipo.componentes.tipoComponente',
        'equipo.asignacionActual.persona',
        'equipo.asignacionActual.dependencia',
        'equipo.asignacionActual.sede',
        'equipo.asignacionActual.piso',
    ])->findOrFail($evento);

    $equipo = $evento->equipo;
    $asignacion = $equipo?->asignacionActual;
    $componentesEntregados = $equipo?->componentes->whereNull('fecha_retiro') ?? collect();

    $propiedadLabels = [
        'gobernacion' => __('Gobernación'),
        'tercero' => __('Tercero') . ($equipo?->propietario_tercero ? ' (' . $equipo->propietario_tercero . ')' : ''),
    ];

    $placeholder = '[Sin dato]';
@endphp

@extends('layouts.documento')

@section('titulo', __('Formato de entrega · Evento #:id', ['id' => $evento->id]))

@section('contenido')
    {{-- 1. Cabecera --}}
    <div class="mb-6 border-b-2 border-primary pb-4 text-center">
        <h1 class="font-display text-[20px] font-bold uppercase tracking-wide text-ink">{{ __('Formato de entrega de equipo') }}</h1>
        <p class="mt-1 text-[13px] text-ink-muted">{{ __('Dirección TIC · Gobernación de Córdoba') }}</p>
        <p class="mt-1 text-[12px] text-ink-muted">{{ __('Consecutivo: :id / Versión 2.0', ['id' => $evento->id]) }}</p>
    </div>

    {{-- 2. Fecha / Evento --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Fecha') }}</td>
            <td class="w-1/4 border border-line px-3 py-2 text-ink">{{ $evento->fecha->format('d/m/Y') }}</td>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Evento') }}</td>
            <td class="w-1/4 border border-line px-3 py-2 text-ink">{{ ucfirst(str_replace('_', ' ', $evento->tipo)) }}</td>
        </tr>
    </table>

    {{-- 3. Datos del equipo --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td colspan="4" class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Datos del equipo') }}</td>
        </tr>
        <tr>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Tipo') }}</td>
            <td class="w-1/4 border border-line px-3 py-2 text-ink">{{ $equipo?->tipoEquipo?->nombre ?? $placeholder }}</td>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Marca') }}</td>
            <td class="w-1/4 border border-line px-3 py-2 text-ink">{{ $equipo?->marca?->nombre ?? $placeholder }}</td>
        </tr>
        <tr>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Modelo') }}</td>
            <td class="border border-line px-3 py-2 text-ink">{{ $equipo?->modelo ?? $placeholder }}</td>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Serial') }}</td>
            <td class="border border-line px-3 py-2 font-mono text-ink">{{ $equipo?->serial ?? $placeholder }}</td>
        </tr>
        <tr>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Código de activo') }}</td>
            <td class="border border-line px-3 py-2 font-mono text-ink">{{ $equipo?->codigo_activo ?? $placeholder }}</td>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Propiedad') }}</td>
            <td class="border border-line px-3 py-2 text-ink">{{ $equipo ? ($propiedadLabels[$equipo->propiedad] ?? $equipo->propiedad) : $placeholder }}</td>
        </tr>
    </table>

    {{-- 4. Componentes y periféricos entregados --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td colspan="4" class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Componentes y periféricos entregados') }}</td>
        </tr>
        <tr>
            <th class="border border-line bg-[#F8FAFC] px-3 py-2 text-left font-semibold text-ink-label">{{ __('Componente') }}</th>
            <th class="border border-line bg-[#F8FAFC] px-3 py-2 text-left font-semibold text-ink-label">{{ __('Característica') }}</th>
            <th class="border border-line bg-[#F8FAFC] px-3 py-2 text-left font-semibold text-ink-label">{{ __('Serial') }}</th>
            <th class="border border-line bg-[#F8FAFC] px-3 py-2 text-left font-semibold text-ink-label">{{ __('Estado') }}</th>
        </tr>
        @forelse ($componentesEntregados as $componente)
            <tr>
                <td class="border border-line px-3 py-2 text-ink">
                    {{ $componente->tipoComponente?->nombre ?? __('Componente') }}
                    @if($componente->marca) <span class="text-ink-muted">({{ $componente->marca }})</span> @endif
                </td>
                <td class="border border-line px-3 py-2 text-ink">{{ $componente->capacidad_caracteristica ?? '—' }}</td>
                <td class="border border-line px-3 py-2 font-mono text-ink">{{ $componente->serial ?? '—' }}</td>
                <td class="border border-line px-3 py-2 text-ink">{{ $componente->estado ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="border border-line px-3 py-3 text-center text-ink-muted">{{ __('Este equipo no tiene componentes registrados.') }}</td>
            </tr>
        @endforelse
    </table>

    {{-- 5. Quien recibe --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td colspan="2" class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Quien recibe') }}</td>
        </tr>
        <tr>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Nombre') }}</td>
            <td class="border border-line px-3 py-2 text-ink">{{ $asignacion?->persona?->nombre ?? $placeholder }}</td>
        </tr>
        <tr>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Cédula') }}</td>
            <td class="border border-line px-3 py-2 text-ink">{{ $asignacion?->persona?->cedula ?? $placeholder }}</td>
        </tr>
        <tr>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Cargo') }}</td>
            <td class="border border-line px-3 py-2 text-ink">{{ $asignacion?->persona?->cargo ?? '[Cargo]' }}</td>
        </tr>
        <tr>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Dependencia') }}</td>
            <td class="border border-line px-3 py-2 text-ink">{{ $asignacion?->dependencia?->nombre ?? '[Dependencia]' }}</td>
        </tr>
        <tr>
            <td class="border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Ubicación') }}</td>
            <td class="border border-line px-3 py-2 text-ink">
                @if($asignacion?->sede)
                    {{ $asignacion->sede->nombre }}@if($asignacion->piso?->numero), {{ __('piso :numero', ['numero' => $asignacion->piso->numero]) }}@endif
                @else
                    [Ubicación]
                @endif
            </td>
        </tr>
    </table>

    {{-- 6. Observaciones --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Observaciones') }}</td>
        </tr>
        <tr>
            <td class="min-h-[60px] border border-line px-3 py-3 text-ink">{{ $evento->descripcion ?: __('Sin observaciones.') }}</td>
        </tr>
    </table>

    {{-- 7. Texto legal --}}
    <p class="mb-10 text-[13px] text-ink">
        {{ __('Con la firma de este formato, el funcionario recibe el equipo descrito y se hace responsable de su custodia y buen uso.') }}
    </p>

    {{-- 8. Firmas --}}
    <div class="grid grid-cols-2 gap-10 text-[13px]">
        <div>
            <div class="mb-2 border-t border-ink pt-2">
                <p class="font-semibold text-ink">{{ __('Recibe') }}</p>
                <p class="text-ink-muted">{{ __('Funcionario responsable') }}</p>
                <p class="mt-4 text-ink-muted">{{ __('C.C. ____________') }}</p>
            </div>
        </div>
        <div>
            <div class="mb-2 border-t border-ink pt-2">
                <p class="font-semibold text-ink">{{ __('Entrega') }}</p>
                <p class="text-ink-muted">{{ __('Área de sistemas · Dirección TIC') }}</p>
                <p class="mt-4 text-ink-muted">{{ __('C.C. ____________') }}</p>
            </div>
        </div>
    </div>
@endsection
