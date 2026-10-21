<?php

namespace App\Livewire\Admin;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Usuarios" (Manuel, bloque de Administración).
 *
 * RN-15: los usuarios NUNCA se eliminan, solo se desactivan (columna `activo`).
 * Por eso aquí no existe ningún método que borre un User; "Desactivar"/"Activar"
 * solo alternan esa columna.
 *
 * RF-47: `restablecerContrasena()` genera una contraseña temporal y obliga a
 * cambiarla en el próximo ingreso. No va por correo porque todavía no hay correo
 * saliente configurado.
 */
class UsuariosPanel extends Component
{
    // --- Formulario "Crear usuario" ---
    public string $nombre = '';

    public string $correo = '';

    public string $perfil = 'usuario';

    public ?int $personaId = null;

    public string $contrasena = '';

    public bool $debeCambiar = true;

    // --- Modal "Editar usuario" ---
    public ?int $editandoId = null;

    public string $editandoNombre = '';

    public string $editandoCorreo = '';

    public string $editandoPerfil = 'usuario';

    public ?int $editandoPersonaId = null;

    public function crear(): void
    {
        $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255', 'unique:users,email'],
            'perfil' => ['required', 'in:usuario,administrador'],
            // RF-44: cada usuario puede enlazarse a una persona, y una persona
            // tiene a lo sumo un usuario.
            'personaId' => ['nullable', 'integer', Rule::exists('personas', 'id'), Rule::unique('users', 'persona_id')],
            'contrasena' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $this->nombre,
            'email' => $this->correo,
            'persona_id' => $this->personaId,
            // El modelo User castea 'password' => 'hashed': si ya llega hasheado
            // (como aquí, con bcrypt()) el cast no vuelve a hashearlo.
            'password' => bcrypt($this->contrasena),
            'perfil' => $this->perfil,
            'activo' => true,
            'debe_cambiar_contrasena' => $this->debeCambiar,
            'email_verified_at' => now(),
        ]);

        $this->reset(['nombre', 'correo', 'contrasena', 'personaId']);
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
        $this->editandoPersonaId = $usuario->persona_id;
        $this->resetErrorBag();

        $this->dispatch('open-modal', 'editar-usuario');
    }

    public function guardarEdicion(): void
    {
        $this->validate([
            'editandoNombre' => ['required', 'string', 'max:255'],
            'editandoCorreo' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editandoId)],
            'editandoPerfil' => ['required', 'in:usuario,administrador'],
            'editandoPersonaId' => ['nullable', 'integer', Rule::exists('personas', 'id'), Rule::unique('users', 'persona_id')->ignore($this->editandoId)],
        ]);

        User::findOrFail($this->editandoId)->update([
            'name' => $this->editandoNombre,
            'email' => $this->editandoCorreo,
            'perfil' => $this->editandoPerfil,
            'persona_id' => $this->editandoPersonaId,
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

    /**
     * RF-47: el administrador restablece la contraseña a una temporal y obliga
     * a cambiarla en el próximo ingreso. Se genera en el momento y se muestra
     * una sola vez para que el administrador se la entregue a la persona (no hay
     * correo saliente configurado todavía).
     */
    public function restablecerContrasena(int $userId): void
    {
        $usuario = User::findOrFail($userId);
        $temporal = Str::password(12, symbols: false);

        $usuario->update([
            'password' => bcrypt($temporal),
            'debe_cambiar_contrasena' => true,
        ]);

        session()->flash('status', __('Contraseña temporal de :nombre: :clave — se pedirá cambiarla al ingresar.', [
            'nombre' => $usuario->name,
            'clave' => $temporal,
        ]));
    }

    public function render()
    {
        return view('livewire.admin.usuarios-panel', [
            'usuarios' => User::with('persona')->orderBy('name')->get(),
            'personas' => Persona::orderBy('apellidos')->orderBy('nombres')->get(),
        ]);
    }
}
