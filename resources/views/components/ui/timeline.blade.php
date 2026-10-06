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
        <div class="timeline-item group">
            <span class="timeline-dot transition-transform duration-200 group-hover:scale-125"></span>
            <p class="text-[13px] font-semibold text-ink-muted">{{ data_get($event, 'date') }}</p>
            <p class="font-display font-semibold text-ink transition-colors duration-150 group-hover:text-primary">{{ data_get($event, 'type') }}</p>

            @if (data_get($event, 'description'))
                <p class="mt-1 text-[14px] text-ink-muted">{{ data_get($event, 'description') }}</p>
            @endif

            @if (data_get($event, 'author'))
                <p class="mt-1 text-[13px] text-ink-muted">{{ __('Por') }} {{ data_get($event, 'author') }}</p>
            @endif
        </div>
    @empty
        <p class="text-[14px] text-ink-muted">{{ __('Sin eventos registrados.') }}</p>
    @endforelse
</div>
