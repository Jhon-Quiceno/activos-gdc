{{--
    Lista de definición en 2 columnas, para fichas tipo "hoja de vida"
    (campo a la izquierda, valor a la derecha).

    Uso con array asociativo:
    <x-ui.definition-list :items="['Serial' => $equipo->serial, 'Marca' => $equipo->marca]" />

    Uso con slot (control total del marcado):
    <x-ui.definition-list>
        <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-2 sm:gap-6">
            <dt class="text-sm font-medium text-ink-label">Serial</dt>
            <dd class="text-sm text-ink">{{ $equipo->serial }}</dd>
        </div>
    </x-ui.definition-list>
--}}
@props(['items' => []])

<dl {{ $attributes->merge(['class' => 'divide-y divide-line']) }}>
    @forelse ($items as $label => $value)
        <div class="grid grid-cols-1 gap-1 py-3 sm:grid-cols-2 sm:gap-6">
            <dt class="text-[14px] font-medium text-ink-label">{{ $label }}</dt>
            <dd class="text-[14px] text-ink">{{ $value }}</dd>
        </div>
    @empty
        {{ $slot }}
    @endforelse
</dl>
