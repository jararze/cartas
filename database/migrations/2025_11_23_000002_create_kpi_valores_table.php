<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_valores', function (Blueprint $table) {
            $table->id();
            
            // Relación con KPI
            $table->foreignId('kpi_id')->constrained('kpis')->onDelete('cascade');
            
            // Valor calculado
            $table->decimal('valor', 12, 2);
            $table->decimal('valor_anterior', 12, 2)->nullable();
            
            // Metadata del cálculo
            $table->json('datos_calculo')->nullable(); // Datos usados para el cálculo
            $table->text('notas')->nullable();
            
            // Tendencia
            $table->enum('tendencia', ['subiendo', 'bajando', 'estable'])->nullable();
            $table->decimal('porcentaje_cambio', 5, 2)->nullable();
            
            // Estado de alerta
            $table->boolean('en_alerta')->default(false);
            $table->enum('tipo_alerta', ['critica', 'advertencia', 'normal'])->default('normal');
            
            // Fecha del cálculo
            $table->timestamp('fecha_calculo')->useCurrent();
            
            // Auditoría
            $table->foreignId('calculado_por')->nullable()->constrained('users');
            $table->timestamps();
            
            // Índices
            $table->index(['kpi_id', 'fecha_calculo']);
            $table->index(['en_alerta', 'tipo_alerta']);
            $table->index('fecha_calculo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_valores');
    }
};
