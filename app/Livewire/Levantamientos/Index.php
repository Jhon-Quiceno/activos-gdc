<?php

namespace App\Livewire\Levantamientos;

use App\Models\LevantamientoActivos;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Levantamientos de activos (ruta /levantamientos, RF-59, CU-18): inventario de
 * los activos de una dependencia y proceso, cerrado con el acta de
 * identificación y aceptación firmada (GT-M4 4.1 y 4.1.2).
 *
 * Pantalla de arranque dejada por el líder: solo lista los levantamientos.
 * Crear, verificar, generar el acta y subirla firmada es el bloque de Anuar
 * (docs/anuar.md).
 */
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.levantamientos.index', [
            'levantamientos' => LevantamientoActivos::query()
                ->with(['dependencia', 'sede'])
                ->latest('fecha_inicio')
                ->paginate(20),
        ]);
    }
}
