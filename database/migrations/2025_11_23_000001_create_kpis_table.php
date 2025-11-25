<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            
            // Relación con carta
            $table->foreignId('carta_id')->constrained('cartas')->onDelete('cascade');
            
            // Información del KPI
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            
            // Tipo de KPI
            $table->enum('tipo', ['predefinido', 'personalizado'])->default('personalizado');
            $table->string('codigo')->nullable(); // Para KPIs predefinidos: ejecucion_presupuestal, spi, etc.
            
            // Configuración de cálculo
            $table->string('formula')->nullable(); // Formula para calcular: sum, avg, percentage, ratio, etc.
            $table->json('campos_calculo')->nullable(); // Campos usados en el cálculo
            
            // Umbral de alerta
            $table->decimal('umbral_min', 12, 2)->nullable();
            $table->decimal('umbral_max', 12, 2)->nullable();
            $table->enum('tipo_umbral', ['mayor_mejor', 'menor_mejor', 'rango'])->default('mayor_mejor');
            
            // Visualización
            $table->enum('tipo_visualizacion', ['numero', 'porcentaje', 'moneda', 'grafico'])->default('numero');
            $table->string('unidad_medida')->nullable(); // %, $, días, etc.
            $table->string('color')->default('#3B82F6'); // Color para gráficos
            
            // Orden y estado
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->boolean('mostrar_en_dashboard')->default(true);
            
            // Auditoría
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();
            
            // Índices
            $table->index(['carta_id', 'activo']);
            $table->index(['tipo', 'activo']);
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
