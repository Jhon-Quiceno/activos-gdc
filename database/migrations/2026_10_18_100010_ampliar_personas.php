<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: la persona responsable usa el catálogo oficial de cargos
 * (Decreto 607), su ubicación real y su jefe inmediato (planta de personal).
 *
 * Migración en dos tiempos: `nombre`, `cedula` y `cargo` (texto) siguen
 * existiendo y App\Models\Persona los mantiene sincronizados con los campos
 * nuevos. La migración de cierre del día 2 los borra, cuando ningún bloque
 * los use (ver docs/base-de-datos.md §6).
 *
 * Minimización (Ley 1581, RNF-19): de la planta de Talento Humano solo se
 * guardan estos campos. Nada de salario, RH, estado civil, etnia, salud ni
 * dirección de residencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->enum('tipo_documento', ['CC', 'CE', 'PPT', 'PA', 'TI'])->default('CC')->after('id');
            // Dato personal: se enmascara en listados (RN-12).
            $table->string('numero_documento', 20)->nullable()->unique()->after('tipo_documento');
            $table->string('nombres', 80)->nullable()->after('numero_documento');
            $table->string('apellidos', 80)->nullable()->after('nombres');
            $table->string('correo_institucional', 120)->nullable()->after('apellidos');
            $table->foreignId('cargo_id')->nullable()->after('cargo')->constrained('cargos')->restrictOnDelete();
            $table->char('grado', 2)->nullable()->after('cargo_id');
            // Cargo ejercido en encargo: las «(E)» de la planta.
            $table->boolean('encargo')->default(false)->after('grado');
            $table->foreignId('jefe_inmediato_id')->nullable()->after('dependencia_id')->constrained('personas')->nullOnDelete();
            $table->enum('planta', ['central', 'educacion'])->nullable()->after('jefe_inmediato_id');
            $table->enum('tipo_vinculacion', ['planta', 'contratista', 'pasante'])->nullable()->change();
            $table->enum('tipo_provision', [
                'carrera_administrativa',
                'libre_nombramiento',
                'provisional',
                'temporal',
                'periodo',
                'no_aplica',
            ])->default('no_aplica')->after('tipo_vinculacion');
            $table->string('numero_contrato', 40)->nullable()->after('tipo_provision');
            // Fin de contrato o retiro: dispara el paz y salvo (RF-23).
            $table->date('fecha_fin_vinculacion')->nullable()->after('numero_contrato');
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cargo_id');
            $table->dropConstrainedForeignId('jefe_inmediato_id');
            $table->dropUnique(['numero_documento']);
            $table->dropColumn([
                'tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'correo_institucional',
                'grado', 'encargo', 'planta', 'tipo_provision', 'numero_contrato', 'fecha_fin_vinculacion',
            ]);
            $table->enum('tipo_vinculacion', ['planta', 'contratista'])->nullable()->change();
        });
    }
};
