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
        Schema::table('kpis', function (Blueprint $table) {
            // Categoría del KPI
            $table->enum('categoria', [
                'social',
                'productivo',
                'ambiental',
                'economico',
                'infraestructura',
                'capacitacion',
                'calidad',
                'otro'
            ])->default('otro')->after('codigo');

            // Asociación opcional a producto/actividad
            $table->foreignId('producto_id')->nullable()->after('carta_id')
                ->constrained('productos')->nullOnDelete();
            $table->foreignId('actividad_id')->nullable()->after('producto_id')
                ->constrained('actividades')->nullOnDelete();

            // Meta y línea base
            $table->decimal('meta', 12, 2)->nullable()->after('tipo_visualizacion');
            $table->decimal('linea_base', 12, 2)->nullable()->after('meta');

            // Frecuencia de medición
            $table->enum('frecuencia', [
                'unico',
                'diario',
                'semanal',
                'quincenal',
                'mensual',
                'trimestral',
                'semestral',
                'anual'
            ])->default('mensual')->after('linea_base');

            // Control de mediciones
            $table->string('fuente_verificacion')->nullable()->after('frecuencia');
            $table->date('proxima_medicion')->nullable()->after('fuente_verificacion');

            // Índices
            $table->index(['categoria', 'activo']);
            $table->index('proxima_medicion');
        });

        // Agregar campo evidencia a kpi_valores
        Schema::table('kpi_valores', function (Blueprint $table) {
            $table->string('evidencia_path')->nullable()->after('notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['actividad_id']);
            $table->dropIndex(['categoria', 'activo']);
            $table->dropIndex(['proxima_medicion']);

            $table->dropColumn([
                'categoria',
                'producto_id',
                'actividad_id',
                'meta',
                'linea_base',
                'frecuencia',
                'fuente_verificacion',
                'proxima_medicion'
            ]);
        });

        Schema::table('kpi_valores', function (Blueprint $table) {
            $table->dropColumn('evidencia_path');
        });
    }
};
