{{--
    Segmented control (ej. toggle "Gobernación / Tercero").
    Si se pasa $model, cada botón actualiza esa propiedad Livewire con wire:click.

    Uso:
    <x-ui.segmented-control :options="['gobernacion' => 'Gobernación', 'tercero' => 'Tercero']" :selected="$tipoPropietario" model="tipoPropietario" />
--}}
@props(['options' => [], 'selected' => null, 'model' => null])

<div {{ $attributes->merge(['class' => 'inline-flex rounded-lg border border-line-input bg-white p-1']) }} role="tablist">
    @foreach ($options as $value => $label)
        <button
            type="button"
            role="tab"
            aria-selected="{{ (string) $selected === (string) $value ? 'true' : 'false' }}"
            @if ($model) wire:click="$set('{{ $model }}', '{{ $value }}')" @endif
            class="rounded-md px-4 py-2 text-sm font-semibold transition
                {{ (string) $selected === (string) $value ? 'bg-primary text-white' : 'text-ink-muted hover:text-ink' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
