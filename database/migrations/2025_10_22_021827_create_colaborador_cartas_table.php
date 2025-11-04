<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colaborador_cartas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carta_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('nombre')->nullable(); // Se llena cuando acepta invitación
            $table->enum('rol', ['proveedor', 'contraparte', 'supervisor', 'invitado'])->default('proveedor');
            $table->enum('estado', ['invitado', 'aceptado', 'rechazado', 'bloqueado'])->default('invitado');
            $table->text('mensaje_invitacion')->nullable();
            $table->string('token_invitacion')->unique()->nullable();
            $table->timestamp('invitado_en');
            $table->timestamp('respondido_en')->nullable();
            $table->foreignId('invitado_por')->constrained('users');
            $table->json('permisos')->nullable(); // Permisos específicos
            $table->timestamps();

            // Índices
            $table->index(['carta_id', 'estado']);
            $table->index(['email']);
            $table->index(['token_invitacion']);
            $table->unique(['carta_id', 'email']); // Un email por carta
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colaborador_cartas');
    }
};
