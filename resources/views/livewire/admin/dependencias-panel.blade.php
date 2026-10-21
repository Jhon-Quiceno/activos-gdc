<div>
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <input
                type="search"
                wire:model.live.debounce.300ms="busqueda"
                placeholder="{{ __('Buscar dependencia…') }}"
                class="h-11 w-72 rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
            />
            <label class="flex items-center gap-2 text-[13px] text-ink-muted">
                <input type="checkbox" wire:model.live="verInactivas" class="rounded border-line-input text-primary focus:ring-primary" />
                {{ __('Ver inactivas') }}
            </label>
        </div>
        <x-ui.button variant="success" wire:click="nuevo">{{ __('Nueva dependencia') }}</x-ui.button>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('Dependencia') }}</th>
                <th>{{ __('Tipo') }}</th>
                <th>{{ __('Proceso') }}</th>
                <th>{{ __('Estado') }}</th>
                <th>{{ __('Acciones') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($arbol as $dependencia)
                <tr wire:key="dep-{{ $dependencia->id }}" @class(['opacity-60' => ! $dependencia->activa])>
                    <td class="text-ink">
                        <span style="padding-left: {{ $dependencia->profundidad * 18 }}px">
                            @if ($dependencia->profundidad > 0)<span class="text-ink-muted">└ </span>@endif
                            <span class="font-semibold">{{ $dependencia->nombre }}</span>
                            @if ($dependencia->sigla)<span class="text-[12px] text-ink-muted">({{ $dependencia->sigla }})</span>@endif
                        </span>
                    </td>
                    <td class="text-ink-muted">{{ $tipos[$dependencia->tipo] ?? $dependencia->tipo }}</td>
                    <td class="text-ink-muted">{{ $dependencia->proceso?->nombre ?? '—' }}</td>
                    <td>
                        @if ($dependencia->activa)
                            <x-ui.badge variant="success">{{ __('Activa') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral">{{ __('Inactiva') }}</x-ui.badge>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $dependencia->id }})">{{ __('Editar') }}</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="abrirEquivalencias({{ $dependencia->id }})">{{ __('Variantes') }}</x-ui.button>
                            <x-ui.button size="sm" variant="secondary" wire:click="alternarActiva({{ $dependencia->id }})">
                                {{ $dependencia->activa ? __('Desactivar') : __('Reactivar') }}
                            </x-ui.button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-ink-muted">{{ __('No hay dependencias para ese criterio.') }}</td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    {{-- Crear / editar --}}
    <x-ui.modal name="dependencia-form" :title="$editandoId ? __('Editar dependencia') : __('Nueva dependencia')">
        <form wire:submit="guardar" class="space-y-4">
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Nombre') }} *</label>
                <x-ui.input name="nombre" wire:model="nombre" required />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Nombre corto') }}</label>
                    <x-ui.input name="nombreCorto" wire:model="nombreCorto" />
                </div>
                <div>
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Sigla') }}</label>
                    <x-ui.input name="sigla" wire:model="sigla" />
                </div>
            </div>
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo') }} *</label>
                <select wire:model="tipo" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    @foreach ($tipos as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Depende de') }}</label>
                <select wire:model="padreId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('— Sin padre (raíz) —') }}</option>
                    @foreach ($opcionesPadre as $opcion)
                        @if ($opcion->id !== $editandoId)
                            <option value="{{ $opcion->id }}">{{ str_repeat('— ', $opcion->profundidad) }}{{ $opcion->nombre }}</option>
                        @endif
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('padreId')" class="mt-1" />
            </div>
            <div>
                <label class="text-[13px] font-semibold text-ink-label">{{ __('Proceso') }}</label>
                <select wire:model="procesoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                    <option value="">{{ __('— Ninguno —') }}</option>
                    @foreach ($procesos as $proceso)
                        <option value="{{ $proceso->id }}">{{ $proceso->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'dependencia-form')">{{ __('Cancelar') }}</x-ui.button>
            <x-ui.button variant="primary" wire:click="guardar">{{ __('Guardar') }}</x-ui.button>
        </x-slot>
    </x-ui.modal>

    {{-- Equivalencias --}}
    <x-ui.modal name="dependencia-equivalencias" :title="__('Variantes de texto')">
        @if ($dependenciaActiva)
            <p class="mb-3 text-[13px] text-ink-muted">
                {{ __('Variantes con que llega «:nombre» en los Excel de carga (RF-39).', ['nombre' => $dependenciaActiva->nombre]) }}
            </p>

            <form wire:submit="agregarEquivalencia" class="mb-4 flex items-end gap-2">
                <div class="flex-1">
                    <label class="text-[13px] font-semibold text-ink-label">{{ __('Nueva variante') }}</label>
                    <x-ui.input name="nuevaEquivalencia" wire:model="nuevaEquivalencia" placeholder="DIRECION DE SISTEMAS" />
                    <x-input-error :messages="$errors->get('nuevaEquivalencia')" class="mt-1" />
                </div>
                <x-ui.button type="submit" variant="success">{{ __('Agregar') }}</x-ui.button>
            </form>

            <div class="divide-y divide-line">
                @forelse ($equivalencias as $equivalencia)
                    <div class="flex items-center justify-between gap-3 py-2" wire:key="eq-{{ $equivalencia->id }}">
                        <span class="text-[14px] text-ink">{{ $equivalencia->texto }}</span>
                        <x-ui.button size="sm" variant="secondary" wire:click="eliminarEquivalencia({{ $equivalencia->id }})">{{ __('Quitar') }}</x-ui.button>
                    </div>
                @empty
                    <p class="py-4 text-center text-[13px] text-ink-muted">{{ __('Sin variantes registradas.') }}</p>
                @endforelse
            </div>
        @endif
        <x-slot name="footer">
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'dependencia-equivalencias')">{{ __('Cerrar') }}</x-ui.button>
        </x-slot>
    </x-ui.modal>
</div>
