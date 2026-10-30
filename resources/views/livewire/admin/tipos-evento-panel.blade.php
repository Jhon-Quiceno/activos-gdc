{{--
    Vista propia del componente Livewire App\Livewire\Admin\TiposEventoPanel.

    Se llama "tipos-evento-panel" por la misma razón que usuarios-panel.blade.php
    y listas-panel.blade.php: la ruta `admin.tipos-evento` ya usa el nombre de
    vista 'livewire.admin.tipos-evento' para el wrapper (ver tipos-evento.blade.php).
--}}
@php
    $textareaClass = 'mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
@endphp

<div>
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">
            {{ session('status') }}
        </div>
    @endif

    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('Nombre') }}</th>
                <th>{{ __('Descripción') }}</th>
                <th>{{ __('Requiere firma') }}</th>
                <th>{{ __('Origen') }}</th>
                <th>{{ __('Eventos') }}</th>
                <th>{{ __('Estado') }}</th>
                <th>{{ __('Acciones') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $fila)
                @php $tipo = $fila['tipo']; @endphp
                <tr wire:key="tipo-evento-{{ $tipo->id }}">
                    <td class="font-semibold text-ink">{{ $tipo->nombre }}</td>
                    <td class="text-ink-muted">{{ $tipo->descripcion ?? '—' }}</td>
                    <td>
                        <x-ui.badge variant="{{ $tipo->requiere_firma ? 'success' : 'neutral' }}">
                            {{ $tipo->requiere_firma ? __('Sí') : __('No') }}
                        </x-ui.badge>
                    </td>
                    <td>
                        <x-ui.badge variant="{{ $tipo->es_sistema ? 'info' : 'neutral' }}">
                            {{ $tipo->es_sistema ? __('Sistema') : __('Configurable') }}
                        </x-ui.badge>
                    </td>
                    <td>{{ $fila['eventos'] }}</td>
                    <td>
                        @if ($tipo->activo)
                            <x-ui.badge variant="success">{{ __('Activo') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">{{ __('Inactivo') }}</x-ui.badge>
                        @endif
                    </td>
                    <td>
                        @if ($tipo->es_sistema)
                            <span class="text-[13px] text-ink-muted">{{ __('No editable') }}</span>
                        @else
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $tipo->id }})">
                                    {{ __('Editar') }}
                                </x-ui.button>
                                <x-ui.button size="sm" variant="secondary" wire:click="desactivar({{ $tipo->id }})">
                                    {{ $tipo->activo ? __('Desactivar') : __('Activar') }}
                                </x-ui.button>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table>

    <x-ui.card class="mt-4">
        <p class="section-title">{{ __('Agregar tipo de evento configurable') }}</p>

        <form wire:submit="agregar" class="mt-4 space-y-4">
            <div class="flex flex-wrap items-start gap-4">
                <div class="w-full max-w-sm">
                    <x-ui.input name="nuevoNombre" :label="__('Nombre') . ' *'" type="text" wire:model="nuevoNombre" required />
                </div>

                <label class="mt-7 flex items-center gap-2 text-[13px] text-ink">
                    <input type="checkbox" wire:model="nuevoRequiereFirma" class="rounded border-line-input text-primary focus:ring-primary">
                    {{ __('Requiere firma (el evento nace pendiente de firma)') }}
                </label>
            </div>

            <div>
                <label for="nuevaDescripcion" class="text-[13px] font-semibold text-ink-label">{{ __('Descripción') }}</label>
                <textarea id="nuevaDescripcion" wire:model="nuevaDescripcion" rows="2" class="{{ $textareaClass }}"></textarea>
                <x-input-error :messages="$errors->get('nuevaDescripcion')" class="mt-1" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4">
                <span class="text-[13px] text-ink-muted">
                    {{ __('Un tipo configurable solo registra un hecho sobre el equipo (fecha, descripción y adjuntos); no se borra, se desactiva.') }}
                </span>

                <x-ui.button type="submit" variant="success">
                    {{ __('Agregar') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.modal name="editar-tipo-evento" :title="__('Editar tipo de evento')">
        <form wire:submit="guardarEdicion" class="space-y-4">
            <x-ui.input name="editandoNombre" :label="__('Nombre') . ' *'" type="text" wire:model="editandoNombre" required />

            <div>
                <label for="editandoDescripcion" class="text-[13px] font-semibold text-ink-label">{{ __('Descripción') }}</label>
                <textarea id="editandoDescripcion" wire:model="editandoDescripcion" rows="2" class="{{ $textareaClass }}"></textarea>
                <x-input-error :messages="$errors->get('editandoDescripcion')" class="mt-1" />
            </div>

            <label class="flex items-center gap-2 text-[13px] text-ink">
                <input type="checkbox" wire:model="editandoRequiereFirma" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('Requiere firma (el evento nace pendiente de firma)') }}
            </label>
        </form>

        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'editar-tipo-evento')">
                {{ __('Cancelar') }}
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="guardarEdicion">
                {{ __('Guardar cambios') }}
            </x-ui.button>
        </x-slot>
    </x-ui.modal>
</div>
