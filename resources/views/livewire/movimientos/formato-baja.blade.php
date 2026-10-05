{{--
    Página de la ruta `movimientos.formato-baja` (Route::view con {evento}).

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
        'equipo.asignacionActual.persona',
        'equipo.asignacionActual.dependencia',
        'diagnostico.motivoBaja',
    ])->findOrFail($evento);

    $equipo = $evento->equipo;
    $asignacion = $equipo?->asignacionActual;
    $diagnostico = $evento->diagnostico;

    $placeholder = '[Sin dato]';
@endphp

@extends('layouts.documento')

@section('titulo', __('Formato de baja · Evento #:id', ['id' => $evento->id]))

@section('contenido')
    {{-- 1. Cabecera --}}
    <div class="mb-6 border-b-2 border-primary pb-4 text-center">
        <h1 class="font-display text-[20px] font-bold uppercase tracking-wide text-ink">{{ __('Formato de hoja de vida – baja') }}</h1>
        <p class="mt-1 text-[13px] text-ink-muted">{{ __('Dirección TIC · Gobernación de Córdoba') }}</p>
        <p class="mt-1 text-[12px] text-ink-muted">{{ __('Consecutivo: :id / Versión 2.0', ['id' => $evento->id]) }}</p>
    </div>

    {{-- 2. Fecha de revisión / Motivo --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Fecha de revisión') }}</td>
            <td class="w-1/4 border border-line px-3 py-2 text-ink">{{ $evento->fecha->format('d/m/Y') }}</td>
            <td class="w-1/4 border border-line bg-[#F8FAFC] px-3 py-2 font-semibold text-ink-label">{{ __('Motivo') }}</td>
            <td class="w-1/4 border border-line px-3 py-2 text-ink">{{ $diagnostico?->motivoBaja?->nombre ?? $placeholder }}</td>
        </tr>
    </table>

    {{-- 3. Datos del equipo / periférico --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td colspan="4" class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Datos del equipo / periférico') }}</td>
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
            <td class="border border-line px-3 py-2 font-mono text-ink" colspan="3">{{ $equipo?->codigo_activo ?? $placeholder }}</td>
        </tr>
    </table>

    {{-- 4. Funcionario responsable que entrega --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td colspan="2" class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Funcionario responsable que entrega') }}</td>
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
    </table>

    {{-- 5. Diagnóstico --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Diagnóstico') }}</td>
        </tr>
        <tr>
            <td class="border border-line px-3 py-3 text-ink">{{ $diagnostico?->causa ?: ($evento->descripcion ?: __('Sin diagnóstico registrado.')) }}</td>
        </tr>
    </table>

    {{-- 6. Recomendaciones / sugerencias del área de sistemas --}}
    <table class="mb-6 w-full border-collapse border border-line text-[13px]">
        <tr>
            <td class="border border-line bg-primary px-3 py-2 font-semibold uppercase tracking-wide text-white">{{ __('Recomendaciones / sugerencias del área de sistemas') }}</td>
        </tr>
        <tr>
            <td class="border border-line px-3 py-3 text-ink">{{ $diagnostico?->recomendaciones ?: __('Sin recomendaciones registradas.') }}</td>
        </tr>
    </table>

    {{-- 7. Texto legal --}}
    <p class="mb-10 text-[13px] text-ink">
        {{ __('Este formato se usa cuando un funcionario entrega un equipo (traslado o retiro) y para la baja definitiva del equipo.') }}
    </p>

    {{-- 8. Firmas --}}
    <div class="grid grid-cols-2 gap-10 text-[13px]">
        <div>
            <div class="mb-2 border-t border-ink pt-2">
                <p class="font-semibold text-ink">{{ __('Ingeniero de soporte técnico') }}</p>
                <p class="text-ink-muted">{{ __('Dirección TIC') }}</p>
                <p class="mt-4 text-ink-muted">{{ __('C.C. ____________') }}</p>
            </div>
        </div>
        <div>
            <div class="mb-2 border-t border-ink pt-2">
                <p class="font-semibold text-ink">{{ __('Aprobado') }}</p>
                <p class="text-ink-muted">{{ __('Funcionario responsable') }}</p>
                <p class="mt-4 text-ink-muted">{{ __('C.C. ____________') }}</p>
            </div>
        </div>
    </div>
@endsection
