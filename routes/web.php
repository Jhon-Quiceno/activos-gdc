<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    // --- Equipos (Juan José) ---
    Route::view('/equipos', 'livewire.equipos.page')->name('equipos.index');
    Route::view('/equipos/crear', 'livewire.equipos.crear')->name('equipos.crear');
    Route::view('/equipos/{equipo}', 'livewire.equipos.show')->name('equipos.show');
    Route::view('/equipos/{equipo}/editar', 'livewire.equipos.editar')->name('equipos.editar');

    // --- Movimientos (Anuar) ---
    Route::view('/movimientos', 'livewire.movimientos.page')->name('movimientos.index');
    Route::view('/movimientos/traslados', 'livewire.movimientos.traslados-index')->name('movimientos.traslados.index');
    Route::view('/movimientos/traslados/{equipo}', 'livewire.movimientos.traslado')->name('movimientos.traslado');
    Route::view('/movimientos/bajas', 'livewire.movimientos.bajas-index')->name('movimientos.bajas.index');
    Route::view('/movimientos/bajas/{equipo}', 'livewire.movimientos.baja')->name('movimientos.baja');
    Route::view('/movimientos/diagnostico/{equipo}', 'livewire.movimientos.diagnostico')->name('movimientos.diagnostico');
    Route::view('/movimientos/componente/{equipo}', 'livewire.movimientos.componente')->name('movimientos.componente');
    Route::view('/movimientos/pendientes', 'livewire.movimientos.pendientes')->name('movimientos.pendientes');
    Route::view('/movimientos/{evento}/formato-entrega', 'livewire.movimientos.formato-entrega')->name('movimientos.formato-entrega');
    Route::view('/movimientos/{evento}/formato-baja', 'livewire.movimientos.formato-baja')->name('movimientos.formato-baja');

    // --- Importación (Juan Camilo) ---
    Route::view('/importacion', 'livewire.importacion.page')->name('importacion.index');

    // --- Reportes (Alex) ---
    Route::view('/reportes', 'livewire.reportes.page')->name('reportes.index');

    // --- Administración (Manuel) ---
    Route::view('/admin', 'livewire.admin.page')->name('admin.index');
    Route::view('/admin/usuarios', 'livewire.admin.usuarios')->name('admin.usuarios');
    Route::view('/admin/listas', 'livewire.admin.listas')->name('admin.listas');

    // --- Etiquetas QR (desde el 9 oct, Juan José; antes Manuel) ---
    Route::view('/qr', 'livewire.qr.page')->name('qr.index');
    Route::view('/qr/verificar', 'livewire.qr.verificar')->name('qr.verificar');
    Route::view('/qr/etiquetas', 'livewire.qr.etiquetas')->name('qr.etiquetas');

    // RF-50, RNF-18: el QR solo trae este enlace corto con el identificador
    // permanente del equipo (RN-17, RN-19). Como está dentro de `auth`, quien
    // escanea sin sesión pasa por el login y vuelve aquí (RN-18).
    Route::get('/e/{uuid}', function (string $uuid) {
        $equipo = \App\Models\Equipo::where('qr_uuid', $uuid)->firstOrFail();

        return redirect()->route('equipos.show', $equipo);
    })->name('qr.escanear');
});

require __DIR__.'/auth.php';
