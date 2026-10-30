<?php

namespace App\Livewire\Movimientos;

use App\Livewire\Movimientos\Soporte\ReglasMovimiento;
use App\Models\CambioComponente;
use App\Models\Componente;
use App\Models\Equipo;
use App\Models\TipoComponente;
use App\Services\HistorialService;
use App\Support\Identificadores;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Pantalla "Cambio de componente". No registra mantenimiento, solo altas,
 * cambios y bajas de componentes físicos de un equipo (RF-15).
 *
 * RF-17: lo retirado tiene destino (bodega, otro equipo o descarte). Cuando el
 * destino es «otro equipo», el componente pasa a la configuración actual de
 * ese equipo y el equipo destino recibe su propio evento de cambio de
 * componente (acción «agregar»), porque cada hoja de vida debe contar su parte.
 */
class ComponenteForm extends Component
{
    public Equipo $equipo;

    /** 'agregar' | 'cambiar' | 'quitar' */
    public string $accion = 'agregar';

    public ?int $componenteRetiradoId = null;

    /** 'bodega' | 'otro_equipo' | 'descarte' */
    public ?string $destinoRetirado = null;

    /** Serial o código de activo que se digita para buscar el equipo destino. */
    public string $busquedaDestino = '';

    /** Equipo destino encontrado por la búsqueda (RF-17). */
    public ?int $equipoDestinoId = null;

    public ?int $tipoComponenteId = null;

    public string $caracteristica = '';

    public string $marca = '';

    public string $serial = '';

    public string $motivo = '';

    public string $fecha = '';

    public const DESTINOS = [
        'bodega' => 'Bodega',
        'otro_equipo' => 'Otro equipo',
        'descarte' => 'Descarte',
    ];

    public function mount(Equipo $equipo): void
    {
        $this->equipo = $equipo->load([
            'tipoEquipo',
            'marca',
            'componentes' => fn ($q) => $q->whereNull('fecha_retiro')->with('tipoComponente')->orderBy('id'),
        ]);

        $this->fecha = now()->toDateString();
    }

    /**
     * Busca el equipo destino por serial o código de activo, con la misma
     * normalización que el registro de equipos (espacios, guiones, mayúsculas).
     */
    public function updatedBusquedaDestino(): void
    {
        $this->equipoDestinoId = $this->buscarEquipoDestino($this->busquedaDestino)?->id;
        $this->resetValidation('equipoDestinoId');
    }

    public function updatedDestinoRetirado(): void
    {
        if ($this->destinoRetirado !== 'otro_equipo') {
            $this->reset(['busquedaDestino', 'equipoDestinoId']);
        }
    }

    private function buscarEquipoDestino(string $termino): ?Equipo
    {
        $termino = trim($termino);

        if ($termino === '') {
            return null;
        }

        $codigo = Identificadores::codigoActivo($termino);

        return Equipo::query()
            ->where(fn ($q) => $q
                // UPPER(): los seriales viejos pueden estar guardados en minúscula.
                ->whereRaw('UPPER(serial) = ?', [Identificadores::serial($termino)])
                ->when($codigo, fn ($q) => $q->orWhere('codigo_activo', $codigo)))
            ->first();
    }

    protected function rules(): array
    {
        $rules = [
            'motivo' => ['required', 'string'],
            // RN-13: la fecha de un evento no puede ser futura.
            'fecha' => ['required', 'date', 'before_or_equal:today'],
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
            $rules['destinoRetirado'] = ['required', Rule::in(array_keys(self::DESTINOS))];

            if ($this->destinoRetirado === 'otro_equipo') {
                $rules['equipoDestinoId'] = [
                    'required',
                    Rule::exists('equipos', 'id'),
                    Rule::notIn([$this->equipo->id]),
                ];
            }
        }

        if ($this->accion !== 'quitar') {
            $rules['tipoComponenteId'] = ['required', 'exists:tipos_componente,id'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'equipoDestinoId.required' => __('Busca el equipo destino por su serial o código de activo.'),
            'equipoDestinoId.not_in' => __('El equipo destino no puede ser el mismo equipo del que sale el componente.'),
        ];
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

            $equipoDestino = null;
            if ($this->accion !== 'agregar' && $this->destinoRetirado === 'otro_equipo') {
                $equipoDestino = Equipo::query()->findOrFail($this->equipoDestinoId);
                // El destino también recibe un evento: debe admitirlo (RN-10).
                ReglasMovimiento::asegurarQueAdmiteEventos($equipoDestino, 'equipoDestinoId');
            }

            $fecha = Carbon::parse($this->fecha);
            $componenteRetirado = null;
            $componenteInstalado = null;

            if ($this->accion !== 'agregar') {
                $componenteRetirado = Componente::with('tipoComponente')->find($this->componenteRetiradoId);
                $componenteRetirado?->update(['fecha_retiro' => $fecha->toDateString()]);
            }

            if ($this->accion !== 'quitar') {
                $componenteInstalado = Componente::create([
                    'equipo_id' => $this->equipo->id,
                    'tipo_componente_id' => $this->tipoComponenteId,
                    'capacidad_caracteristica' => $this->caracteristica !== '' ? $this->caracteristica : null,
                    'marca' => $this->marca !== '' ? $this->marca : null,
                    'serial' => $this->serial !== '' ? $this->serial : null,
                    'fecha_instalacion' => $fecha->toDateString(),
                ]);
            }

            $evento = app(HistorialService::class)->registrar(
                equipo: $this->equipo,
                tipo: 'cambio_componente',
                usuario: auth()->user(),
                datos: ['fecha' => $this->fecha],
                descripcion: $this->descripcionOrigen($componenteRetirado, $equipoDestino),
            );

            CambioComponente::create([
                'evento_id' => $evento->id,
                'accion' => $this->accion,
                'componente_retirado_id' => $componenteRetirado?->id,
                'serial_retirado' => $componenteRetirado?->serial,
                'componente_instalado_id' => $componenteInstalado?->id,
                'serial_instalado' => $componenteInstalado?->serial,
                'motivo' => $this->motivo,
                'destino_retirado' => $this->accion !== 'agregar' ? $this->destinoRetirado : null,
                'equipo_destino_id' => $equipoDestino?->id,
            ]);

            if ($equipoDestino && $componenteRetirado) {
                $this->instalarEnDestino($componenteRetirado, $equipoDestino, $fecha);
            }
        });

        $this->redirect(route('equipos.show', $this->equipo), navigate: true);
    }

    /**
     * El componente retirado aparece en la configuración actual del equipo
     * destino. Se crea una fila nueva (no se mueve la vieja) para que la
     * configuración de cada equipo en una fecha dada siga siendo correcta
     * (RF-16): en el origen quedó con fecha de retiro, en el destino nace con
     * fecha de instalación.
     */
    private function instalarEnDestino(Componente $retirado, Equipo $destino, Carbon $fecha): void
    {
        $instalado = Componente::create([
            'equipo_id' => $destino->id,
            'tipo_componente_id' => $retirado->tipo_componente_id,
            'capacidad_caracteristica' => $retirado->capacidad_caracteristica,
            'marca' => $retirado->marca,
            'modelo' => $retirado->modelo,
            'serial' => $retirado->serial,
            'estado' => $retirado->estado,
            'fecha_instalacion' => $fecha->toDateString(),
        ]);

        $origen = self::identificacion($this->equipo);

        $evento = app(HistorialService::class)->registrar(
            equipo: $destino,
            tipo: 'cambio_componente',
            usuario: auth()->user(),
            datos: ['fecha' => $this->fecha],
            descripcion: __('Componente recibido del equipo :origen: :componente.', [
                'origen' => $origen,
                'componente' => self::textoComponente($retirado),
            ]),
        );

        CambioComponente::create([
            'evento_id' => $evento->id,
            'accion' => 'agregar',
            'componente_instalado_id' => $instalado->id,
            'serial_instalado' => $instalado->serial,
            'motivo' => __('Recibido del equipo :origen. :motivo', ['origen' => $origen, 'motivo' => $this->motivo]),
        ]);
    }

    private function descripcionOrigen(?Componente $retirado, ?Equipo $destino): string
    {
        $base = __('Cambio de componente (:accion).', ['accion' => $this->accion]);

        if (! $retirado) {
            return $base;
        }

        $destinoTexto = match ($this->destinoRetirado) {
            'otro_equipo' => __('pasa al equipo :equipo', ['equipo' => $destino ? self::identificacion($destino) : '—']),
            'bodega' => __('queda en bodega'),
            'descarte' => __('se descarta'),
            default => null,
        };

        return trim($base.' '.__('Retirado: :componente; :destino.', [
            'componente' => self::textoComponente($retirado),
            'destino' => $destinoTexto,
        ]));
    }

    /** «I1-024147» o, si el equipo no tiene código de activo, «serial 8CG1234XYZ». */
    public static function identificacion(Equipo $equipo): string
    {
        return $equipo->codigo_activo ?: __('serial :serial', ['serial' => $equipo->serial]);
    }

    private static function textoComponente(Componente $componente): string
    {
        return collect([
            $componente->tipoComponente?->nombre ?? __('Componente'),
            $componente->capacidad_caracteristica,
            $componente->serial ? __('serial :serial', ['serial' => $componente->serial]) : null,
        ])->filter()->implode(' · ');
    }

    public function render()
    {
        $equipoDestino = $this->equipoDestinoId
            ? Equipo::with(['tipoEquipo', 'marca', 'modeloEquipo', 'asignacionActual.persona', 'asignacionActual.piso.sede'])->find($this->equipoDestinoId)
            : null;

        return view('livewire.movimientos.componente-form', [
            'tiposComponente' => TipoComponente::orderBy('nombre')->get(),
            'destinos' => self::DESTINOS,
            'equipoDestino' => $equipoDestino,
            'destinoDadoDeBaja' => $equipoDestino?->estado_ciclo_vida === 'dado_de_baja',
        ]);
    }
}
