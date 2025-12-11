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
            // Valor actual calculado/registrado
            $table->decimal('valor_actual', 15, 2)->nullable()->after('linea_base');

            // Última medición realizada
            $table->date('ultima_medicion')->nullable()->after('proxima_medicion');

            // Tendencia del KPI
            $table->enum('tendencia', ['subiendo', 'bajando', 'estable'])->nullable()->after('ultima_medicion');
        });
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn(['valor_actual', 'ultima_medicion', 'tendencia']);
        });
    }
};
