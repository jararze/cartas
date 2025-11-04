<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartas', function (Blueprint $table) {
            $table->id();
            // Información básica
            $table->string('codigo')->unique(); // CARTA-2025-001
            $table->string('nombre_proyecto');
            $table->text('descripcion_servicios');

            // Información FAO (se llena desde el usuario)
            $table->foreignId('creado_por')->constrained('users');
            $table->string('oficina_fao');
            $table->string('responsable_fao_nombre');
            $table->string('responsable_fao_email');
            $table->string('responsable_fao_telefono')->nullable();

            // Contenido de la carta
            $table->text('antecedentes');
            $table->text('servicios_requeridos');
            $table->json('productos_requeridos'); // Array de productos

            // Fechas del proyecto
            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // Presupuesto
            $table->decimal('monto_total', 12, 2)->nullable();
            $table->enum('moneda', ['USD', 'BOB'])->default('USD');

            // Relación con proveedor
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors')->onDelete('set null');

            // Estados y control
            $table->enum('estado', [
                'borrador',
                'enviada',
                'vista',
                'aceptada',
                'rechazada',
                'en_ejecucion',
                'finalizada',
                'cancelada'
            ])->default('borrador');

            // Configuración de envío
            $table->enum('tipo_envio', ['email', 'whatsapp', 'ambos'])->default('email');

            // Invitación
            $table->text('mensaje_invitacion')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_vista')->nullable();
            $table->timestamp('fecha_respuesta')->nullable();

            // Archivos
            $table->string('archivo_pdf')->nullable(); // PDF generado
            $table->json('archivos_adjuntos')->nullable(); // Otros archivos

            $table->timestamps();

            // Índices
            $table->index(['estado', 'created_at']);
            $table->index('creado_por');
            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas');
    }
};
