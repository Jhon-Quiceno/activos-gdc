<?php

namespace App\Livewire\Reportes\Definiciones;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Builder;

class CalidadInventario extends ReporteDefinicion
{
    public const INDICADORES = [
        'sin_serial' => 'Sin serial',
        'sin_codigo' => 'Sin código de activo',
        'sin_responsable' => 'Sin responsable',
        'codigo_repetido' => 'Código de activo repetido',
        'pendiente_verificar' => 'Pendiente de verificar',
        'sin_etiqueta_qr' => 'Sin etiqueta QR impresa',
    ];

    private ?array $codigosRepetidos = null;

    public function titulo(): string
    {
        return 'Calidad del inventario';
    }

    public function filtroEspecifico(): ?array
    {
        return [
            'etiqueta' => 'Problema',
            'todas' => 'Todos los problemas',
            'opciones' => self::INDICADORES,
        ];
    }

    protected function consultaBase(): Builder
    {
        return parent::consultaBase()->withCount('etiquetasQr');
    }

    protected function aplicarFiltros(Builder $q, array $f): Builder
    {
        $q = parent::aplicarFiltros($q, $f);
        $indicador = $f['especifico'] ?? '';

        // Con un problema elegido, solo ese; sin elegir, los equipos con cualquiera de ellos.
        return $q->where(function (Builder $w) use ($indicador) {
            if (array_key_exists($indicador, self::INDICADORES)) {
                $this->condicion($w, $indicador);

                return;
            }

            foreach (array_keys(self::INDICADORES) as $clave) {
                $w->orWhere(fn (Builder $x) => $this->condicion($x, $clave));
            }
        });
    }

    private function condicion(Builder $q, string $clave): void
    {
        match ($clave) {
            'sin_serial' => $q->where(fn ($x) => $x->whereNull('equipos.serial')->orWhere('equipos.serial', '')),
            'sin_codigo' => $q->where(fn ($x) => $x->whereNull('equipos.codigo_activo')->orWhere('equipos.codigo_activo', '')),
            'sin_responsable' => $q->where('equipos.estado_ciclo_vida', '!=', 'dado_de_baja')
                ->whereDoesntHave('asignacionActual', fn ($a) => $a->whereNotNull('persona_id')),
            'codigo_repetido' => $q->whereIn('equipos.codigo_activo', $this->codigosRepetidos()),
            'pendiente_verificar' => $q->where('equipos.verificacion', 'pendiente_de_verificar'),
            'sin_etiqueta_qr' => $q->whereDoesntHave('etiquetasQr'),
        };
    }

    private function codigosRepetidos(): array
    {
        return $this->codigosRepetidos ??= Equipo::query()
            ->whereNotNull('codigo_activo')
            ->where('codigo_activo', '!=', '')
            ->groupBy('codigo_activo')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('codigo_activo')
            ->all();
    }

    private function problemas(Equipo $e): string
    {
        $lista = [];

        if (blank($e->serial)) {
            $lista[] = self::INDICADORES['sin_serial'];
        }
        if (blank($e->codigo_activo)) {
            $lista[] = self::INDICADORES['sin_codigo'];
        } elseif (in_array($e->codigo_activo, $this->codigosRepetidos(), true)) {
            $lista[] = self::INDICADORES['codigo_repetido'];
        }
        if ($e->estado_ciclo_vida !== 'dado_de_baja' && blank($e->asignacionActual?->persona_id)) {
            $lista[] = self::INDICADORES['sin_responsable'];
        }
        if ($e->verificacion === 'pendiente_de_verificar') {
            $lista[] = self::INDICADORES['pendiente_verificar'];
        }
        if (($e->etiquetas_qr_count ?? 0) === 0) {
            $lista[] = self::INDICADORES['sin_etiqueta_qr'];
        }

        return implode(', ', $lista);
    }

    public function columnas(): array
    {
        return [
            'Serial'           => fn ($e) => $e->serial,
            'Código de activo' => fn ($e) => $e->codigo_activo,
            'Tipo'             => fn ($e) => $e->tipoEquipo?->nombre,
            'Marca'            => fn ($e) => $e->marca?->nombre,
            'Estado'           => fn ($e) => $this->etiquetaEstado($e->estado_ciclo_vida),
            'Verificación'     => fn ($e) => $e->verificacion,
            'Sede'             => fn ($e) => $e->asignacionActual?->sede?->nombre,
            'Dependencia'      => fn ($e) => $e->asignacionActual?->dependencia?->nombre,
            'Responsable'      => fn ($e) => $e->asignacionActual?->persona?->nombre,
            'Problemas'        => fn ($e) => $this->problemas($e),
        ];
    }
}