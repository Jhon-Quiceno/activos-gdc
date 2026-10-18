@php
    use App\Livewire\Movimientos\Soporte\Cedula;

    $asignacion = $equipo->asignacionActual;
    $selectClass = 'mt-1 h-11 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary';
    $vinculaciones = ['planta' => __('Planta'), 'contratista' => __('Contratista')];
@endphp

<div class="space-y-6">
    <x-ui.page-header
        :title="__('Traslado o cambio de responsable')"
        :subtitle="trim(($equipo->tipoEquipo?->nombre ?? '') . ' · ' . ($equipo->marca?->nombre ?? '') . ' ' . ($equipo->modelo ?? '') . ' · ' . $equipo->serial . ($equipo->codigo_activo ? ' · ' . $equipo->codigo_activo : ''))"
    />

    @if ($eventoId)
        {{-- Paso 2: el traslado ya quedó registrado; ahora se gestionan las firmas. --}}
        <div class="rounded-lg border border-line bg-success-bg px-4 py-3 text-[14px] text-success-text" role="status">
            {{ __('Traslado registrado. Descarga los dos formatos prellenados, hazlos firmar y súbelos para completar el evento.') }}
        </div>

        <x-ui.card>
            <p class="section-title">{{ __('Ahora (nuevo)') }}</p>
            <x-ui.definition-list :items="[
                __('Responsable') => $asignacion?->persona?->nombre ?? __('Sin asignar (bodega)'),
                __('Sede / Piso') => $asignacion
                    ? trim(($asignacion->sede?->nombre ?? '—') . ' · ' . __('Piso :numero', ['numero' => $asignacion->piso?->numero ?? '—']))
                    : '—',
                __('Dependencia') => $asignacion?->dependencia?->nombre ?? '—',
            ]" class="mt-2" />
        </x-ui.card>

        <livewire:movimientos.documentos-evento :evento-id="$eventoId" :key="'docs-traslado-'.$eventoId" />

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('movimientos.traslados.index')">
                {{ __('Volver a traslados') }}
            </x-ui.button>
            <x-ui.button variant="primary" :href="route('equipos.show', $equipo)">
                {{ __('Ver hoja de vida') }}
            </x-ui.button>
        </div>
    @elseif ($dadoDeBaja || $bajaEnTramite)
        <div class="rounded-lg border border-[#EBC7C7] bg-danger-bg px-4 py-3 text-[14px] text-[#7E2A2A]">
            @if ($dadoDeBaja)
                {{ __('Este equipo está dado de baja: no admite traslados. Si la baja fue un error, anúlala desde la pantalla de baja.') }}
            @else
                {{ __('Este equipo tiene una baja en trámite (pendiente de firma). Completa o anula la baja antes de trasladarlo.') }}
            @endif
        </div>
        <div class="flex justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('equipos.show', $equipo)">{{ __('Ver hoja de vida') }}</x-ui.button>
            <x-ui.button variant="danger" :href="route('movimientos.baja', $equipo)">{{ __('Ir a la baja') }}</x-ui.button>
        </div>
    @else
        <x-input-error :messages="$errors->get('equipo')" />

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

                @unless ($bodega)
                    <div class="mt-4">
                        <x-ui.segmented-control
                            :options="['existente' => __('Persona registrada'), 'nueva' => __('Persona nueva')]"
                            :selected="$modoResponsable"
                            model="modoResponsable"
                        />
                    </div>

                    @if ($modoResponsable === 'existente')
                        <div class="mt-4">
                            @if ($personaElegida)
                                <div class="flex items-start justify-between gap-3 rounded-lg border border-line p-3">
                                    <div>
                                        <p class="text-[14px] font-semibold text-ink">{{ $personaElegida->nombre }}</p>
                                        <p class="text-[13px] text-ink-muted">
                                            {{ __('C.C.') }} {{ Cedula::enmascarar($personaElegida->cedula) }}
                                            · {{ $personaElegida->cargo ?? __('Sin cargo') }}
                                            · {{ $vinculaciones[$personaElegida->tipo_vinculacion] ?? __('Sin vinculación') }}
                                        </p>
                                    </div>
                                    <x-ui.button variant="ghost" size="sm" wire:click="$set('personaId', null)">{{ __('Cambiar') }}</x-ui.button>
                                </div>
                            @else
                                <x-ui.input name="buscarPersona" :label="__('Nuevo responsable')" wire:model.live.debounce.300ms="buscarPersona" placeholder="{{ __('Buscar por nombre o cédula…') }}" autocomplete="off" />
                                @if ($coincidencias->isNotEmpty())
                                    <ul class="mt-2 divide-y divide-line rounded-lg border border-line">
                                        @foreach ($coincidencias as $persona)
                                            <li wire:key="persona-{{ $persona->id }}">
                                                <button type="button" wire:click="elegirPersona({{ $persona->id }})" class="w-full px-3 py-2 text-left hover:bg-app-bg">
                                                    <span class="text-[14px] font-semibold text-ink">{{ $persona->nombre }}</span>
                                                    <span class="block text-[13px] text-ink-muted">{{ __('C.C.') }} {{ Cedula::enmascarar($persona->cedula) }} · {{ $persona->dependencia?->nombre ?? __('Sin dependencia') }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif (mb_strlen(trim($buscarPersona)) >= 2)
                                    <p class="mt-2 text-[13px] text-ink-muted">
                                        {{ __('No hay personas con ese nombre o cédula.') }}
                                        <button type="button" class="font-semibold text-primary" wire:click="$set('modoResponsable', 'nueva')">{{ __('Registrar persona nueva') }}</button>
                                    </p>
                                @endif
                                <x-input-error :messages="$errors->get('personaId')" class="mt-1" />
                            @endif
                        </div>
                    @else
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-ui.input class="sm:col-span-2" name="nombre" :label="__('Nombre completo')" wire:model="nombre" required />
                            <x-ui.input name="cedula" :label="__('Cédula')" wire:model="cedula" inputmode="numeric" required />
                            <x-ui.input name="cargo" :label="__('Cargo')" wire:model="cargo" required />
                            <div class="sm:col-span-2">
                                <label class="text-[13px] font-semibold text-ink-label">{{ __('Tipo de vinculación') }}</label>
                                <select wire:model="tipoVinculacion" class="{{ $selectClass }}">
                                    <option value="">{{ __('Selecciona…') }}</option>
                                    @foreach ($vinculaciones as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('tipoVinculacion')" class="mt-1" />
                            </div>
                        </div>
                    @endif
                @endunless

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ $bodega ? __('Sede de la bodega') : __('Sede') }}</label>
                        <select wire:model="sedeId" class="{{ $selectClass }}">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('sedeId')" class="mt-1" />
                    </div>

                    <div>
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Piso') }}</label>
                        <select wire:model="pisoId" class="{{ $selectClass }}">
                            <option value="">{{ __('Selecciona…') }}</option>
                            @foreach ($pisos as $piso)
                                <option value="{{ $piso->id }}">{{ __('Piso :numero', ['numero' => $piso->numero]) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pisoId')" class="mt-1" />
                    </div>

                    @unless ($bodega)
                        <div class="sm:col-span-2">
                            <label class="text-[13px] font-semibold text-ink-label">{{ __('Dependencia') }}</label>
                            <select wire:model="dependenciaId" class="{{ $selectClass }}">
                                <option value="">{{ __('Selecciona…') }}</option>
                                @foreach ($dependencias as $dependencia)
                                    <option value="{{ $dependencia->id }}">{{ $dependencia->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('dependenciaId')" class="mt-1" />
                        </div>
                    @endunless

                    <x-ui.input type="date" name="fecha" :label="__('Fecha')" wire:model="fecha" max="{{ now()->toDateString() }}" required />

                    <div class="sm:col-span-2">
                        <label class="text-[13px] font-semibold text-ink-label">{{ __('Motivo') }}</label>
                        <textarea wire:model="motivo" rows="3" class="mt-1 w-full rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"></textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
                    </div>
                </div>
            </x-ui.card>
        </div>

        <x-ui.card>
            <p class="section-title">{{ __('Documentos del traslado') }}</p>
            <p class="mt-1 text-[13px] text-ink-muted">
                {{ __('Al guardar se generan dos formatos prellenados: el formato de baja (firma :entrega, quien entrega) y el formato de entrega (firma quien recibe). El traslado queda «Pendiente de firma» hasta subir los dos firmados, o el documento único si la Dirección TIC lo aprueba.', ['entrega' => $asignacion?->persona?->nombre ?? __('el área de sistemas')]) }}
            </p>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="secondary" :href="route('movimientos.traslados.index')">
                {{ __('Cancelar') }}
            </x-ui.button>
            <x-ui.button variant="primary" wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar">
                {{ __('Guardar traslado') }}
            </x-ui.button>
        </div>
    @endif
</div>
