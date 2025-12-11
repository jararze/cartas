<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero modificar el enum para agregar 'pendiente_cancelacion'
        DB::statement("ALTER TABLE actividades MODIFY COLUMN estado ENUM('pendiente', 'en_curso', 'finalizado', 'atrasado', 'cancelado', 'pendiente_cancelacion') DEFAULT 'pendiente'");

        Schema::table('actividades', function (Blueprint $table) {
            // Campos para solicitud de cancelación
            $table->text('motivo_cancelacion')->nullable()->after('observaciones');
            $table->timestamp('fecha_solicitud_cancelacion')->nullable();
            $table->foreignId('solicitado_por')->nullable()->constrained('users');

            // Campos para aprobación/rechazo
            $table->enum('estado_cancelacion', ['pendiente', 'aprobada', 'rechazada'])->nullable();
            $table->text('respuesta_cancelacion')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('fecha_respuesta_cancelacion')->nullable();

            // Estado anterior (para restaurar si se rechaza)
            $table->string('estado_anterior_cancelacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropForeign(['solicitado_por']);
            $table->dropForeign(['aprobado_por']);

            $table->dropColumn([
                'motivo_cancelacion',
                'fecha_solicitud_cancelacion',
                'solicitado_por',
                'estado_cancelacion',
                'respuesta_cancelacion',
                'aprobado_por',
                'fecha_respuesta_cancelacion',
                'estado_anterior_cancelacion'
            ]);
        });

        DB::statement("ALTER TABLE actividades MODIFY COLUMN estado ENUM('pendiente', 'en_curso', 'finalizado', 'atrasado', 'cancelado') DEFAULT 'pendiente'");
    }
};
