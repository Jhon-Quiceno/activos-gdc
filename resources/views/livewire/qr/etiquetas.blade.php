{{--
    Página de la ruta `qr.etiquetas` (Route::view). Documento propio, sin
    menú ni barra superior, para que solo salgan las etiquetas al imprimir
    (mismo esqueleto que livewire/qr/verificar.blade.php).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Etiquetas QR') }} - {{ config('app.name', 'Hoja de Vida de Equipos') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Work+Sans:wght@500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-app-bg font-sans text-ink antialiased">
        <livewire:qr.etiquetas />
    </body>
</html>
