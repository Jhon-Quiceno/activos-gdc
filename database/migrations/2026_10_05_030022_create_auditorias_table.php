<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            // Nullable + nullOnDelete: se conserva el registro de auditoría aunque el
            // usuario autor sea eliminado posteriormente.
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('accion');
            $table->string('entidad');
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->json('valor_anterior')->nullable();
            $table->json('valor_nuevo')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
