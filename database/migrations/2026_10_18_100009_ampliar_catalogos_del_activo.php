<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: catálogos del activo. Los tipos de equipo se clasifican en
 * hardware o soporte (GT-M4, tabla 2), los sistemas operativos llevan su
 * fecha de fin de soporte y aparece el catálogo de modelos por marca y tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_equipo', function (Blueprint $table) {
            $table->enum('familia', [
                'computo',
                'video',
                'impresion',
                'conectividad',
                'digitalizacion',
                'energia',
                'proyeccion',
                'seguridad_electronica',
                'climatizacion',
                'telefonia',
            ])->change();
            $table->enum('tipo_activo', ['hardware', 'soporte'])->default('hardware')->after('familia');
            // Muestra sistema operativo, antivirus, red y componentes internos.
            $table->boolean('usa_configuracion_computo')->default(false)->after('tipo_activo');
            $table->unsignedSmallInteger('vida_util_meses')->nullable()->after('campos_aplicables');
            $table->boolean('activo')->default(true)->after('vida_util_meses');
        });

        Schema::table('sistemas_operativos', function (Blueprint $table) {
            // Alimenta el reporte de obsolescencia: null = con soporte vigente.
            $table->date('fin_soporte')->nullable()->after('nombre');
        });

        Schema::table('tipos_componente', function (Blueprint $table) {
            // RAM, disco y procesador: obligatorios para dar un equipo de cómputo por verificado (RF-14).
            $table->boolean('obligatorio_en_computo')->default(false)->after('es_periferico');
        });

        Schema::create('modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marca_id')->constrained('marcas')->restrictOnDelete();
            $table->foreignId('tipo_equipo_id')->constrained('tipos_equipo')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->timestamps();

            $table->unique(['marca_id', 'tipo_equipo_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos');

        Schema::table('tipos_componente', function (Blueprint $table) {
            $table->dropColumn('obligatorio_en_computo');
        });

        Schema::table('sistemas_operativos', function (Blueprint $table) {
            $table->dropColumn('fin_soporte');
        });

        Schema::table('tipos_equipo', function (Blueprint $table) {
            $table->dropColumn(['tipo_activo', 'usa_configuracion_computo', 'vida_util_meses', 'activo']);
            $table->enum('familia', [
                'computo', 'video', 'impresion', 'conectividad', 'digitalizacion', 'energia', 'proyeccion',
            ])->change();
        });
    }
};
