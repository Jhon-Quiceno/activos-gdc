<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo v4: la ficha del equipo suma el consecutivo de activo del FOR.01,
 * el proceso al que da soporte, el modelo de catálogo y los datos de
 * adquisición y garantía (RF-52). La configuración de cómputo suma
 * ofimática, dominio, IP y MAC.
 *
 * Migración en dos tiempos: `equipos.modelo` y `equipos.propietario_tercero`
 * (texto) siguen existiendo y App\Models\Equipo los mantiene sincronizados
 * con `modelo_id` y `proveedor_tercero_id`. Se borran en la migración de
 * cierre del día 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // «ID ACTIVO» del FOR.01. Lo asigna App\Models\Equipo al crear y nunca cambia (RN-27).
            $table->unsignedInteger('numero_activo')->nullable()->unique()->after('qr_uuid');
            $table->foreignId('modelo_id')->nullable()->after('modelo')->constrained('modelos')->restrictOnDelete();
            $table->string('descripcion', 255)->nullable()->after('modelo_id');
            $table->foreignId('proceso_id')->nullable()->after('caracteristicas')->constrained('procesos')->restrictOnDelete();
            $table->foreignId('proveedor_tercero_id')->nullable()->after('propietario_tercero')->constrained('proveedores')->restrictOnDelete();
            $table->enum('figura_tercero', ['comodato', 'convenio', 'proveedor', 'arrendamiento'])->nullable()->change();
            $table->enum('forma_ingreso', ['compra', 'donacion', 'comodato', 'convenio', 'arrendamiento', 'carga_inicial'])
                ->default('carga_inicial')->after('figura_tercero');
            $table->date('fecha_adquisicion')->nullable()->after('forma_ingreso');
            // Pesos colombianos (RNF-14).
            $table->decimal('valor_adquisicion', 15, 2)->nullable()->after('fecha_adquisicion');
            $table->foreignId('proveedor_id')->nullable()->after('valor_adquisicion')->constrained('proveedores')->restrictOnDelete();
            $table->string('numero_contrato', 40)->nullable()->after('proveedor_id');
            $table->string('numero_factura', 40)->nullable()->after('numero_contrato');
            $table->date('fecha_fin_garantia')->nullable()->after('numero_factura');
            $table->unsignedSmallInteger('vida_util_meses')->nullable()->after('fecha_fin_garantia');
        });

        Schema::table('configuraciones_computo', function (Blueprint $table) {
            $table->string('ofimatica', 80)->nullable()->after('licencia');
            $table->string('licencia_ofimatica', 80)->nullable()->after('ofimatica');
            $table->string('dominio', 60)->nullable()->after('nombre_red');
            $table->string('direccion_ip', 45)->nullable()->after('dominio');
            $table->string('direccion_mac', 17)->nullable()->after('direccion_ip');
        });

        Schema::table('componentes', function (Blueprint $table) {
            $table->string('modelo', 80)->nullable()->after('marca');
        });
    }

    public function down(): void
    {
        Schema::table('componentes', function (Blueprint $table) {
            $table->dropColumn('modelo');
        });

        Schema::table('configuraciones_computo', function (Blueprint $table) {
            $table->dropColumn(['ofimatica', 'licencia_ofimatica', 'dominio', 'direccion_ip', 'direccion_mac']);
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('modelo_id');
            $table->dropConstrainedForeignId('proceso_id');
            $table->dropConstrainedForeignId('proveedor_tercero_id');
            $table->dropConstrainedForeignId('proveedor_id');
            $table->dropUnique(['numero_activo']);
            $table->dropColumn([
                'numero_activo', 'descripcion', 'forma_ingreso', 'fecha_adquisicion', 'valor_adquisicion',
                'numero_contrato', 'numero_factura', 'fecha_fin_garantia', 'vida_util_meses',
            ]);
            $table->enum('figura_tercero', ['comodato', 'convenio', 'proveedor'])->nullable()->change();
        });
    }
};
