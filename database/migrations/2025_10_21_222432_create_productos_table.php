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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carta_id')->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->text('descripcion');
            $table->json('indicadores_kpi'); // KPIs específicos del producto
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('presupuesto', 12, 2)->nullable();
            $table->integer('orden')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
