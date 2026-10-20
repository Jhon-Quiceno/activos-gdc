<?php

namespace App\Livewire\Importacion\Soporte;

use App\Livewire\Importacion\InventarioExcel;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Lee un Excel o CSV y lo convierte en filas asociativas por encabezado.
 *
 * Los archivos oficiales no empiezan siempre en la fila 1: la planta trae
 * títulos arriba y el FOR.01 tiene el encabezado en la fila 9. Por eso la
 * fila de encabezados se busca: es la primera que contiene todas las
 * columnas «ancla» indicadas.
 */
final class LectorHoja
{
    /**
     * @param  array<int, string>  $anclas  Encabezados (ya normalizados) que debe tener la fila de títulos.
     * @param  array<string, array<int, string>>  $columnas  Clave interna => encabezados aceptados (normalizados).
     *                                                       Las columnas que no estén aquí se descartan al leer.
     * @return array{encontrado: bool, filas: array<int, array{numero: int, datos: array<string, ?string>}>, faltantes: array<int, string>}
     */
    public static function leer(string $ruta, array $anclas, array $columnas): array
    {
        // Se usa la primera hoja del libro que tenga los encabezados ancla.
        $filas = [];
        $indice = null;
        foreach (Excel::toArray(new InventarioExcel, $ruta) as $candidata) {
            if (($indice = self::indiceEncabezado($candidata, $anclas)) !== null) {
                $filas = $candidata;
                break;
            }
        }

        if ($indice === null) {
            return ['encontrado' => false, 'filas' => [], 'faltantes' => $anclas];
        }

        $encabezados = array_map(fn ($valor) => self::normalizar($valor), $filas[$indice]);

        // Columna del archivo => clave interna, solo para las columnas permitidas.
        $mapa = [];
        foreach ($encabezados as $posicion => $titulo) {
            foreach ($columnas as $clave => $aceptados) {
                if (! isset($mapa[$posicion]) && ! in_array($clave, $mapa, true) && in_array($titulo, $aceptados, true)) {
                    $mapa[$posicion] = $clave;
                }
            }
        }

        $resultado = [];
        foreach (array_slice($filas, $indice + 1, null, true) as $posicionFila => $fila) {
            $datos = [];
            foreach ($mapa as $posicion => $clave) {
                $datos[$clave] = self::limpiar($fila[$posicion] ?? null);
            }

            if (array_filter($datos, fn ($valor) => $valor !== null) === []) {
                continue;
            }

            // Número de fila como lo ve la persona en Excel (empieza en 1).
            $resultado[] = ['numero' => $posicionFila + 1, 'datos' => $datos];
        }

        return [
            'encontrado' => true,
            'filas' => $resultado,
            'faltantes' => array_values(array_diff(array_keys($columnas), $mapa)),
        ];
    }

    /** «Número de Cédula » → «numero de cedula». */
    public static function normalizar(mixed $valor): string
    {
        return Str::of((string) $valor)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
    }

    private static function indiceEncabezado(array $filas, array $anclas): ?int
    {
        foreach (array_slice($filas, 0, 30, true) as $indice => $fila) {
            $normalizada = array_map(fn ($valor) => self::normalizar($valor), $fila);

            if (array_diff($anclas, $normalizada) === []) {
                return $indice;
            }
        }

        return null;
    }

    private static function limpiar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim(preg_replace('/\s+/u', ' ', (string) $valor));

        return $texto === '' ? null : $texto;
    }
}
