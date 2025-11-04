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
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo'); // financiero, avance, actividades, etc
            $table->text('descripcion')->nullable();
            $table->json('filtros')->nullable();
            $table->json('configuracion')->nullable();
            $table->foreignId('creado_por')->constrained('users');
            $table->boolean('publico')->default(false);
            $table->integer('veces_generado')->default(0);
            $table->timestamp('ultima_generacion')->nullable();
            $table->timestamps();
        });

        Schema::create('reportes_generados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained()->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('formato'); // pdf, excel, csv
            $table->string('archivo_path');
            $table->json('parametros')->nullable();
            $table->timestamps();
            $table->index(['reporte_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportes');
        Schema::dropIfExists('reportes_generados');
    }
};
