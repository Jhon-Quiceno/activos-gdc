{{--
    Vista de App\Livewire\Qr\Etiquetas: hoja de etiquetas QR para imprimir.
    La barra de arriba no sale en la impresión; las etiquetas llevan un borde
    punteado como guía de corte.
--}}
<div>
    <style>
        .etq-hoja { display: flex; flex-wrap: wrap; gap: 4mm; }
        .etq { width: 60mm; height: 35mm; box-sizing: border-box; border: 1px dashed #9AA5B1; padding: 2.5mm;
               display: flex; gap: 2.5mm; align-items: center; background: #fff; color: #000; page-break-inside: avoid;
               font-family: Verdana, 'DejaVu Sans', Arial, sans-serif; }
        .etq-qr svg { width: 26mm; height: 26mm; display: block; }
        .etq-datos { min-width: 0; font-size: 6.5pt; line-height: 1.3; }
        .etq-entidad { font-weight: bold; font-size: 6pt; text-transform: uppercase; letter-spacing: .2px; }
        .etq-tipo { font-weight: bold; font-size: 8pt; margin-top: 1mm; }
        .etq-valor { font-family: 'DejaVu Sans Mono', monospace; font-size: 7pt; word-break: break-all; }
        @media print {
            @page { size: letter portrait; margin: 10mm; }
            .etq-barra { display: none !important; }
            body, .etq-fondo { background: #fff !important; }
            .etq-fondo { padding: 0 !important; }
        }
    </style>

    <div class="etq-barra sticky top-0 z-10 flex flex-wrap items-center gap-3 border-b border-line bg-white px-6 py-3 shadow-sm">
        <p class="font-display text-[16px] font-semibold text-ink">
            {{ trans_choice(':count etiqueta QR|:count etiquetas QR', $etiquetas->count(), ['count' => $etiquetas->count()]) }}
        </p>
        <p class="text-[13px] text-ink-muted">{{ __('6 × 3,5 cm. Imprime en material adhesivo resistente (poliéster o vinilo).') }}</p>
        @if ($mensaje)
            <span class="rounded-lg bg-success-bg px-3 py-1 text-[13px] text-success-text">{{ $mensaje }}</span>
        @endif
        <div class="ml-auto flex gap-2">
            <x-ui.button variant="secondary" size="sm" onclick="window.close()">{{ __('Cerrar') }}</x-ui.button>
            <x-ui.button variant="primary" size="sm" wire:click="imprimir" :disabled="$etiquetas->isEmpty()">
                {{ __('Imprimir') }}
            </x-ui.button>
        </div>
    </div>

    <div class="etq-fondo p-6">
        @if ($etiquetas->isEmpty())
            <p class="text-[14px] text-ink-muted">{{ __('No hay equipos para imprimir. Elígelos desde la hoja de vida o desde «Etiquetas QR».') }}</p>
        @else
            <div class="etq-hoja">
                @foreach ($etiquetas as $etiqueta)
                    @php($equipo = $etiqueta['equipo'])
                    <div class="etq" wire:key="etq-{{ $equipo->id }}">
                        <div class="etq-qr">{{ $etiqueta['qr'] }}</div>
                        <div class="etq-datos">
                            <div class="etq-entidad">{{ __('Gobernación de Córdoba · Dirección TIC') }}</div>
                            <div class="etq-tipo">{{ $equipo->tipoEquipo?->nombre }}</div>
                            <div>{{ __('Serial') }}:</div>
                            <div class="etq-valor">{{ $equipo->serial }}</div>
                            <div>{{ __('Código') }}:</div>
                            <div class="etq-valor">{{ $equipo->codigo_activo ?? __('Sin código') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
