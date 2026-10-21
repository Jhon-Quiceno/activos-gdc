<?php

namespace App\Livewire\Equipos\Concerns;

use App\Support\Identificadores;

/**
 * Normalización de los identificadores que se digitan a mano al registrar o
 * editar un equipo (serial, código de activo y cédula del responsable).
 *
 * Se aplica antes de validar, para que la unicidad (RN-02, RN-03) se compare
 * siempre sobre el mismo formato y no se cuelen duplicados por diferencias de
 * espacios, guiones o mayúsculas.
 *
 * Las reglas viven en App\Support\Identificadores (fuente única compartida con
 * la carga de inventario). Este trait solo adapta la política del formulario:
 * un código que no se reconoce se devuelve tal cual (en mayúsculas) para que la
 * validación muestre el error sobre lo que la persona escribió.
 */
trait NormalizaIdentificadores
{
    protected const PATRON_CODIGO_ACTIVO = Identificadores::PATRON_CODIGO_ACTIVO;

    protected function normalizarSerial(string $serial): string
    {
        return Identificadores::serial($serial);
    }

    protected function normalizarCodigoActivo(string $codigo): string
    {
        return Identificadores::codigoActivo($codigo) ?? mb_strtoupper(trim($codigo));
    }

    protected function normalizarDocumento(string $documento): string
    {
        return Identificadores::documento($documento);
    }
}
