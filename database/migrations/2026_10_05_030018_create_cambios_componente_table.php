<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambios_componente', function (Blueprint $table) {
            $table->id();
            // Extiende Evento 1 a 1 (el evento padre debe ser de tipo cambio_componente).
            $table->foreignId('evento_id')->unique()->constrained('eventos')->onDelete('cascade');
            $table->enum('accion', ['agregar', 'cambiar', 'quitar']);
            // Referencias a componentes son nullOnDelete: el serial en texto queda como
            // respaldo histórico aunque la fila de componentes se elimine en el futuro.
            $table->foreignId('componente_retirado_id')->nullable()->constrained('componentes')->onDelete('set null');
            $table->string('serial_retirado')->nullable();
            $table->foreignId('componente_instalado_id')->nullable()->constrained('componentes')->onDelete('set null');
            $table->string('serial_instalado')->nullable();
            $table->text('motivo')->nullable();
            $table->enum('destino_retirado', ['bodega', 'otro_equipo', 'descarte'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios_componente');
    }
};
