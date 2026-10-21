<div>
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-bg px-4 py-3 text-[14px] text-success-text">{{ session('status') }}</div>
    @endif

    {{-- Control segmentado --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($segmentos as $clave => $etiqueta)
            <button type="button" wire:click="seleccionar('{{ $clave }}')"
                class="rounded-lg px-3 py-2 text-[14px] font-semibold transition-colors
                    {{ $segmento === $clave ? 'bg-primary text-white' : 'bg-neutral-bg text-neutral-text hover:bg-app-bg' }}">
                {{ __($etiqueta) }}
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Alta / edición --}}
        <x-ui.card class="lg:col-span-1 h-fit">
            <p class="section-title">{{ $editandoId ? __('Editar') : __('Agregar') }}</p>
            <form wire:submit="guardar" class="mt-4 space-y-3">
                @switch($segmento)
                    @case('sedes')
                        <x-ui.input name="form.nombre" :label="__('Nombre') . ' *'" wire:model="form.nombre" />
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Municipio') }} *</label>
                            <select wire:model="form.municipio_id" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($municipios as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach
                            </select>
                            <x-input-error :messages="$errors->get('form.municipio_id')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo') }} *</label>
                                <select wire:model="form.tipo" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                    @foreach ($tiposSede as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[13px] font-semibold text-ink-label">{{ __('Tenencia') }} *</label>
                                <select wire:model="form.tenencia" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                    @foreach ($tenencias as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <x-ui.input name="form.direccion" :label="__('Dirección')" wire:model="form.direccion" />
                        <label class="flex items-center gap-2 text-[13px] text-ink-muted">
                            <input type="checkbox" wire:model="form.direccion_reservada" class="rounded border-line-input text-primary">
                            {{ __('Dirección reservada (RN-28: no se muestra)') }}
                        </label>
                        @break
                    @case('municipios')
                        <x-ui.input name="form.codigo_dane" :label="__('Código DANE') . ' *'" wire:model="form.codigo_dane" />
                        <x-ui.input name="form.nombre" :label="__('Nombre') . ' *'" wire:model="form.nombre" />
                        @break
                    @case('procesos')
                        <x-ui.input name="form.nombre" :label="__('Nombre') . ' *'" wire:model="form.nombre" />
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo') }} *</label>
                            <select wire:model="form.tipo" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                @foreach ($tiposProceso as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
                            </select>
                        </div>
                        @break
                    @case('cargos')
                        <x-ui.input name="form.codigo" :label="__('Código')" wire:model="form.codigo" />
                        <x-ui.input name="form.denominacion" :label="__('Denominación') . ' *'" wire:model="form.denominacion" />
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Nivel') }} *</label>
                            <select wire:model="form.nivel" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                @foreach ($nivelesCargo as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach
                            </select>
                        </div>
                        @break
                    @case('proveedores')
                        <x-ui.input name="form.razon_social" :label="__('Razón social') . ' *'" wire:model="form.razon_social" />
                        <x-ui.input name="form.nit" :label="__('NIT')" wire:model="form.nit" />
                        <x-ui.input name="form.telefono" :label="__('Teléfono')" wire:model="form.telefono" />
                        <x-ui.input name="form.correo" type="email" :label="__('Correo')" wire:model="form.correo" />
                        @break
                    @case('modelos')
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Marca') }} *</label>
                            <select wire:model="form.marca_id" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($marcas as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach
                            </select>
                            <x-input-error :messages="$errors->get('form.marca_id')" class="mt-1" />
                        </div>
                        <div>
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de equipo') }} *</label>
                            <select wire:model="form.tipo_equipo_id" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px]">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($tiposEquipo as $t)<option value="{{ $t->id }}">{{ $t->nombre }}</option>@endforeach
                            </select>
                            <x-input-error :messages="$errors->get('form.tipo_equipo_id')" class="mt-1" />
                        </div>
                        <x-ui.input name="form.nombre" :label="__('Modelo') . ' *'" wire:model="form.nombre" />
                        @break
                @endswitch

                <div class="flex items-center gap-2 pt-1">
                    <x-ui.button type="submit" variant="success">{{ $editandoId ? __('Guardar') : __('Agregar') }}</x-ui.button>
                    @if ($editandoId)
                        <x-ui.button type="button" variant="secondary" wire:click="cancelarEdicion">{{ __('Cancelar') }}</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        {{-- Listado --}}
        <div class="lg:col-span-2">
            <x-ui.table>
                <thead>
                    <tr>
                        @switch($segmento)
                            @case('sedes')<th>{{ __('Sede') }}</th><th>{{ __('Municipio') }}</th><th>{{ __('Pisos') }}</th><th>{{ __('Estado') }}</th><th></th>@break
                            @case('municipios')<th>{{ __('Código DANE') }}</th><th>{{ __('Nombre') }}</th><th></th>@break
                            @case('procesos')<th>{{ __('Proceso') }}</th><th>{{ __('Tipo') }}</th><th>{{ __('Estado') }}</th><th></th>@break
                            @case('cargos')<th>{{ __('Código') }}</th><th>{{ __('Denominación') }}</th><th>{{ __('Nivel') }}</th><th></th>@break
                            @case('proveedores')<th>{{ __('Razón social') }}</th><th>{{ __('NIT') }}</th><th>{{ __('Contacto') }}</th><th></th>@break
                            @case('modelos')<th>{{ __('Modelo') }}</th><th>{{ __('Marca') }}</th><th>{{ __('Tipo') }}</th><th></th>@break
                        @endswitch
                    </tr>
                </thead>
                <tbody>
                    @forelse ($datos as $fila)
                        <tr wire:key="{{ $segmento }}-{{ $fila->id }}">
                            @switch($segmento)
                                @case('sedes')
                                    <td class="font-semibold text-ink">{{ $fila->nombre }}</td>
                                    <td class="text-ink-muted">{{ $fila->municipio?->nombre }}</td>
                                    <td class="text-ink-muted">
                                        {{ $fila->pisos->map(fn ($p) => $p->etiqueta())->implode(', ') ?: '—' }}
                                        <button wire:click="agregarPiso({{ $fila->id }})" class="ml-1 text-[12px] font-semibold text-primary">+ {{ __('piso') }}</button>
                                        @if ($pisoSedeId === $fila->id)
                                            <div class="mt-1 flex items-center gap-1">
                                                <input wire:model="pisoNumero" type="number" placeholder="{{ __('N.º') }}" class="h-8 w-16 rounded border-line-input text-[13px]">
                                                <input wire:model="pisoNombre" placeholder="{{ __('Nombre (opc.)') }}" class="h-8 w-28 rounded border-line-input text-[13px]">
                                            </div>
                                            <x-input-error :messages="$errors->get('pisoNumero')" class="mt-1" />
                                        @endif
                                    </td>
                                    <td>@if ($fila->activa)<x-ui.badge variant="success">{{ __('Activa') }}</x-ui.badge>@else<x-ui.badge variant="neutral">{{ __('Inactiva') }}</x-ui.badge>@endif</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $fila->id }})">{{ __('Editar') }}</x-ui.button>
                                            <x-ui.button size="sm" variant="secondary" wire:click="alternarActiva({{ $fila->id }})">{{ $fila->activa ? __('Desactivar') : __('Activar') }}</x-ui.button>
                                        </div>
                                    </td>
                                    @break
                                @case('municipios')
                                    <td class="font-mono">{{ $fila->codigo_dane }}</td><td class="font-semibold text-ink">{{ $fila->nombre }}</td>
                                    <td><x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $fila->id }})">{{ __('Editar') }}</x-ui.button></td>
                                    @break
                                @case('procesos')
                                    <td class="font-semibold text-ink">{{ $fila->nombre }}</td><td class="text-ink-muted">{{ $tiposProceso[$fila->tipo] ?? $fila->tipo }}</td>
                                    <td>@if ($fila->activo)<x-ui.badge variant="success">{{ __('Activo') }}</x-ui.badge>@else<x-ui.badge variant="neutral">{{ __('Inactivo') }}</x-ui.badge>@endif</td>
                                    <td><div class="flex gap-2"><x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $fila->id }})">{{ __('Editar') }}</x-ui.button><x-ui.button size="sm" variant="secondary" wire:click="alternarActiva({{ $fila->id }})">{{ $fila->activo ? __('Desactivar') : __('Activar') }}</x-ui.button></div></td>
                                    @break
                                @case('cargos')
                                    <td class="font-mono">{{ $fila->codigo }}</td><td class="font-semibold text-ink">{{ $fila->denominacion }}</td><td class="text-ink-muted">{{ $nivelesCargo[$fila->nivel] ?? $fila->nivel }}</td>
                                    <td><x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $fila->id }})">{{ __('Editar') }}</x-ui.button></td>
                                    @break
                                @case('proveedores')
                                    <td class="font-semibold text-ink">{{ $fila->razon_social }}</td><td class="font-mono">{{ $fila->nit }}</td><td class="text-ink-muted">{{ trim($fila->telefono . ' ' . $fila->correo) ?: '—' }}</td>
                                    <td><x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $fila->id }})">{{ __('Editar') }}</x-ui.button></td>
                                    @break
                                @case('modelos')
                                    <td class="font-semibold text-ink">{{ $fila->nombre }}</td><td class="text-ink-muted">{{ $fila->marca?->nombre }}</td><td class="text-ink-muted">{{ $fila->tipoEquipo?->nombre }}</td>
                                    <td><x-ui.button size="sm" variant="secondary" wire:click="abrirEdicion({{ $fila->id }})">{{ __('Editar') }}</x-ui.button></td>
                                    @break
                            @endswitch
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-10 text-center text-ink-muted">{{ __('Todavía no hay elementos.') }}</td></tr>
                    @endforelse
                </tbody>
            </x-ui.table>
        </div>
    </div>
</div>
