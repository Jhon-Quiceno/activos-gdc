{{--
    Tabla de equivalencias: valor tal como viene en el archivo (columna izquierda)
    vs. el valor oficial del catálogo de Dependencia (select editable).
    Usada tanto en el paso "Equivalencias" como en el resumen del paso "Vista previa".
--}}
<x-ui.table>
    <thead>
        <tr>
            <th>{{ __('En el archivo') }}</th>
            <th></th>
            <th>{{ __('Valor oficial') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($equivalencias as $indice => $equivalencia)
            <tr wire:key="equivalencia-{{ $indice }}">
                <td class="font-mono text-ink-muted">{{ $equivalencia['origen'] }}</td>
                <td class="text-ink-muted">&rarr;</td>
                <td>
                    <select
                        wire:model="equivalencias.{{ $indice }}.sugerido"
                        class="h-10 w-full max-w-xs rounded-lg border-line-input text-[14px] text-ink focus:border-primary focus:ring-primary"
                    >
                        @foreach ($dependencias as $dependencia)
                            <option value="{{ $dependencia }}">{{ $dependencia }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        @endforeach
    </tbody>
</x-ui.table>
