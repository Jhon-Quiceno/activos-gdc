<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * RF-44: pantalla de cambio de contraseña obligatorio en el primer ingreso.
 * Al guardar, `debe_cambiar_contrasena` pasa a falso y el usuario puede
 * navegar al resto del sistema (ver Middleware\ForzarCambioContrasena).
 */
class CambioContrasenaObligatorio extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function guardar(): void
    {
        $this->validate([
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->password),
            'debe_cambiar_contrasena' => false,
        ]);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.cambio-contrasena-obligatorio');
    }
}
