{{--
    Vista propia del componente Livewire App\Livewire\Admin\ListasPanel.

    Se llama "listas-panel" (y no "listas") por la misma razón que
    usuarios-panel.blade.php: la ruta `admin.listas` ya usa el nombre de vista
    'livewire.admin.listas' para el wrapper (ver listas.blade.php).
--}}
<div>
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        {{-- Nav lateral de catálogos --}}
        <div class="lg:col-span-1">
            <x-ui.card :padding="false">
                <nav class="divide-y divide-line">
                    @foreach ($catalogos as $clave => $cfg)
                        <button
                            type="button"
                            wire:click="seleccionarLista('{{ $clave }}')"
                            wire:key="nav-{{ $clave }}"
                            class="flex w-full items-center justify-between gap-2 px-4 py-3 text-left text-[14px] transition-colors duration-150
                                {{ $listaActiva === $clave ? 'bg-info-bg font-semibold text-primary' : 'text-ink hover:bg-app-bg' }}"
                        >
                            <span>{{ __($cfg['label']) }}</span>
                            <span class="rounded-full bg-neutral-bg px-2 py-0.5 text-[12px] font-semibold text-neutral-text">
                                {{ $conteos[$clave] }}
                            </span>
                        </button>
                    @endforeach
                </nav>
            </x-ui.card>
        </div>

        {{-- Panel derecho: tabla + agregar --}}
        <div class="space-y-4 lg:col-span-3">
            <x-ui.table>
                <thead>
                    <tr>
                        <th>{{ __('Nombre') }}</th>
                        <th>{{ __('Detalle') }}</th>
                        <th>{{ __($usoLabel) }}</th>
                        <th>{{ __('Estado') }}</th>
                        <th>{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        @php $registro = $fila['registro']; @endphp
                        <tr wire:key="registro-{{ $listaActiva }}-{{ $registro->id }}">
                            <td class="font-semibold text-ink">
                                {{ $registro->nombre }}
                            </td>
                            <td class="text-ink-muted">{{ $fila['detalle'] }}</td>
                            <td>{{ $fila['uso'] }}</td>
                            <td>
                                @if ($fila['activo'])
                                    <x-ui.badge variant="success">{{ __('Activo') }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral">{{ __('Inactivo') }}</x-ui.badge>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $registro->id }})">
                                        {{ __('Editar') }}
                                    </x-ui.button>
                                    @if ($tieneEstado)
                                        <x-ui.button size="sm" variant="secondary" wire:click="desactivar({{ $registro->id }})">
                                            {{ $fila['activo'] ? __('Desactivar') : __('Activar') }}
                                        </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-ink-muted">
                                {{ __('Todavía no hay elementos en esta lista.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            <x-ui.card>
                <p class="section-title">{{ __('Agregar :singular', ['singular' => __($config['singular'])]) }}</p>

                <form wire:submit="agregar" class="mt-4 flex flex-wrap items-end gap-4">
                    <div class="w-full max-w-sm">
                        <x-ui.input
                            name="nuevoValor"
                            :label="__('Nombre') . ' *'"
                            type="text"
                            wire:model="nuevoValor"
                            required
                        />
                    </div>

                    @include('livewire.admin.partials.campos-lista', ['prefijo' => 'extra'])

                    <div class="flex w-full flex-wrap items-center justify-between gap-4">
                        <span class="text-[13px] text-ink-muted">
                            {{ __('Los elementos en uso no se eliminan: se desactivan para que el historial no se pierda.') }}
                        </span>

                        <x-ui.button type="submit" variant="success">
                            {{ __('Agregar') }}
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="editar-item" :title="__('Editar :singular', ['singular' => __($config['singular'])])">
        <form wire:submit="guardarEdicion" class="space-y-4">
            <x-ui.input
                name="editandoValor"
                :label="__('Nombre') . ' *'"
                type="text"
                wire:model="editandoValor"
                required
            />

            <div class="flex flex-wrap gap-4">
                @include('livewire.admin.partials.campos-lista', ['prefijo' => 'editandoExtra'])
            </div>
        </form>

        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'editar-item')">
                {{ __('Cancelar') }}
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="guardarEdicion">
                {{ __('Guardar cambios') }}
            </x-ui.button>
        </x-slot>
    </x-ui.modal>
</div>
