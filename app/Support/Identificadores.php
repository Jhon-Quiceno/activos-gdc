<?php

namespace App\Support;

/**
 * Normalización canónica de los identificadores que escribe o carga una persona:
 * serial del fabricante, código de activo de Almacén (RN-02, RN-03) y número de
 * documento del responsable.
 *
 * Fuente única de verdad: tanto el registro manual de equipos
 * (App\Livewire\Equipos\Concerns\NormalizaIdentificadores) como la carga de
 * inventario (App\Livewire\Importacion\Soporte\Normalizar) delegan aquí, para
 * que un cambio de regla valga en los dos lados a la vez. Cada consumidor
 * mantiene su propia política alrededor (qué hacer con un valor vacío o
 * inválido): estos métodos solo transforman una cadena ya no vacía.
 */
final class Identificadores
{
    /**
     * Formato del código de activo de Almacén (RN-03). Se aceptan 5 o 6 dígitos
     * porque el inventario 2026 tiene ambas series y, hasta que Almacén confirme
     * lo contrario, se tratan como numeraciones distintas (pregunta abierta 4 del
     * análisis): por eso tampoco se rellenan con ceros a la izquierda.
     */
    public const PATRON_CODIGO_ACTIVO = '/^I1-\d{5,6}$/';

    /**
     * Serial del fabricante: en mayúsculas y sin espacios. «  sn-123 » → «SN-123».
     */
    public static function serial(string $valor): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($valor)));
    }

    /**
     * Código de activo al formato I1-######, o null si no se reconoce. Quita
     * espacios, guiones y puntos, corrige la «L1» que se digita por «I1», y
     * acepta el código con prefijo («I1 24147», «i124147», «L1-24147»), solo con
     * los dígitos («020410») o embebido en un texto («I1-003788 Pc todo en Uno»).
     */
    public static function codigoActivo(string $valor): ?string
    {
        $valor = trim($valor);
        $compacto = mb_strtoupper(preg_replace('/[\s\-.]+/u', '', $valor));
        $compacto = preg_replace('/^L1/', 'I1', $compacto);

        if (preg_match('/^I1(\d{5,6})$/', $compacto, $m)) {
            return 'I1-'.$m[1];
        }

        // Solo los dígitos, sin el prefijo de Almacén: «020410» → «I1-020410».
        if (preg_match('/^(\d{5,6})$/', $compacto, $m)) {
            return 'I1-'.$m[1];
        }

        // El código dentro de un texto: «I1-003788 Pc todo en Uno».
        if (preg_match('/\b[IL]1[\s\-]?(\d{5,6})\b/iu', $valor, $m)) {
            return 'I1-'.$m[1];
        }

        return null;
    }

    /**
     * Número de documento sin separadores y en mayúsculas (los pasaportes llevan
     * letras): «1.067.888.999» → «1067888999». Quien llame se encarga de limpiar
     * antes los artefactos de origen (p. ej. el «.0» con que Excel guarda un número).
     */
    public static function documento(string $valor): string
    {
        return mb_strtoupper(preg_replace('/[\s.,\-]+/u', '', trim($valor)));
    }
}
