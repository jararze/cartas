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
        Schema::create('desembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->foreignId('carta_id')->constrained()->onDelete('cascade');

            // Montos
            $table->decimal('monto_total', 12, 2); // Total del producto
            $table->decimal('monto_aprobado', 12, 2)->nullable(); // Lo que finanzas aprueba

            // Estado del desembolso
            $table->enum('estado', [
                'pendiente',      // Esperando revisión de finanzas
                'en_proceso',     // Finanzas lo está procesando
                'pagado',         // Transferencia realizada
                'rechazado'       // Finanzas rechazó
            ])->default('pendiente');

            // Desglose por línea presupuestaria (JSON)
            $table->json('desglose_lineas'); // [{linea: 'Consultoría', monto: 5000}, ...]

            // Usuarios involucrados
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('procesado_por')->nullable()->constrained('users')->nullOnDelete();

            // Fechas
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamp('fecha_proceso')->nullable();
            $table->timestamp('fecha_pago')->nullable();

            // Datos de pago
            $table->string('numero_transferencia')->nullable();
            $table->string('banco')->nullable();
            $table->string('cuenta_destino')->nullable();
            $table->string('comprobante_path')->nullable(); // Archivo adjunto

            // Observaciones
            $table->text('observaciones')->nullable();
            $table->text('motivo_rechazo')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['estado', 'fecha_solicitud']);
            $table->index('carta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desembolsos');
    }
};
