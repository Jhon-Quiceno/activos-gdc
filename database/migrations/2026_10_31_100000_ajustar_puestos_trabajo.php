<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F3-1 (RF-05): `puestos_trabajo.activo` (un puesto se desactiva, no se borra:
 * los equipos lo referencian por sus asignaciones) y `unique(piso_id,
 * nombre_o_codigo)` (dos puestos en el mismo piso no comparten código).
 *
 * `equipos.puesto_trabajo_id` se elimina: es un resto de v3 que ningún código
 * escribe hoy (docs/base-de-datos.md, sección 10.2). La fuente de verdad del
 * puesto de un equipo es la asignación abierta (`asignaciones.puesto_trabajo_id`),
 * igual que el piso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puestos_trabajo', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('dependencia_id');
            $table->unique(['piso_id', 'nombre_o_codigo']);
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('puesto_trabajo_id');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->foreignId('puesto_trabajo_id')->nullable()->constrained('puestos_trabajo')->onDelete('restrict');
        });

        Schema::table('puestos_trabajo', function (Blueprint $table) {
            $table->dropUnique(['piso_id', 'nombre_o_codigo']);
            $table->dropColumn('activo');
        });
    }
};
