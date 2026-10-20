{{-- Vista de App\Livewire\Levantamientos\Index: lista y creación de levantamientos (RF-59, CU-18). --}}
@php
    $claseSelect = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
    $estadoEstilos = ['en_curso' => 'info', 'pendiente_de_firma' => 'warning', 'aceptado' => 'success'];
@endphp

<div class="space-y-6">
    <div class="flex justify-end">
        <x-ui.button variant="primary" wire:click="$toggle('mostrarFormulario')">{{ __('Nuevo levantamiento') }}</x-ui.button>
    </div>

    @if ($mostrarFormulario)
        <x-ui.card>
            <p class="section-title">{{ __('Nuevo levantamiento') }}</p>
            <p class="mt-1 text-[13px] text-ink-muted">{{ __('Incluye los equipos asignados a la dependencia y a todas las que cuelgan de ella, en la sede elegida.') }}</p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="dependenciaId" class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }} *</label>
                    <select id="dependenciaId" wire:model.live="dependenciaId" class="{{ $claseSelect }}">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($dependencias as $dependencia)
                            <option value="{{ $dependencia->id }}">{{ str_repeat("\u{00A0}\u{00A0}\u{00A0}", $dependencia->profundidad) }}{{ $dependencia->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('dependenciaId')" class="mt-1" />
                </div>

                <div>
                    <label for="sedeId" class="text-[13px] font-semibold text-ink-label">{{ __('Sede') }} *</label>
                    <select id="sedeId" wire:model="sedeId" class="{{ $claseSelect }}">
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('sedeId')" class="mt-1" />
                </div>

                <div>
                    <label for="procesoId" class="text-[13px] font-semibold text-ink-label">{{ __('Proceso') }}</label>
                    <select id="procesoId" wire:model="procesoId" class="{{ $claseSelect }}">
                        <option value="">{{ __('El de la dependencia') }}</option>
                        @foreach ($procesos as $proceso)
                            <option value="{{ $proceso->id }}">{{ $proceso->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div></div>

                <div>
                    <label for="propietarioId" class="text-[13px] font-semibold text-ink-label">{{ __('Propietario (director o líder del proceso)') }}</label>
                    <select id="propietarioId" wire:model="propietarioId" class="{{ $claseSelect }}" @disabled(! $dependenciaId)>
                        <option value="">{{ $dependenciaId ? __('Selecciona…') : __('Elige primero la dependencia') }}</option>
                        @foreach ($personas as $persona)
                            <option value="{{ $persona->id }}">{{ $persona->nombreCompleto() }}{{ $persona->cargoTexto() ? ' · '.$persona->cargoTexto() : '' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('propietarioId')" class="mt-1" />
                </div>

                <div>
                    <label for="custodioId" class="text-[13px] font-semibold text-ink-label">{{ __('Custodio') }}</label>
                    <select id="custodioId" wire:model="custodioId" class="{{ $claseSelect }}" @disabled(! $dependenciaId)>
                        <option value="">{{ $dependenciaId ? __('Selecciona…') : __('Elige primero la dependencia') }}</option>
                        @foreach ($personas as $persona)
                            <option value="{{ $persona->id }}">{{ $persona->nombreCompleto() }}{{ $persona->cargoTexto() ? ' · '.$persona->cargoTexto() : '' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('custodioId')" class="mt-1" />
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <x-ui.button variant="secondary" wire:click="$set('mostrarFormulario', false)">{{ __('Cancelar') }}</x-ui.button>
                <x-ui.button variant="primary" wire:click="crear">{{ __('Crear y empezar') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-ui.table>
        <thead>
            <tr>
                <th>{{ __('Código') }}</th>
                <th>{{ __('Dependencia') }}</th>
                <th>{{ __('Proceso') }}</th>
                <th>{{ __('Sede') }}</th>
                <th>{{ __('Inicio') }}</th>
                <th>{{ __('Valoraciones') }}</th>
                <th>{{ __('Estado') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($levantamientos as $levantamiento)
                <tr wire:key="levantamiento-{{ $levantamiento->id }}" onclick="window.location.href='{{ route('levantamientos.show', $levantamiento) }}'" class="cursor-pointer">
                    <td class="font-mono font-semibold">{{ $levantamiento->codigo }}</td>
                    <td>{{ $levantamiento->dependencia?->nombre }}</td>
                    <td>{{ $levantamiento->proceso?->nombre ?? '—' }}</td>
                    <td>{{ $levantamiento->sede?->nombre }}</td>
                    <td>{{ $levantamiento->fecha_inicio?->format('d/m/Y') }}</td>
                    <td>{{ $levantamiento->valoraciones_count }}</td>
                    <td><x-ui.badge :variant="$estadoEstilos[$levantamiento->estado] ?? 'neutral'">{{ __(\App\Models\LevantamientoActivos::ESTADOS[$levantamiento->estado] ?? $levantamiento->estado) }}</x-ui.badge></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-10 text-center text-ink-muted">
                        {{ __('Todavía no hay levantamientos. Crea uno por dependencia y sede para verificar y valorar sus equipos y cerrar con el acta de aceptación firmada.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>

        @if ($levantamientos->hasPages())
            <x-slot name="pagination">{{ $levantamientos->links() }}</x-slot>
        @endif
    </x-ui.table>
</div>
