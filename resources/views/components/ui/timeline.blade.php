{{--
    Timeline vertical para el historial cronológico de la hoja de vida.
    Acepta cualquier colección/array de eventos (array asociativo u objeto/modelo),
    leyendo las claves date / type / description / author con data_get().

    Uso:
    <x-ui.timeline :events="$historial" />
--}}
@props(['events' => []])

<div {{ $attributes->merge(['class' => '']) }}>
    @forelse ($events as $event)
        <div class="timeline-item">
            <span class="timeline-dot"></span>
            <p class="text-xs font-semibold text-ink-muted">{{ data_get($event, 'date') }}</p>
            <p class="font-display font-semibold text-ink">{{ data_get($event, 'type') }}</p>

            @if (data_get($event, 'description'))
                <p class="mt-1 text-sm text-ink-muted">{{ data_get($event, 'description') }}</p>
            @endif

            @if (data_get($event, 'author'))
                <p class="mt-1 text-xs text-ink-muted">{{ __('Por') }} {{ data_get($event, 'author') }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-ink-muted">{{ __('Sin eventos registrados.') }}</p>
    @endforelse
</div>
