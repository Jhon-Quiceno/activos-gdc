{{--
    Página de la ruta `qr.verificar` (Route::view, sin parámetros).

    A diferencia del resto de pantallas, este mockup simula una app móvil
    (ancho máximo ~430px, centrado, fondo gris alrededor) y por eso NO usa
    <x-layouts.app-shell>: es su propio documento HTML completo, con el mismo
    esqueleto (csrf, fuentes, @vite) que resources/views/layouts/guest.blade.php.

    El componente Livewire real vive en App\Livewire\Qr\VerificarPanel, con vista
    propia en livewire/qr/verificar-panel.blade.php (nombre distinto al de esta
    vista para no chocar), igual que el patrón ya usado en admin/usuarios.blade.php.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Verificar en sitio') }} - {{ config('app.name', 'Hoja de Vida de Equipos') }}</title>

        <!-- Fonts: Source Sans 3 (texto) + Work Sans (títulos) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Work+Sans:wght@500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen bg-[#E2E8F0] py-0 sm:py-8">
            <div class="mx-auto min-h-screen w-full max-w-[430px] overflow-hidden bg-app-bg shadow-xl sm:min-h-0 sm:rounded-2xl">
                <livewire:qr.verificar-panel />
            </div>
        </div>
    </body>
</html>
