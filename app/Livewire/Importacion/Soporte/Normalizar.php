<?php

namespace App\Livewire\Importacion\Soporte;

use App\Support\Identificadores;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;
use Throwable;

/**
 * Normalización de los valores que llegan en los Excel de carga. Cada método
 * devuelve null si el valor está vacío o es «basura» de formulario (N/A, No se
 * ve…), y lanza \InvalidArgumentException si trae algo que no se puede usar.
 */
final class Normalizar
{
    private const BASURA = ['N/A', 'NA', 'N\A', 'NO SE VE', 'NO LEGIBLE', 'SIN DATO', 'NO APLICA', 'SIN CODIGO', 'SIN CÓDIGO', 'NO TIENE', 'NO VISIBLE', 'NN', '-'];

    public static function texto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpio = trim(preg_replace('/\s+/u', ' ', $valor));

        return $limpio === '' || in_array(mb_strtoupper($limpio), self::BASURA, true) ? null : $limpio;
    }

    /** Para comparar con catálogos: minúsculas, sin tildes ni signos. */
    public static function clave(?string $valor): string
    {
        return LectorHoja::normalizar($valor);
    }

    /** Mayúsculas y sin espacios: «  cn-0001 » → «CN-0001». */
    public static function serial(?string $valor): ?string
    {
        $texto = self::texto($valor);

        return $texto === null ? null : Identificadores::serial($texto);
    }

    /**
     * Código de activo al formato I1-######: «I1 24147», «i124147» y «L1-24147»
     * quedan «I1-24147». Lo que no tiene forma de código se rechaza.
     */
    public static function codigoActivo(?string $valor): ?string
    {
        $texto = self::texto($valor);
        if ($texto === null) {
            return null;
        }

        return Identificadores::codigoActivo($texto)
            ?? throw new \InvalidArgumentException("El código de activo «{$texto}» no tiene el formato I1-######.");
    }

    /** ¿Tiene forma de código de activo (aunque esté mal escrito)? */
    public static function pareceCodigo(?string $valor): bool
    {
        try {
            return self::codigoActivo($valor) !== null;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /** Documento sin separadores: «1.067.888.999» → «1067888999». */
    public static function documento(?string $valor): ?string
    {
        $texto = self::texto($valor);
        if ($texto === null) {
            return null;
        }

        // Excel suele guardar la cédula como número: «1067888999.0».
        $texto = preg_replace('/\.0+$/', '', $texto);

        return Identificadores::documento($texto);
    }

    /** Pesos colombianos: «$ 2.350.000,50» → «2350000.50». */
    public static function valor(?string $valor): ?string
    {
        $texto = self::texto($valor);
        if ($texto === null) {
            return null;
        }

        $sinSimbolos = preg_replace('/[\s$]+/u', '', $texto);

        // Un número que Excel ya leyó como tal («2350000» o «2350000.5»).
        if (preg_match('/^\d+(\.\d{1,2})?$/', $sinSimbolos)) {
            return $sinSimbolos;
        }

        // Formato colombiano: puntos de miles y coma decimal.
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $sinSimbolos) || preg_match('/^\d+(,\d{1,2})?$/', $sinSimbolos)) {
            return str_replace(',', '.', str_replace('.', '', $sinSimbolos));
        }

        throw new \InvalidArgumentException("El valor «{$texto}» no es un valor en pesos.");
    }

    /**
     * Fecha a AAAA-MM-DD. Acepta AAAA-MM-DD, DD/MM/AAAA y el número de serie de Excel.
     */
    public static function fecha(?string $valor): ?string
    {
        $texto = self::texto($valor);
        if ($texto === null) {
            return null;
        }

        try {
            if (preg_match('/^\d{4,5}(\.\d+)?$/', $texto)) {
                return Carbon::instance(FechaExcel::excelToDateTimeObject((float) $texto))->toDateString();
            }

            if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $texto, $m)) {
                return Carbon::createSafe((int) $m[3], (int) $m[2], (int) $m[1])->toDateString();
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $texto)) {
                return Carbon::createFromFormat('Y-m-d', substr($texto, 0, 10))->toDateString();
            }
        } catch (Throwable) {
            // Cae al error de abajo.
        }

        throw new \InvalidArgumentException("La fecha «{$texto}» no es válida (usa AAAA-MM-DD o DD/MM/AAAA).");
    }

    /** «3c-52-82-1a-2b-4c» → «3C:52:82:1A:2B:4C». */
    public static function mac(?string $valor): ?string
    {
        $texto = self::texto($valor);
        if ($texto === null) {
            return null;
        }

        $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $texto));
        if (strlen($hex) !== 12) {
            throw new \InvalidArgumentException("La MAC «{$texto}» no tiene 6 pares hexadecimales.");
        }

        return implode(':', str_split($hex, 2));
    }

    public static function entero(?string $valor): ?int
    {
        $texto = self::texto($valor);
        if ($texto === null) {
            return null;
        }

        $texto = preg_replace('/\.0+$/', '', $texto);
        if (! ctype_digit($texto)) {
            throw new \InvalidArgumentException("«{$texto}» no es un número entero.");
        }

        return (int) $texto;
    }
}
