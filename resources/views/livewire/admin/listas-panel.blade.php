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
                                {{ $config['campo'] === 'numero' ? __('Piso :numero', ['numero' => $registro->numero]) : $registro->nombre }}
                            </td>
                            <td class="text-ink-muted">{{ $fila['detalle'] }}</td>
                            <td>{{ $fila['uso'] }}</td>
                            <td>
                                <x-ui.badge variant="success">{{ __('Activo') }}</x-ui.badge>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $registro->id }})">
                                        {{ __('Editar') }}
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="secondary" wire:click="desactivar({{ $registro->id }})">
                                        {{ __('Desactivar') }}
                                    </x-ui.button>
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
                            :label="($config['campo'] === 'numero' ? __('Número') : __('Nombre')) . ' *'"
                            :type="$config['campo'] === 'numero' ? 'number' : 'text'"
                            wire:model="nuevoValor"
                            required
                        />
                    </div>

                    @if ($listaActiva === 'tipos_equipo')
                        <div class="w-full max-w-xs">
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Familia') }} *</label>
                            <select wire:model="nuevaFamilia" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($familias as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('nuevaFamilia')" class="mt-1" />
                        </div>
                    @endif

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
                :label="($config['campo'] === 'numero' ? __('Número') : __('Nombre')) . ' *'"
                :type="$config['campo'] === 'numero' ? 'number' : 'text'"
                wire:model="editandoValor"
                required
            />

            @if ($listaActiva === 'tipos_equipo')
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Familia') }} *</label>
                    <select wire:model="editandoFamilia" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($familias as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('editandoFamilia')" class="mt-1" />
                </div>
            @endif
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
