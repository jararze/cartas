<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_actividades', function (Blueprint $table) {
            $table->id();

            // Relación con actividad
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');

            // Progreso
            $table->decimal('progreso_anterior', 5, 2);
            $table->decimal('progreso_nuevo', 5, 2);

            // Presupuesto
            $table->decimal('monto_gastado', 12, 2);
            $table->decimal('gasto_acumulado_anterior', 12, 2);
            $table->decimal('gasto_acumulado_nuevo', 12, 2);

            // Descripción y detalles
            $table->text('descripcion_avance');
            $table->text('logros')->nullable();
            $table->text('dificultades')->nullable();
            $table->text('proximos_pasos')->nullable();

            // Fechas
            $table->date('nueva_fecha_inicio')->nullable();
            $table->date('nueva_fecha_fin')->nullable();
            $table->date('proxima_revision')->nullable();

            // Responsable
            $table->string('responsable_nombre');
            $table->text('observaciones')->nullable();

            // Estados
            $table->enum('estado_anterior', ['pendiente', 'en_curso', 'finalizado', 'atrasado', 'cancelado']);
            $table->enum('estado_nuevo', ['pendiente', 'en_curso', 'finalizado', 'atrasado', 'cancelado']);

            // Indicadores de alerta
            $table->boolean('excede_presupuesto')->default(false);
            $table->boolean('esta_atrasado')->default(false);

            // Variaciones presupuestarias
            $table->decimal('variacion_presupuesto', 12, 2)->default(0);
            $table->decimal('variacion_presupuesto_porcentaje', 5, 2)->default(0);

            // Métricas de eficiencia
            $table->decimal('indice_eficiencia', 5, 2)->nullable();
            $table->decimal('costo_por_unidad_trabajo', 12, 2)->nullable();

            // Planificación vs realidad
            $table->integer('dias_planificados')->nullable();
            $table->integer('dias_reales')->nullable();

            // Riesgos y calidad
            $table->enum('nivel_riesgo', ['bajo', 'medio', 'alto', 'critico'])->default('bajo');
            $table->text('riesgos_identificados')->nullable();
            $table->text('acciones_correctivas')->nullable();

            // Evidencia
            $table->json('archivos_adjuntos')->nullable();
            $table->json('imagenes')->nullable();

            // Etiquetas/Tags
            $table->json('etiquetas')->nullable();

            // Revisión
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->timestamp('fecha_revision')->nullable();
            $table->enum('estado_revision', ['pendiente', 'aprobado', 'rechazado', 'requiere_cambios'])->default('pendiente');
            $table->text('comentarios_revision')->nullable();

            // Auditoría
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamp('fecha_registro');

            $table->timestamps();

            // Índices para optimización
            $table->index(['actividad_id', 'fecha_registro']);
            $table->index(['registrado_por']);
            $table->index(['fecha_registro']);
            $table->index(['estado_revision']);
            $table->index(['excede_presupuesto']);
            $table->index(['nivel_riesgo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_actividades');
    }
};
