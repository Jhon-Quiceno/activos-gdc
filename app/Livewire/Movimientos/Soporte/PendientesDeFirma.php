<?php

namespace App\Livewire\Movimientos\Soporte;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pendientes de firma (RF-31, RF-30, RN-05): la regla de qué evento sigue
 * esperando documentos, en un solo lugar para la pantalla y su exportación.
 *
 * Un evento está pendiente cuando:
 * - su estado_firma es «pendiente_de_firma»,
 * - no fue anulado (una anulación deja el evento sin nada que firmar), y
 * - le falta al menos uno de sus documentos firmados. El estado del evento
 *   pasa a «completo» al subir el último documento (GestorFirmas), pero la
 *   consulta lo comprueba también sobre los documentos para que un dato
 *   desalineado nunca muestre como pendiente un evento que ya tiene todo.
 *
 * Los documentos que exige cada tipo de evento son los de
 * GestorFirmas::requeridos(); el traslado acepta además el documento único.
 */
class PendientesDeFirma
{
    /** Rangos de antigüedad del filtro: clave => [días mínimos, días máximos]. */
    public const ANTIGUEDADES = [
        'hasta_7' => ['Hasta 7 días', 0, 7],
        'de_8_a_30' => ['De 8 a 30 días', 8, 30],
        'mas_30' => ['Más de 30 días', 31, null],
    ];

    /** Etiquetas de los tipos del sistema; un tipo nuevo (RF-13) se muestra legible igual. */
    public const ETIQUETAS_TIPO = [
        'alta' => 'Alta',
        'traslado_responsable' => 'Traslado',
        'cambio_componente' => 'Cambio de componente',
        'diagnostico' => 'Diagnóstico',
        'valoracion' => 'Valoración',
        'baja' => 'Baja',
        'actualizacion_datos' => 'Actualización de datos',
        'anulacion_aclaracion' => 'Anulación / aclaración',
    ];

    public const ETIQUETAS_FORMATO = [
        'formato_baja' => 'Formato de retiro / baja',
        'formato_entrega' => 'Formato de entrega',
    ];

    public function __construct(
        private GestorFirmas $firmas,
        private GestorAsignaciones $asignaciones,
    ) {}

    /**
     * @param  array{tipo?: string, antiguedad?: string, busqueda?: string}  $filtros
     */
    public function consulta(array $filtros = []): Builder
    {
        $tipo = $filtros['tipo'] ?? '';
        $antiguedad = $filtros['antiguedad'] ?? '';
        $busqueda = trim($filtros['busqueda'] ?? '');

        return self::base()
            ->when($tipo !== '', fn (Builder $q) => $q->where('tipo', $tipo))
            ->when(isset(self::ANTIGUEDADES[$antiguedad]), function (Builder $q) use ($antiguedad) {
                [, $minimo, $maximo] = self::ANTIGUEDADES[$antiguedad];
                $hoy = Carbon::today();

                // «Hace N días» se cuenta por fecha del evento, sin la hora.
                $q->whereDate('fecha', '<=', $hoy->copy()->subDays($minimo));
                if ($maximo !== null) {
                    $q->whereDate('fecha', '>=', $hoy->copy()->subDays($maximo));
                }
            })
            ->when($busqueda !== '', function (Builder $q) use ($busqueda) {
                $compacto = str_replace([' ', '-'], '', $busqueda);

                // El OR va agrupado: suelto rompería la correlación del whereHas.
                $q->whereHas('equipo', fn (Builder $e) => $e->where(fn (Builder $w) => $w
                    ->whereRaw("REPLACE(REPLACE(serial, ' ', ''), '-', '') LIKE ?", ["%{$compacto}%"])
                    ->orWhereRaw("REPLACE(REPLACE(codigo_activo, ' ', ''), '-', '') LIKE ?", ["%{$compacto}%"])));
            });
    }

    /**
     * Todos los pendientes, sin filtros. Es la regla que debería usar también
     * cualquier contador de pendientes (la campanita del layout).
     */
    public static function base(): Builder
    {
        return Evento::query()
            ->where('estado_firma', 'pendiente_de_firma')
            ->whereDoesntHave('anulaciones')
            ->whereNot(fn (Builder $q) => self::condicionCompleto($q));
    }

    /**
     * El evento ya tiene firmados todos los documentos que exige su tipo
     * (mismas reglas que GestorFirmas::requeridos() y ::estaCompleto()).
     */
    private static function condicionCompleto(Builder $q): void
    {
        $firmado = fn (string $tipo) => fn (Builder $d) => $d->where('firmado', true)->where('tipo', $tipo);

        $q->where(fn (Builder $c) => $c
            ->where('tipo', 'traslado_responsable')
            ->where(fn (Builder $t) => $t
                ->where(fn (Builder $ambos) => $ambos
                    ->whereHas('documentos', $firmado('formato_baja'))
                    ->whereHas('documentos', $firmado('formato_entrega')))
                ->orWhereHas('documentos', fn (Builder $d) => $d
                    ->where('firmado', true)
                    ->where('tipo', 'otro')
                    ->where('consecutivo', 'like', 'DU-%'))))
            ->orWhere(fn (Builder $c) => $c
                ->whereIn('tipo', ['baja', 'diagnostico'])
                ->whereHas('documentos', $firmado('formato_baja')))
            ->orWhere(fn (Builder $c) => $c
                ->where('tipo', 'alta')
                ->whereHas('documentos', $firmado('formato_entrega')));
    }

    /**
     * Tipos de evento que hoy tienen pendientes, para el filtro, ordenados por
     * su etiqueta. Se ordena en PHP y no con ORDER BY: en MySQL `eventos.tipo`
     * es un ENUM y ordenaría por la posición del valor, no alfabéticamente.
     *
     * @return array<string, string>
     */
    public function tiposConPendientes(): array
    {
        return self::base()
            ->distinct()
            ->pluck('tipo')
            ->mapWithKeys(fn (string $tipo) => [$tipo => self::etiquetaTipo($tipo)])
            ->sort()
            ->all();
    }

    /**
     * Resumen para los indicadores de la pantalla.
     *
     * @return array{total: int, mas_30: int, traslados: int, bajas: int}
     */
    public function resumen(): array
    {
        return [
            'total' => self::base()->count(),
            'mas_30' => self::base()->whereDate('fecha', '<', Carbon::today()->subDays(30))->count(),
            'traslados' => self::base()->where('tipo', 'traslado_responsable')->count(),
            'bajas' => self::base()->where('tipo', 'baja')->count(),
        ];
    }

    /**
     * Qué documentos le faltan al evento y quién firma cada uno: el formato
     * de retiro/baja lo firma quien entrega y el de entrega quien recibe.
     *
     * @return array<int, array{tipo: string, etiqueta: string, firma: ?string}>
     */
    public function faltantes(Evento $evento): array
    {
        $firmados = $evento->relationLoaded('documentos')
            ? $evento->documentos->where('firmado', true)->pluck('tipo')->all()
            : $evento->documentos()->where('firmado', true)->pluck('tipo')->all();

        return collect($this->firmas->requeridos($evento))
            ->reject(fn (string $tipo) => in_array($tipo, $firmados, true))
            ->map(fn (string $tipo) => [
                'tipo' => $tipo,
                'etiqueta' => __(self::ETIQUETAS_FORMATO[$tipo] ?? $tipo),
                'firma' => ($tipo === 'formato_entrega'
                    ? $this->asignaciones->vigenteDespuesDe($evento)
                    : $this->asignaciones->vigenteAntesDe($evento))?->persona?->nombreCompleto(),
            ])
            ->values()
            ->all();
    }

    public static function dias(Evento $evento): int
    {
        return (int) $evento->fecha->copy()->startOfDay()->diffInDays(Carbon::today());
    }

    public static function etiquetaTipo(string $tipo): string
    {
        return __(self::ETIQUETAS_TIPO[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo)));
    }

    /**
     * Filas para exportar a Excel el listado tal como está filtrado.
     *
     * @return Collection<int, array<int, string|int>>
     */
    public function filas(array $filtros = []): Collection
    {
        return $this->consulta($filtros)
            ->with(['equipo.tipoEquipo', 'usuario', 'documentos'])
            ->orderBy('fecha')
            ->get()
            ->map(function (Evento $evento) {
                $faltantes = $this->faltantes($evento);

                return [
                    $evento->id,
                    self::etiquetaTipo($evento->tipo),
                    $evento->fecha->format('d/m/Y'),
                    self::dias($evento),
                    $evento->equipo?->codigo_activo ?: '—',
                    $evento->equipo?->serial ?? '—',
                    $evento->equipo?->tipoEquipo?->nombre ?? '—',
                    collect($faltantes)->pluck('etiqueta')->implode(' + ') ?: '—',
                    collect($faltantes)->pluck('firma')->filter()->unique()->implode(', ') ?: '—',
                    $evento->usuario?->name ?? '—',
                ];
            });
    }

    /** @return array<int, string> */
    public static function encabezados(): array
    {
        return ['N.° de evento', 'Movimiento', 'Fecha', 'Días pendiente', 'Código de activo', 'Serial', 'Tipo de equipo', 'Falta', 'Quién firma', 'Registrado por'];
    }
}
