<?php

namespace App\Livewire\Equipos\Concerns;

/**
 * Normalización de los identificadores que se digitan a mano al registrar o
 * editar un equipo (serial, código de activo y cédula del responsable).
 *
 * Se aplica antes de validar, para que la unicidad (RN-02, RN-03) se compare
 * siempre sobre el mismo formato y no se cuelen duplicados por diferencias de
 * espacios, guiones o mayúsculas.
 */
trait NormalizaIdentificadores
{
    /**
     * Formato del código de activo de Almacén (RN-03). Se aceptan 5 o 6 dígitos
     * porque el inventario 2026 tiene ambas series y, hasta que Almacén confirme
     * lo contrario, se tratan como numeraciones distintas (pregunta abierta 4 del
     * análisis): por eso tampoco se rellenan con ceros a la izquierda.
     */
    protected const PATRON_CODIGO_ACTIVO = '/^I1-\d{5,6}$/';

    /**
     * Serial del fabricante: sin espacios al inicio o al final, espacios internos
     * colapsados y en mayúsculas («  sn-123 » → «SN-123»).
     */
    protected function normalizarSerial(string $serial): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim($serial)));
    }

    /**
     * Código de activo al formato I1-###### (D-03 del análisis): quita espacios,
     * guiones y puntos, pasa a mayúsculas y corrige la «L1» que se digita por
     * «I1» («I1 24147», «i124147», «L1-24147» → «I1-24147»).
     *
     * Si el valor no se parece al patrón se devuelve tal cual (en mayúsculas)
     * para que la validación muestre el error sobre lo que la persona escribió.
     */
    protected function normalizarCodigoActivo(string $codigo): string
    {
        $codigo = mb_strtoupper(trim($codigo));
        $compacto = preg_replace('/[\s\-.]+/u', '', $codigo);

        if (str_starts_with($compacto, 'L1')) {
            $compacto = 'I1'.substr($compacto, 2);
        }

        if (preg_match('/^I1(\d{5,6})$/', $compacto, $partes)) {
            return 'I1-'.$partes[1];
        }

        return $codigo;
    }

    /**
     * Cédula del responsable: solo dígitos. Quita los separadores con que suele
     * escribirse («1.067.888.999», «1067 888 999»); si queda algo que no sea un
     * dígito, la validación lo rechaza.
     */
    protected function normalizarCedula(string $cedula): string
    {
        return preg_replace('/[\s.,\-]+/u', '', trim($cedula));
    }
}
