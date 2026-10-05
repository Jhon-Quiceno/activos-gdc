<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Usuarios" (Manuel, bloque de Administración).
 *
 * RN-15: los usuarios NUNCA se eliminan, solo se desactivan (columna `activo`).
 * Por eso aquí no existe ningún método que borre un User; "Desactivar"/"Activar"
 * solo alternan esa columna.
 *
 * El restablecimiento de contraseña por correo queda como TODO aceptado (ver
 * CLAUDE.md de la tarea): `restablecerContrasena()` no tiene lógica real todavía.
 */
class UsuariosPanel extends Component
{
    // --- Formulario "Crear usuario" ---
    public string $nombre = '';

    public string $correo = '';

    public string $perfil = 'usuario';

    public string $contrasena = '';

    public bool $debeCambiar = true;

    // --- Modal "Editar usuario" ---
    public ?int $editandoId = null;

    public string $editandoNombre = '';

    public string $editandoCorreo = '';

    public string $editandoPerfil = 'usuario';

    public function crear(): void
    {
        $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255', 'unique:users,email'],
            'perfil' => ['required', 'in:usuario,administrador'],
            'contrasena' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $this->nombre,
            'email' => $this->correo,
            // El modelo User castea 'password' => 'hashed': si ya llega hasheado
            // (como aquí, con bcrypt()) el cast no vuelve a hashearlo.
            'password' => bcrypt($this->contrasena),
            'perfil' => $this->perfil,
            'activo' => true,
            'debe_cambiar_contrasena' => $this->debeCambiar,
            'email_verified_at' => now(),
        ]);

        $this->reset(['nombre', 'correo', 'contrasena']);
        $this->perfil = 'usuario';
        $this->debeCambiar = true;

        session()->flash('status', __('Usuario creado correctamente.'));
    }

    public function abrirEdicion(int $userId): void
    {
        $usuario = User::findOrFail($userId);

        $this->editandoId = $usuario->id;
        $this->editandoNombre = $usuario->name;
        $this->editandoCorreo = $usuario->email;
        $this->editandoPerfil = $usuario->perfil;
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'editar-usuario');
    }

    public function guardarEdicion(): void
    {
        $this->validate([
            'editandoNombre' => ['required', 'string', 'max:255'],
            'editandoCorreo' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editandoId)],
            'editandoPerfil' => ['required', 'in:usuario,administrador'],
        ]);

        User::findOrFail($this->editandoId)->update([
            'name' => $this->editandoNombre,
            'email' => $this->editandoCorreo,
            'perfil' => $this->editandoPerfil,
        ]);

        $this->dispatch('close-modal', 'editar-usuario');

        session()->flash('status', __('Usuario actualizado.'));
    }

    /**
     * RN-15: nunca se elimina un usuario, solo se alterna `activo`.
     */
    public function alternarActivo(int $userId): void
    {
        $usuario = User::findOrFail($userId);

        $usuario->update(['activo' => ! $usuario->activo]);

        session()->flash('status', $usuario->activo
            ? __('Usuario activado.')
            : __('Usuario desactivado.'));
    }

    public function restablecerContrasena(int $userId): void
    {
        // TODO: enviar correo de restablecimiento real. Fuera del alcance de esta tarea.
        session()->flash('status', __('Función pendiente: el restablecimiento de contraseña por correo aún no está disponible.'));
    }

    public function render()
    {
        return view('livewire.admin.usuarios-panel', [
            'usuarios' => User::orderBy('name')->get(),
        ]);
    }
}
