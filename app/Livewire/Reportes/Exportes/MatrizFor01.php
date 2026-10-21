<?php

namespace App\Livewire\Reportes\Exportes;

use App\Models\Equipo;
use App\Models\ValoracionActivo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Matriz de inventario de activos de información en el formato oficial FOR.01
 * (RF-53). Recibe una colección de equipos (con su valoración vigente) y arma
 * la hoja con el encabezado oficial, los seis grupos de columnas y las mismas
 * columnas del formato, en el mismo orden.
 *
 * Se deja como clase independiente a propósito: el levantamiento (bloque de
 * Anuar, RF-59) genera esta misma matriz para los equipos de la dependencia.
 */
final class MatrizFor01 implements FromArray, WithEvents, WithTitle, ShouldAutoSize
{
    public const ENCABEZADO = 'GOBERNACIÓN DE CÓRDOBA · Proceso Gestión de Seguridad de la Información · FOR.01 — Inventario de activos de información · Versión 00';

    /**
     * Grupos de columnas del FOR.01, en orden. La lista plana de encabezados
     * sale de aquí, así la matriz y las pruebas comparten una sola fuente.
     *
     * @var array<string, list<string>>
     */
    public const GRUPOS = [
        'Identificación' => ['ID ACTIVO', 'Código de activo', 'Sucursal', 'Proceso', 'Área o Dependencia'],
        'Atributo' => ['Nombre del Activo', 'Descripción del Activo', 'Tipo de Activo'],
        'Propiedad' => [
            'Contiene datos personales', 'Clasificación datos personales',
            'Infraestructura crítica', 'Continuidad del negocio',
            'Propietario del activo (cargo)', 'Custodio técnico del activo (cargo)',
        ],
        'Ubicación' => ['Tipo ubicación del activo'],
        'Retención' => ['Tiempo de retención de los datos'],
        'Valoración' => ['Confidencialidad', 'Integridad', 'Disponibilidad', 'Total', 'Importancia', 'Requiere gestión de riesgos'],
    ];

    private const SI_NO = ['si' => 'Sí', 'no' => 'No', 'no_aplica' => 'No aplica'];

    private const TIPOS_UBICACION = ['fisico' => 'Físico', 'electronico' => 'Electrónico', 'fisico_electronico' => 'Físico y electrónico'];

    private const TIPOS_ACTIVO = ['hardware' => 'Hardware', 'soporte' => 'Soporte'];

    /** @param Collection<int, Equipo> $equipos */
    public function __construct(private Collection $equipos) {}

    /** Encabezados planos, en el orden del formato. @return list<string> */
    public static function columnas(): array
    {
        return array_merge(...array_values(self::GRUPOS));
    }

    /** Valores de un equipo, en el mismo orden que columnas(). */
    public static function fila(Equipo $equipo): array
    {
        $v = $equipo->valoracionVigente;

        return [
            // Identificación
            $equipo->numero_activo,
            $equipo->codigo_activo,
            $equipo->asignacionActual?->piso?->sede?->nombre,
            $equipo->proceso?->nombre,
            $equipo->asignacionActual?->dependencia?->nombre,
            // Atributo
            self::nombreActivo($equipo),
            $equipo->descripcion,
            self::TIPOS_ACTIVO[$equipo->tipoEquipo?->tipo_activo] ?? $equipo->tipoEquipo?->tipo_activo,
            // Propiedad
            $v ? (self::SI_NO[$v->contiene_datos_personales] ?? $v->contiene_datos_personales) : null,
            $v ? (ValoracionActivo::CLASIFICACIONES_DATOS[$v->clasificacion_datos] ?? $v->clasificacion_datos) : null,
            $v ? (self::SI_NO[$v->infraestructura_critica] ?? $v->infraestructura_critica) : null,
            $v ? (self::SI_NO[$v->continuidad_negocio] ?? $v->continuidad_negocio) : null,
            $v?->propietario_cargo,
            $v?->custodio_cargo,
            // Ubicación
            $v ? (self::TIPOS_UBICACION[$v->tipo_ubicacion] ?? $v->tipo_ubicacion) : null,
            // Retención
            $v?->tiempo_retencion,
            // Valoración
            $v?->confidencialidad,
            $v?->integridad,
            $v?->disponibilidad,
            $v?->total,
            $v ? (ValoracionActivo::IMPORTANCIAS[$v->importancia] ?? $v->importancia) : null,
            $v ? ($v->requiere_gestion_riesgo ? 'Sí' : 'No') : null,
        ];
    }

    /** «Monitor HP P24»: tipo + marca + modelo, sin huecos. */
    public static function nombreActivo(Equipo $equipo): string
    {
        return trim(implode(' ', array_filter([
            $equipo->tipoEquipo?->nombre,
            $equipo->marca?->nombre,
            $equipo->modeloEquipo?->nombre,
        ])));
    }

    public function array(): array
    {
        $columnas = self::columnas();

        // Fila 1: encabezado oficial. Fila 2: grupos. Fila 3: columnas.
        $filaGrupos = [];
        foreach (self::GRUPOS as $grupo => $cols) {
            $filaGrupos[] = $grupo;
            $filaGrupos = array_merge($filaGrupos, array_fill(0, count($cols) - 1, null));
        }

        $filas = [
            array_merge([self::ENCABEZADO], array_fill(0, count($columnas) - 1, null)),
            $filaGrupos,
            $columnas,
        ];

        foreach ($this->equipos as $equipo) {
            $filas[] = self::fila($equipo);
        }

        return $filas;
    }

    public function title(): string
    {
        return 'FOR.01';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $evento) {
                $hoja = $evento->sheet->getDelegate();
                $ultima = Coordinate::stringFromColumnIndex(count(self::columnas()));

                // Fila 1: encabezado oficial a lo ancho de toda la matriz.
                $hoja->mergeCells("A1:{$ultima}1");

                // Fila 2: cada grupo sobre sus columnas.
                $col = 1;
                foreach (self::GRUPOS as $cols) {
                    $desde = Coordinate::stringFromColumnIndex($col);
                    $hasta = Coordinate::stringFromColumnIndex($col + count($cols) - 1);
                    if ($desde !== $hasta) {
                        $hoja->mergeCells("{$desde}2:{$hasta}2");
                    }
                    $col += count($cols);
                }

                $hoja->getStyle("A1:{$ultima}3")->getFont()->setBold(true);
                $hoja->getStyle("A2:{$ultima}3")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $hoja->freezePane('A4');
            },
        ];
    }
}
