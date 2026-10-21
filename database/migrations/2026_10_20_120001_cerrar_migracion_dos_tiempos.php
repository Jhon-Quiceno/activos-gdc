<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cierre de la migración en dos tiempos (docs/base-de-datos.md §6.2).
 *
 * Todos los bloques ya usan los campos nuevos del modelo v4, así que se borran
 * las columnas viejas que convivían durante la transición y se vuelven
 * obligatorias las que quedaron opcionales mientras duraba.
 *
 * Fuera de este cierre, a propósito: pasar `equipos.estado_funcionamiento` y
 * `componentes.estado` a enum (`bueno`, `regular`, `malo`, `no_funcional`). Es
 * una normalización de visualización que toca los formularios de Equipos, la
 * importación y los PDF, y va en su propio PR para no mezclarla con el cierre.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Borrar las columnas viejas (la sede sale del piso; nombre/cédula/cargo
        //    salen de los campos nuevos; modelo y propietario, de sus catálogos).
        Schema::table('asignaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });

        Schema::table('puestos_trabajo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'cedula', 'cargo']);
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['modelo', 'propietario_tercero']);
        });

        // 2. Volver obligatorias las columnas que la transición dejó opcionales.
        //    ALTER directo para no recrear las FK ni el índice único de
        //    pisos (sede_id, numero).
        //
        //    `personas.numero_documento` NO se vuelve obligatorio (a diferencia
        //    de lo previsto en §6.2): la carga de inventario crea a propósito
        //    responsables sin documento cuando el Excel no trae la cédula
        //    (CargaEquipos::personaSinDocumento, RF-38/39), y los reutiliza por
        //    nombre. El índice único ya convive con varios NULL en MySQL.
        DB::statement('ALTER TABLE pisos MODIFY sede_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE sedes MODIFY municipio_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sedes MODIFY municipio_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE pisos MODIFY sede_id BIGINT UNSIGNED NULL');

        Schema::table('equipos', function (Blueprint $table) {
            $table->string('modelo')->nullable();
            $table->string('propietario_tercero')->nullable();
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->string('nombre')->nullable();
            $table->string('cedula')->nullable();
            $table->string('cargo')->nullable();
        });

        Schema::table('puestos_trabajo', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('id')->constrained('sedes')->restrictOnDelete();
        });

        Schema::table('asignaciones', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('persona_id')->constrained('sedes')->restrictOnDelete();
        });
    }
};
