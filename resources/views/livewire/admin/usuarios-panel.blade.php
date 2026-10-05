{{--
    Vista propia del componente Livewire App\Livewire\Admin\UsuariosPanel.

    Se llama "usuarios-panel" (y no "usuarios") a propósito: la ruta
    `admin.usuarios` apunta a la vista 'livewire.admin.usuarios' (ver
    routes/web.php), que es un simple wrapper <x-layouts.app-shell> +
    <livewire:admin.usuarios-panel /> (ver usuarios.blade.php). Si este archivo
    también se llamara "usuarios.blade.php" chocaría con esa vista.
--}}
<div>
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>{{ __('Nombre') }}</th>
                        <th>{{ __('Correo') }}</th>
                        <th>{{ __('Perfil') }}</th>
                        <th>{{ __('Estado') }}</th>
                        <th>{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr wire:key="usuario-{{ $usuario->id }}">
                            <td class="font-semibold text-ink">{{ $usuario->name }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                <x-ui.badge :variant="$usuario->perfil === 'administrador' ? 'info' : 'neutral'">
                                    {{ $usuario->perfil === 'administrador' ? __('Administrador') : __('Usuario') }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <x-ui.badge :variant="$usuario->activo ? 'success' : 'neutral'">
                                    {{ $usuario->activo ? __('Activo') : __('Desactivado') }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $usuario->id }})">
                                        {{ __('Editar') }}
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="secondary" wire:click="restablecerContrasena({{ $usuario->id }})">
                                        {{ __('Restablecer contraseña') }}
                                    </x-ui.button>
                                    @if ($usuario->activo)
                                        <x-ui.button
                                            size="sm"
                                            variant="danger"
                                            wire:click="alternarActivo({{ $usuario->id }})"
                                            wire:confirm="{{ __('¿Desactivar a :nombre?', ['nombre' => $usuario->name]) }}"
                                        >
                                            {{ __('Desactivar') }}
                                        </x-ui.button>
                                    @else
                                        <x-ui.button size="sm" variant="secondary" wire:click="alternarActivo({{ $usuario->id }})">
                                            {{ __('Activar') }}
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-ink-muted">
                                {{ __('Todavía no hay usuarios registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </div>

        <div class="lg:col-span-1">
            <x-ui.card>
                <p class="section-title">{{ __('Crear usuario') }}</p>

                <form wire:submit="crear" class="mt-4 space-y-4">
                    <x-ui.input name="nombre" :label="__('Nombre completo') . ' *'" wire:model="nombre" required />

                    <x-ui.input name="correo" type="email" :label="__('Correo') . ' *'" wire:model="correo" required />

                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Perfil') }} *</label>
                        <select wire:model="perfil" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="usuario">{{ __('Usuario') }}</option>
                            <option value="administrador">{{ __('Administrador') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('perfil')" class="mt-1" />
                    </div>

                    <x-ui.input name="contrasena" type="password" :label="__('Contraseña inicial') . ' *'" wire:model="contrasena" required />

                    <label class="flex items-center gap-2 text-[13px] text-ink-muted">
                        <input type="checkbox" wire:model="debeCambiar" class="rounded border-line-input text-primary focus:ring-primary">
                        {{ __('Debe cambiarla en su primer ingreso') }}
                    </label>

                    <x-ui.button type="submit" variant="success" class="w-full justify-center">
                        {{ __('Crear usuario') }}
                    </x-ui.button>
                </form>
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="editar-usuario" :title="__('Editar usuario')">
        <form wire:submit="guardarEdicion" class="space-y-4">
            <x-ui.input name="editandoNombre" :label="__('Nombre completo') . ' *'" wire:model="editandoNombre" required />
            <x-ui.input name="editandoCorreo" type="email" :label="__('Correo') . ' *'" wire:model="editandoCorreo" required />

            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Perfil') }} *</label>
                <select wire:model="editandoPerfil" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="usuario">{{ __('Usuario') }}</option>
                    <option value="administrador">{{ __('Administrador') }}</option>
                </select>
                <x-input-error :messages="$errors->get('editandoPerfil')" class="mt-1" />
            </div>
        </form>

        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'editar-usuario')">
                {{ __('Cancelar') }}
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="guardarEdicion">
                {{ __('Guardar cambios') }}
            </x-ui.button>
        </x-slot>
    </x-ui.modal>
</div>
