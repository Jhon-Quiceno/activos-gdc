{{--
    Campo de formulario con label arriba y error debajo.
    Envuelve los componentes base de Breeze (x-input-label, x-text-input, x-input-error)
    con el estilo institucional, para no reinventar el manejo de $errors.

    Uso:
    <x-ui.input name="nombre" label="Nombre" wire:model="nombre" />
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'required' => false,
])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <x-input-label :for="$name" :value="$label" class="!text-ink-label" />
    @endif

    <x-text-input
        :id="$name"
        :name="$name"
        :type="$type"
        :required="$required"
        class="mt-1 block w-full h-11 rounded-lg border-line-input text-ink focus:border-primary focus:ring-primary"
        {{ $attributes->except(['class', 'name', 'label', 'type', 'required']) }}
    />

    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
