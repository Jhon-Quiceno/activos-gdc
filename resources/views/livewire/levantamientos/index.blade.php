<div>
    <x-ui.card>
        @if ($levantamientos->isEmpty())
            <p class="text-[14px] text-ink-muted">
                {{ __('Todavía no hay levantamientos. Desde aquí se creará uno por dependencia y proceso, se verificarán y valorarán sus equipos, y se subirá el acta de aceptación firmada.') }}
            </p>
        @else
            <table class="w-full text-[14px]">
                <thead>
                    <tr class="text-left text-[13px] text-ink-label">
                        <th class="py-2">{{ __('Código') }}</th>
                        <th class="py-2">{{ __('Dependencia') }}</th>
                        <th class="py-2">{{ __('Sede') }}</th>
                        <th class="py-2">{{ __('Inicio') }}</th>
                        <th class="py-2">{{ __('Estado') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($levantamientos as $levantamiento)
                        <tr class="border-t border-line">
                            <td class="py-2">{{ $levantamiento->codigo }}</td>
                            <td class="py-2">{{ $levantamiento->dependencia->nombre }}</td>
                            <td class="py-2">{{ $levantamiento->sede->nombre }}</td>
                            <td class="py-2">{{ $levantamiento->fecha_inicio->format('d/m/Y') }}</td>
                            <td class="py-2">{{ \App\Models\LevantamientoActivos::ESTADOS[$levantamiento->estado] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">{{ $levantamientos->links() }}</div>
        @endif
    </x-ui.card>
</div>
