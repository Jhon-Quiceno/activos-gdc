{{--
    Tabla con filtros arriba y paginación abajo.

    Uso:
    <x-ui.table>
        <x-slot name="filters"> ...inputs de filtro... </x-slot>

        <thead>
            <tr><th>Columna</th></tr>
        </thead>
        <tbody>
            <tr><td>Valor</td></tr>
        </tbody>

        <x-slot name="pagination">{{ $items->links() }}</x-slot>
    </x-ui.table>
--}}
@props(['filters' => null, 'pagination' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-line bg-white shadow-sm']) }}>
    @isset($filters)
        <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4">
            {{ $filters }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-line text-[14px]
            [&_th]:bg-[#F8FAFC] [&_th]:px-4 [&_th]:py-2.5 [&_th]:text-left [&_th]:text-[13px] [&_th]:font-semibold [&_th]:text-ink-muted
            [&_td]:px-4 [&_td]:py-3 [&_td]:text-ink [&_tbody_tr]:border-t [&_tbody_tr]:border-line [&_tbody_tr]:transition-colors [&_tbody_tr]:duration-150 [&_tbody_tr:hover]:bg-app-bg">
            {{ $slot }}
        </table>
    </div>

    @isset($pagination)
        <div class="border-t border-line px-5 py-3">
            {{ $pagination }}
        </div>
    @endisset
</div>
