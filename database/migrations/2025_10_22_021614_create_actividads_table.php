<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) { // ← Cambiar aquí
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion');
            $table->decimal('monto', 12, 2);
            $table->decimal('gasto_acumulado', 12, 2)->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_inicio_real')->nullable();
            $table->date('fecha_fin_real')->nullable();
            $table->string('linea_presupuestaria');
            $table->enum('estado', ['pendiente', 'en_curso', 'finalizado', 'atrasado', 'cancelado'])->default('pendiente');
            $table->decimal('progreso', 5, 2)->default(0);
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->text('observaciones')->nullable();
            $table->text('dificultades')->nullable();
            $table->text('proximos_pasos')->nullable();
            $table->foreignId('responsable_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['producto_id', 'estado']);
            $table->index(['responsable_id']);
            $table->index(['fecha_fin', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades'); // ← Y cambiar aquí también
    }
};
