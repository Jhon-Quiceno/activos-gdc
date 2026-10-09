@php
    $nombres = [
        'formato_baja' => __('Formato de baja'),
        'formato_entrega' => __('Formato de entrega'),
    ];
    $quienFirma = [
        'formato_baja' => $evento->tipo === 'traslado_responsable'
            ? __('Firma quien entrega: :nombre', ['nombre' => $firmantes['formato_baja']])
            : __('Firman: ingeniero de soporte y :nombre', ['nombre' => $firmantes['formato_baja']]),
        'formato_entrega' => __('Firma quien recibe: :nombre', ['nombre' => $firmantes['formato_entrega']]),
    ];
    $estadoEvento = match (true) {
        $evento->anulaciones->isNotEmpty() => ['variant' => 'neutral', 'label' => __('Anulado')],
        $evento->estado_firma === 'completo' => ['variant' => 'success', 'label' => __('Completo')],
        $evento->estado_firma === 'pendiente_de_firma' => ['variant' => 'warning', 'label' => __('Pendiente de firma')],
        default => ['variant' => 'neutral', 'label' => __('Sin formatos')],
    };
    $acepta = '.pdf,.jpg,.jpeg,.png';
@endphp

<div class="space-y-4">
    <x-ui.card>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="section-title">{{ __('Documentos del evento #:id', ['id' => $evento->id]) }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">
                    @if ($evento->tipo === 'traslado_responsable')
                        {{ __('El traslado queda completo cuando los dos documentos estén firmados y subidos.') }}
                    @elseif ($evento->tipo === 'baja')
                        {{ __('Al subir el formato de baja firmado, el equipo pasa a «Dado de baja».') }}
                    @else
                        {{ __('El evento queda completo al subir el formato firmado.') }}
                    @endif
                    {{ __('Archivos PDF, JPG o PNG de máximo :mb MB.', ['mb' => $maxMb]) }}
                </p>
            </div>
            <x-ui.badge :variant="$estadoEvento['variant']">{{ $estadoEvento['label'] }}</x-ui.badge>
        </div>

        @if ($mensaje)
            <div class="mt-4 rounded-lg border border-line bg-success-bg px-4 py-3 text-[14px] text-success-text" role="status">
                {{ $mensaje }}
            </div>
        @endif

        <x-input-error :messages="$errors->get('archivo')" class="mt-3" />

        @if ($admiteUnico && $pendiente)
            <label class="mt-4 flex items-center gap-2 text-[14px] text-ink">
                <input type="checkbox" wire:model.live="usarDocumentoUnico" class="rounded border-line-input text-primary focus:ring-primary">
                {{ __('La Dirección TIC aprobó un documento único (reemplaza a los dos formatos)') }}
            </label>
        @endif

        @if ($documentoUnico)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-line p-4">
                <div>
                    <p class="text-[14px] font-semibold text-ink">{{ __('Documento único firmado') }}</p>
                    <p class="text-[13px] text-ink-muted">{{ __('Subido el :fecha', ['fecha' => $documentoUnico->fecha->format('d/m/Y')]) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.badge variant="success">{{ __('Firmado') }}</x-ui.badge>
                    <x-ui.button variant="secondary" size="sm" wire:click="descargarDocumento({{ $documentoUnico->id }})">{{ __('Ver firmado') }}</x-ui.button>
                </div>
            </div>
        @elseif ($usarDocumentoUnico && $pendiente)
            <div class="mt-4 rounded-lg border border-line p-4">
                <p class="text-[14px] font-semibold text-ink">{{ __('Documento único') }}</p>
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Debe llevar la firma de quien entrega y de quien recibe.') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <input type="file" wire:model="archivoUnico" accept="{{ $acepta }}" class="text-[13px] text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-info-bg file:px-3 file:py-2 file:text-[13px] file:font-semibold file:text-primary">
                    <x-ui.button variant="primary" size="sm" wire:click="subirUnico" wire:loading.attr="disabled" wire:target="archivoUnico,subirUnico">
                        {{ __('Subir documento único') }}
                    </x-ui.button>
                </div>
                <div wire:loading wire:target="archivoUnico" class="mt-1 text-[13px] text-ink-muted">{{ __('Cargando archivo…') }}</div>
                <x-input-error :messages="$errors->get('archivoUnico')" class="mt-1" />
            </div>
        @endif

        @if (! $documentoUnico && ! ($usarDocumentoUnico && $pendiente))
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @forelse ($estado as $tipo => $docs)
                    <div class="rounded-lg border border-line p-4" wire:key="doc-{{ $evento->id }}-{{ $tipo }}">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[14px] font-semibold text-ink">{{ $nombres[$tipo] ?? $tipo }}</p>
                            @if ($docs['firmado'])
                                <x-ui.badge variant="success">{{ __('Firmado') }}</x-ui.badge>
                            @elseif ($pendiente)
                                <x-ui.badge variant="warning">{{ __('Pendiente de firma') }}</x-ui.badge>
                            @else
                                <x-ui.badge variant="neutral">{{ __('Sin firmar') }}</x-ui.badge>
                            @endif
                        </div>
                        <p class="mt-1 text-[13px] text-ink-muted">{{ $quienFirma[$tipo] ?? '' }}</p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.button variant="secondary" size="sm" wire:click="descargarPrellenado('{{ $tipo }}')">
                                {{ __('Descargar PDF prellenado') }}
                            </x-ui.button>
                            @if ($docs['firmado'])
                                <x-ui.button variant="ghost" size="sm" wire:click="descargarDocumento({{ $docs['firmado']->id }})">
                                    {{ __('Ver firmado') }}
                                </x-ui.button>
                            @endif
                        </div>

                        @if ($pendiente && ! $docs['firmado'])
                            <div class="mt-3 space-y-2">
                                <input type="file" wire:model="archivos.{{ $tipo }}" accept="{{ $acepta }}" class="block w-full text-[13px] text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-info-bg file:px-3 file:py-2 file:text-[13px] file:font-semibold file:text-primary">
                                <div wire:loading wire:target="archivos.{{ $tipo }}" class="text-[13px] text-ink-muted">{{ __('Cargando archivo…') }}</div>
                                <x-ui.button variant="primary" size="sm" wire:click="subir('{{ $tipo }}')" wire:loading.attr="disabled" wire:target="archivos.{{ $tipo }},subir">
                                    {{ __('Subir documento firmado') }}
                                </x-ui.button>
                                <x-input-error :messages="$errors->get('archivos.'.$tipo)" class="mt-1" />
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-[14px] text-ink-muted">{{ __('Este evento no requiere documentos firmados.') }}</p>
                @endforelse
            </div>
        @endif

        @if ($evidencias->isNotEmpty())
            <div class="mt-4">
                <p class="text-[13px] font-semibold text-ink-label">{{ __('Evidencias') }}</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($evidencias as $evidencia)
                        <li>
                            <button type="button" wire:click="descargarDocumento({{ $evidencia->id }})" class="rounded-full bg-neutral-bg px-3 py-1 text-[13px] font-semibold text-neutral-text hover:bg-info-bg hover:text-primary">
                                {{ $evidencia->tipo === 'foto' ? __('Foto') : __('Archivo') }} · {{ basename($evidencia->archivo_path) }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-ui.card>
</div>
