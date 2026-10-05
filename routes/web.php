<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Rutas base por bloque — cada una renderiza una página simple ("Próximamente")
// que monta su componente Livewire de arranque dentro del layout con sidebar,
// para que cada programador construya su pantalla encima sin pisar a los demás.
Route::middleware(['auth'])->group(function () {
    Route::view('/equipos', 'livewire.equipos.page')->name('equipos.index');
    Route::view('/movimientos', 'livewire.movimientos.page')->name('movimientos.index');
    Route::view('/importacion', 'livewire.importacion.page')->name('importacion.index');
    Route::view('/reportes', 'livewire.reportes.page')->name('reportes.index');
    Route::view('/admin', 'livewire.admin.page')->name('admin.index');
    Route::view('/qr', 'livewire.qr.page')->name('qr.index');
});

require __DIR__.'/auth.php';
