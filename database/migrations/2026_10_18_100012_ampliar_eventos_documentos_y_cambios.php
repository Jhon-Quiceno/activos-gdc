<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: nuevo tipo de evento `valoracion` (reclasificación GT-M4),
 * marca de evento histórico (altas de carga inicial, exentas de RN-13),
 * acta de aceptación como documento y equipo destino de lo retirado.
 * El mantenimiento sigue fuera del alcance (RN-08): no hay tipo de evento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->enum('tipo', [
                'alta',
                'traslado_responsable',
                'cambio_componente',
                'diagnostico',
                'valoracion',
                'baja',
                'actualizacion_datos',
                'anulacion_aclaracion',
            ])->change();
            $table->boolean('es_historico')->default(false)->after('fecha');
        });

        Schema::table('documentos', function (Blueprint $table) {
            $table->enum('tipo', ['formato_entrega', 'formato_baja', 'acta_aceptacion_activos', 'foto', 'otro'])->change();
        });

        Schema::table('cambios_componente', function (Blueprint $table) {
            // Cuando lo retirado va a otro equipo (RF-17).
            $table->foreignId('equipo_destino_id')->nullable()->after('destino_retirado')->constrained('equipos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cambios_componente', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_destino_id');
        });

        Schema::table('documentos', function (Blueprint $table) {
            $table->enum('tipo', ['formato_entrega', 'formato_baja', 'foto', 'otro'])->change();
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn('es_historico');
            $table->enum('tipo', [
                'alta', 'traslado_responsable', 'cambio_componente', 'diagnostico',
                'baja', 'actualizacion_datos', 'anulacion_aclaracion',
            ])->change();
        });
    }
};
