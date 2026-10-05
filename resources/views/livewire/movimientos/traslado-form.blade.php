@php
    $asignacion = $equipo->asignacionActual;
@endphp

<div class="space-y-6">
    <x-ui.page-header
        :title="__('Traslado o cambio de responsable')"
        :subtitle="trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modelo ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : ''))"
    />

    <div class="grid grid-cols-1 gap-4 nav:grid-cols-2">
        <x-ui.card>
            <p class="section-title">{{ __('Desde (actual)') }}</p>
            <x-ui.definition-list :items="[
                __('Responsable') => $asignacion?->persona?->nombre ?? __('Sin asignar'),
                __('Sede / Piso') => $asignacion
                    ? trim(($asignacion->sede?->nombre ?? '—') . ' · ' . __('Piso :numero', ['numero' => $asignacion->piso?->numero ?? '—']))
                    : '—',
                __('Dependencia') => $asignacion?->dependencia?->nombre ?? '—',
            ]" class="mt-2" />
        </x-ui.card>

        <x-ui.card>
            <p class="section-title">{{ __('Hacia (nuevo)') }}</p>

            <label class="mt-3 flex items-center gap-2 text-[14px] text-ink">
                <input type="checkbox" wire:model.live="bodega" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('Dejar sin asignar (bodega)') }}
            </label>

            @if ($bodega)
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Sede de la bodega') }}</label>
                        <select wire:model="sedeId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('sedeId')" class="mt-1" />
                    </div>

                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }}</label>
                        <select wire:model="pisoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($pisos as $piso)
                                <option value="{{ $piso->id }}">{{ __('Piso :numero', ['numero' => $piso->numero]) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pisoId')" class="mt-1" />
                    </div>
                </div>
            @else
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.input name="nuevoResponsable" :label="__('Nuevo responsable')" wire:model="nuevoResponsable" required />
                    </div>

                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Sede') }}</label>
                        <select wire:model="sedeId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('sedeId')" class="mt-1" />
                    </div>

                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }}</label>
                        <select wire:model="pisoId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($pisos as $piso)
                                <option value="{{ $piso->id }}">{{ __('Piso :numero', ['numero' => $piso->numero]) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pisoId')" class="mt-1" />
                    </div>

                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }}</label>
                        <select wire:model="dependenciaId" class="mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($dependencias as $dependencia)
                                <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('dependenciaId')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.input type="date" name="fecha" :label="__('Fecha')" wire:model="fecha" required />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Motivo') }}</label>
                        <textarea wire:model="motivo" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
                    </div>
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card>
        <p class="section-title">{{ __('Documentos del traslado') }}</p>
        <p class="mt-1 text-[13px] text-ink-muted">
            {{ __('El traslado queda completo cuando los dos documentos estén firmados y subidos.') }}
        </p>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-line p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[14px] font-semibold text-ink">{{ __('Formato de baja') }}</p>
                    <x-ui.badge variant="warning">{{ __('Pendiente de firma') }}</x-ui.badge>
                </div>
                <p class="mt-1 text-[13px] text-ink-muted">
                    {{ __('Firma quien entrega: :nombre', ['nombre' => $asignacion?->persona?->nombre ?? __('Sin asignar')]) }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    {{-- TODO: los formatos PDF prellenados los genera el bloque de dompdf. --}}
                    <x-ui.button variant="secondary" size="sm" type="button">{{ __('Descargar PDF prellenado') }}</x-ui.button>
                    <x-ui.button variant="ghost" size="sm" type="button">{{ __('Subir documento firmado') }}</x-ui.button>
                </div>
            </div>

            <div class="rounded-lg border border-line p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[14px] font-semibold text-ink">{{ __('Formato de entrega') }}</p>
                    <x-ui.badge variant="warning">{{ __('Pendiente de firma') }}</x-ui.badge>
                </div>
                <p class="mt-1 text-[13px] text-ink-muted">
                    {{ __('Firma quien recibe: :nombre', ['nombre' => $bodega ? __('Bodega (sin responsable)') : ($nuevoResponsable !== '' ? $nuevoResponsable : __('Sin definir'))]) }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.button variant="secondary" size="sm" type="button">{{ __('Descargar PDF prellenado') }}</x-ui.button>
                    <x-ui.button variant="ghost" size="sm" type="button">{{ __('Subir documento firmado') }}</x-ui.button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('movimientos.traslados.index')">
            {{ __('Cancelar') }}
        </x-ui.button>
        <x-ui.button variant="primary" wire:click="guardar">
            {{ __('Guardar traslado') }}
        </x-ui.button>
    </div>
</div>
