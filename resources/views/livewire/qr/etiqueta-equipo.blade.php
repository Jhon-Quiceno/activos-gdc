{{-- Vista de App\Livewire\Qr\EtiquetaEquipo (tarjeta de la hoja de vida). --}}
<div>
    <x-ui.card>
        <div class="flex items-center justify-between gap-3">
            <p class="section-title">{{ __('Etiqueta QR') }}</p>
            @if ($impresiones->isEmpty())
                <x-ui.badge variant="warning">{{ __('Sin imprimir') }}</x-ui.badge>
            @else
                <x-ui.badge variant="success">{{ trans_choice('Impresa :count vez|Impresa :count veces', $impresiones->count(), ['count' => $impresiones->count()]) }}</x-ui.badge>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-4">
            <div class="shrink-0 rounded-lg border border-line bg-white p-1.5" aria-label="{{ __('Código QR del equipo') }}">
                {{ $qr }}
            </div>

            <div class="min-w-0 flex-1 space-y-2 text-[13px] text-ink-muted">
                <p>{{ __('Al escanearlo con el celular se abre esta hoja de vida (pide iniciar sesión si hace falta).') }}</p>
                <p class="break-all font-mono text-[12px] text-ink">{{ $enlace }}</p>
                @if ($ultima = $impresiones->first())
                    <p>
                        {{ __('Última impresión: :fecha por :usuario (:motivo).', [
                            'fecha' => $ultima->fecha_impresion->format('d/m/Y H:i'),
                            'usuario' => $ultima->usuario?->name ?? '—',
                            'motivo' => $ultima->motivo === 'reposicion' ? __('reposición') : __('primera impresión'),
                        ]) }}
                    </p>
                @endif

                <x-ui.button variant="secondary" size="sm" :href="route('qr.etiquetas', ['equipos' => $equipo->id])" target="_blank">
                    {{ $impresiones->isEmpty() ? __('Imprimir etiqueta') : __('Reimprimir etiqueta') }}
                </x-ui.button>
            </div>
        </div>
    </x-ui.card>
</div>
