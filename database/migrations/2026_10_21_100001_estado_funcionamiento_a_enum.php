<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pasa `equipos.estado_funcionamiento` y `componentes.estado` de texto libre a
 * una lista cerrada (§9 del análisis, GT-M4): bueno, regular, malo, no_funcional.
 *
 * Primero normaliza lo que ya hubiera guardado (los textos «Bueno», «Malo,
 * requiere cambio de componentes», «Dañado»…) a la clave del enum, y luego
 * cambia el tipo de la columna. En una base recién migrada las tablas están
 * vacías y los seeders ya insertan las claves nuevas.
 */
return new class extends Migration
{
    public function up(): void
    {
        // equipos.estado_funcionamiento → clave del enum.
        DB::statement("UPDATE equipos SET estado_funcionamiento = CASE
            WHEN LOWER(estado_funcionamiento) LIKE 'no funcional%' OR LOWER(estado_funcionamiento) LIKE '%dañad%' OR LOWER(estado_funcionamiento) LIKE '%danad%' THEN 'no_funcional'
            WHEN LOWER(estado_funcionamiento) LIKE 'malo%' OR LOWER(estado_funcionamiento) LIKE '%cambio%' THEN 'malo'
            WHEN LOWER(estado_funcionamiento) LIKE 'regular%' THEN 'regular'
            ELSE 'bueno' END");

        DB::statement("ALTER TABLE equipos MODIFY estado_funcionamiento ENUM('bueno','regular','malo','no_funcional') NOT NULL DEFAULT 'bueno'");

        // componentes.estado → clave del enum (nullable: puede no estar registrado).
        DB::statement("UPDATE componentes SET estado = CASE
            WHEN estado IS NULL OR estado = '' THEN NULL
            WHEN LOWER(estado) LIKE 'no funcional%' OR LOWER(estado) LIKE '%dañad%' OR LOWER(estado) LIKE '%danad%' THEN 'no_funcional'
            WHEN LOWER(estado) LIKE 'malo%' OR LOWER(estado) LIKE '%cambio%' THEN 'malo'
            WHEN LOWER(estado) LIKE 'regular%' THEN 'regular'
            ELSE 'bueno' END");

        DB::statement("ALTER TABLE componentes MODIFY estado ENUM('bueno','regular','malo','no_funcional') NULL");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE componentes MODIFY estado VARCHAR(255) NULL');
        DB::statement('ALTER TABLE equipos MODIFY estado_funcionamiento VARCHAR(255) NULL');
    }
};
