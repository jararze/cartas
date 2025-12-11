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
        Schema::table('productos', function (Blueprint $table) {
            $table->enum('estado', [
                'pendiente',      // Recién creado, sin avance
                'en_progreso',    // Tiene actividades en curso
                'completado',     // 100% progreso, pendiente aprobación coordinador
                'aprobado',       // Coordinador aprobó, listo para desembolso
                'rechazado',      // Coordinador rechazó
                'desembolsado'    // Finanzas procesó el pago
            ])->default('pendiente')->after('orden');

            $table->text('motivo_rechazo')->nullable()->after('estado');
            $table->foreignId('aprobado_por')->nullable()->after('motivo_rechazo')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_aprobacion')->nullable()->after('aprobado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['aprobado_por']);
            $table->dropColumn(['estado', 'motivo_rechazo', 'aprobado_por', 'fecha_aprobacion']);
        });
    }
};
