{{--
    Layout para documentos imprimibles (formatos de entrega / baja).

    A diferencia de <x-layouts.app-shell> (un componente Blade), este archivo
    vive en resources/views/layouts/ y se usa con @extends/@yield, igual que
    cualquier vista de Route::view normal (formato-entrega.blade.php y
    formato-baja.blade.php NO son componentes Livewire, por eso no pueden usar
    el mecanismo de layout de Livewire como sí hace layouts/guest.blade.php).

    Es un documento HTML completo y aislado: sin sidebar ni topbar, pensado
    para imprimirse o exportarse a PDF (con barryvdh/laravel-dompdf, en un
    alcance futuro) en tamaño carta. El ancho máximo (~816px, equivalente a
    una carta a 96dpi) se centra sobre un fondo gris que simula una vista
    previa de impresión; al imprimir (`window.print()`) ese fondo y el botón
    flotante desaparecen (`print:hidden` / `print:bg-white`) y solo queda el
    documento.

    Uso (en la vista que registra la ruta):
    @extends('layouts.documento')
    @section('titulo', 'Formato de entrega de equipo')
    @section('contenido')
        ...contenido del documento...
    @endsection
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', config('app.name', 'Hoja de Vida de Equipos'))</title>

    <!-- Fonts: Source Sans 3 (texto) + Work Sans (títulos) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Work+Sans:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#E2E8F0] font-sans text-ink antialiased print:bg-white">
    <x-ui.button
        type="button"
        variant="primary"
        size="md"
        onclick="window.print()"
        class="fixed bottom-6 right-6 z-50 print:hidden"
    >
        {{ __('Imprimir') }}
    </x-ui.button>

    <div class="mx-auto my-10 max-w-[816px] bg-white p-10 shadow-lg print:my-0 print:max-w-none print:p-0 print:shadow-none">
        @yield('contenido')
    </div>
</body>
</html>
