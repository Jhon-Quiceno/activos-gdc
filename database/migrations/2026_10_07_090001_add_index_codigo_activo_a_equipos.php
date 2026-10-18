<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // La migración 2026_10_06_180001 quitó el unique() de codigo_activo (RN-03
            // permite códigos repetidos con justificación) pero no dejó ningún índice
            // en su lugar. Sin uno, cada búsqueda/filtro por código de activo (listado
            // de equipos, la validación de RN-03 en Equipo::validarJustificacionCodigoActivo,
            // etc.) hace table scan. Un índice simple (no único) alcanza.
            $table->index('codigo_activo');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['codigo_activo']);
        });
    }
};
