<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\CambioComponente;
use App\Models\Componente;
use App\Models\Equipo;
use App\Models\TipoComponente;
use App\Services\HistorialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Cambio de componente". No registra mantenimiento, solo altas,
 * cambios y bajas de componentes físicos de un equipo (RF de Movimientos).
 */
class ComponenteForm extends Component
{
    public Equipo $equipo;

    /** 'agregar' | 'cambiar' | 'quitar' */
    public string $accion = 'agregar';

    public ?int $componenteRetiradoId = null;

    /** 'bodega' | 'otro_equipo' | 'descarte' */
    public ?string $destinoRetirado = null;

    public ?int $tipoComponenteId = null;

    public string $caracteristica = '';

    public string $marca = '';

    public string $serial = '';

    public string $motivo = '';

    public string $fecha = '';

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load([
            'tipoEquipo',
            'marca',
            'componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id'),
        ]);

        $this->fecha = now()->toDateString();
    }

    protected function rules(): array
    {
        $rules = [
            'motivo' => ['required', 'string'],
            'fecha' => ['nullable', 'date'],
        ];

        if ($this->accion !== 'agregar') {
            // El componente a retirar debe pertenecer a ESTE equipo y seguir instalado;
            // sin este scope, un id de componente de otro equipo pasaba la validación
            // y quedaba marcado como retirado igual (bug reportado por Juan José).
            $rules['componenteRetiradoId'] = [
                'required',
                Rule::exists('componentes', 'id')
                    ->where('equipo_id', $this->equipo->id)
                    ->whereNull('fecha_retiro'),
            ];
            $rules['destinoRetirado'] = ['required', 'in:bodega,otro_equipo,descarte'];
        }

        if ($this->accion !== 'quitar') {
            $rules['tipoComponenteId'] = ['required', 'exists:tipos_componente,id'];
        }

        return $rules;
    }

    public function guardar(): void
    {
        $this->validate();

        DB::transaction(function () {
            // RN-10: un equipo dado de baja (o con la baja en trámite) no
            // admite cambios de componente. Dentro de la transacción (no
            // antes) para que el lockForUpdate() de la consulta sirva contra
            // dos envíos concurrentes sobre el mismo equipo.
            ReglasMovimiento::asegurarQueAdmiteEventos($this->equipo);

            $componenteRetirado = null;
            $componenteInstalado = null;

            if ($this->accion !== 'agregar') {
                $componenteRetirado = Componente::find($this->componenteRetiradoId);
                $componenteRetirado?->update(['fecha_retiro' => now()]);
            }

            if ($this->accion !== 'quitar') {
                $componenteInstalado = Componente::create([
                    'equipo_id' => $this->equipo->id,
                    'tipo_componente_id' => $this->tipoComponenteId,
                    'capacidad_caracteristica' => $this->caracteristica !== '' ? $this->caracteristica : null,
                    'marca' => $this->marca !== '' ? $this->marca : null,
                    'serial' => $this->serial !== '' ? $this->serial : null,
                    'fecha_instalacion' => now(),
                ]);
            }

            $evento = app(HistorialService::class)->registrar(
                equipo: $this->equipo,
                tipo: 'cambio_componente',
                usuario: auth()->user(),
                datos: $this->fecha !== '' ? ['fecha' => $this->fecha] : [],
                descripcion: __('Cambio de componente (:accion).', ['accion' => $this->accion]),
            );

            CambioComponente::create([
                'evento_id' => $evento->id,
                'accion' => $this->accion,
                'componente_retirado_id' => $componenteRetirado?->id,
                'serial_retirado' => $componenteRetirado?->serial,
                'componente_instalado_id' => $componenteInstalado?->id,
                'serial_instalado' => $componenteInstalado?->serial,
                'motivo' => $this->motivo,
                'destino_retirado' => $this->destinoRetirado,
            ]);
        });

        $this->redirect(route('equipos.show', $this->equipo), navigate: true);
    }

    public function render()
    {
        return view('livewire.movimientos.componente-form', [
            'tiposComponente' => TipoComponente::orderBy('nombre')->get(),
        ]);
    }
}
