{{--
    Íconos del menú lateral — mismo trazo (stroke-width 1.8, 24x24, round caps) y, donde
    existe equivalente, el mismo path exacto del prototipo "Hoja de Vida de Equipos —
    Prototipo" en Claude Design, para que el menú se vea idéntico al diseño aprobado.
--}}
@props(['name'])

@php
    $paths = match ($name) {
        'inicio' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'equipos' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'movimientos' => '<path d="M7 7h12l-3-3M17 17H5l3 3"/>',
        'importacion' => '<path d="M12 21V9M7 14l5-5 5 5"/><path d="M4 3h16"/>',
        'reportes' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'admin' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.5 3.4-5.5 6.5-5.5s5.7 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 010 7M18 14.5c2 .7 3.2 2.5 3.5 5.5"/>',
        // No existe en el prototipo (el QR ahí se abre escaneando, no desde el menú);
        // ícono nuevo con el mismo lenguaje visual (trazo, esquinas de un código QR).
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3.5v3.5H14zM20.5 14v3.5M14 20.5h3.5M20.5 20.5h.01"/>',
        default => '<circle cx="12" cy="12" r="8"/>',
    };
@endphp

<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths !!}</svg>
