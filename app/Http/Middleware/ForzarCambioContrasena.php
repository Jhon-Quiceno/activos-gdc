<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RF-44: la contraseña inicial que asigna el Administrador se debe cambiar en
 * el primer ingreso. Mientras `debe_cambiar_contrasena` sea verdadero, cualquier
 * navegación a una pantalla protegida se redirige a cambiarla primero.
 *
 * Se aplica por ruta (ver routes/web.php), no en el grupo global "web": así
 * nunca interfiere con el endpoint interno de Livewire (`/livewire/update`),
 * que no pasa por el middleware de una ruta de página.
 */
class ForzarCambioContrasena
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario?->debe_cambiar_contrasena && ! $request->routeIs('password.obligatoria')) {
            return redirect()->route('password.obligatoria');
        }

        return $next($request);
    }
}
