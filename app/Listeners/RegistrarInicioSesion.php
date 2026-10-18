<?php

namespace App\Listeners;

use App\Models\Auditoria;
use Illuminate\Auth\Events\Login;
use Illuminate\Events\Attributes\AsEventListener;

/**
 * RF-46 (Ley 1581): deja en la bitácora de auditoría cada inicio de sesión.
 */
class RegistrarInicioSesion
{
    #[AsEventListener]
    public function handle(Login $event): void
    {
        Auditoria::create([
            'usuario_id' => $event->user->id,
            'accion' => 'login',
            'entidad' => 'User',
            'entidad_id' => $event->user->id,
            'ip' => request()->ip(),
        ]);
    }
}
