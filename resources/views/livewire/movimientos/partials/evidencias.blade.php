{{--
    Campo de evidencias (RF-24) compartido por Diagnóstico y Baja. El
    componente que lo incluye debe usar WithFileUploads, tener la propiedad
    `array $evidencias`, el método `quitarEvidencia(int)` y pasar `$maxMb`.
--}}
<div class="mt-4">
    <label class="text-[13px] font-semibold text-ink-label">{{ __('Evidencias') }}</label>
    <label class="mt-1 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-line-input px-4 py-4 text-[14px] text-ink-muted hover:border-primary hover:text-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V9M7 14l5-5 5 5"></path><path d="M4 3h16"></path></svg>
        {{ __('Haz clic para subir fotos o PDF (máx. :mb MB cada uno)', ['mb' => $maxMb]) }}
        <input type="file" wire:model="evidencias" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden">
    </label>
    <div wire:loading wire:target="evidencias" class="mt-1 text-[13px] text-ink-muted">{{ __('Cargando archivos…') }}</div>

    @if (count($evidencias))
        <ul class="mt-2 flex flex-wrap gap-2">
            @foreach ($evidencias as $indice => $archivo)
                <li wire:key="evidencia-{{ $indice }}" class="inline-flex items-center gap-2 rounded-full bg-neutral-bg px-3 py-1 text-[13px] font-semibold text-neutral-text">
                    {{ method_exists($archivo, 'getClientOriginalName') ? $archivo->getClientOriginalName() : __('Archivo') }}
                    <button type="button" wire:click="quitarEvidencia({{ $indice }})" class="text-ink-muted hover:text-danger-text" aria-label="{{ __('Quitar') }}">&times;</button>
                </li>
            @endforeach
        </ul>
    @endif

    <x-input-error :messages="$errors->get('evidencias')" class="mt-1" />
    @foreach ($errors->get('evidencias.*') as $mensajes)
        <x-input-error :messages="$mensajes" class="mt-1" />
    @endforeach
</div>
