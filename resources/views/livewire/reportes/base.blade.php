<div>
    <h1>{{ $titulo }}</h1>

    <div>
        <select wire:model.live="filtros.sede_id">
            <option value="">Todas las sedes</option>
            @foreach ($catalogos['sedes'] as $s)
                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.piso_id">
            <option value="">Todos los pisos</option>
            @foreach ($catalogos['pisos'] as $p)
                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.dependencia_id">
            <option value="">Todas las dependencias</option>
            @foreach ($catalogos['dependencias'] as $d)
                <option value="{{ $d->id }}">{{ $d->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.tipo_equipo_id">
            <option value="">Todos los tipos</option>
            @foreach ($catalogos['tipos'] as $t)
                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.marca_id">
            <option value="">Todas las marcas</option>
            @foreach ($catalogos['marcas'] as $m)
                <option value="{{ $m->id }}">{{ $m->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.estado_ciclo_vida">
            <option value="">Todos los estados</option>
            @foreach ($catalogos['estados'] as $e)
                <option value="{{ $e }}">{{ $e }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.propiedad">
            <option value="">Toda propiedad</option>
            @foreach ($catalogos['propiedades'] as $p)
                <option value="{{ $p }}">{{ $p }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.persona_id">
            <option value="">Todos los responsables</option>
            @foreach ($catalogos['personas'] as $p)
                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filtros.tipo_vinculacion">
            <option value="">Toda vinculación</option>
            @foreach ($catalogos['vinculaciones'] as $v)
                <option value="{{ $v }}">{{ $v }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="filtros.fecha_desde">
        <input type="date" wire:model.live="filtros.fecha_hasta">

        <button wire:click="limpiarFiltros">Limpiar</button>
        <button wire:click="exportarExcel">Excel</button>
        <button wire:click="exportarPdf">PDF</button>
    </div>

    <table>
        <thead>
            <tr>@foreach ($encabezados as $e)<th>{{ $e }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr>@foreach ($columnas as $fn)<td>{{ $fn($registro) }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($encabezados) }}">No hay equipos con esos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $registros->links() }}
</div>