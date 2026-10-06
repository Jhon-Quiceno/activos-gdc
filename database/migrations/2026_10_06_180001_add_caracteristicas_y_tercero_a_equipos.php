<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // Características propias del tipo de equipo (pulgadas, VA, puertos,
            // lúmenes, etc.). Varían según `tipo_equipo_id`, por eso van en JSON en
            // vez de una columna por característica (ver `campos_aplicables` en
            // tipos_equipo, que ya describe qué claves aplican a cada tipo).
            $table->json('caracteristicas')->nullable()->after('modelo');

            // Figura del tercero cuando propiedad = 'tercero'. `propietario_tercero`
            // ya guardaba el nombre de la entidad; esto agrega bajo qué figura legal
            // está el equipo.
            $table->enum('figura_tercero', ['comodato', 'convenio', 'proveedor'])
                ->nullable()
                ->after('propietario_tercero');

            $table->text('observaciones')->nullable()->after('estado_funcionamiento');

            // RN-03: el código de activo puede repetirse (ej. All in One + su pantalla
            // comparten código), pero el sistema exige justificación cuando eso pasa.
            $table->text('codigo_activo_justificacion')->nullable()->after('codigo_activo');

            // RN-03 permite códigos repetidos con justificación, así que ya no puede
            // ser `unique()` a nivel de base de datos; la validación de "si está
            // repetido, pedí justificación" queda a nivel de aplicación (formulario).
            $table->dropUnique(['codigo_activo']);
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['caracteristicas', 'figura_tercero', 'observaciones', 'codigo_activo_justificacion']);
            $table->unique('codigo_activo');
        });
    }
};
