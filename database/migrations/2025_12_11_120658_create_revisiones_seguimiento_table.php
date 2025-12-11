<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seguimiento_actividad_id')->constrained('seguimiento_actividades')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('tipo', [
                'observacion',
                'solicitud',
                'correccion',
                'aprobacion',
                'rechazo'
            ]);

            $table->text('comentario');

            $table->enum('estado', [
                'pendiente',
                'atendido',
                'cerrado'
            ])->default('pendiente');

            $table->text('respuesta_proveedor')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('fecha_respuesta')->nullable();

            $table->timestamps();

            $table->index(['seguimiento_actividad_id', 'created_at']);
            $table->index(['estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_seguimiento');
    }
};
