<?php
// resources/views/livewire/cartas/public-view.blade.php

use App\Models\Carta;
use Livewire\Volt\Component;

new class extends Component {
    public $carta;
    public $codigo;

    public function mount($codigo)
    {
        $this->codigo = $codigo;
        $this->carta = Carta::with('proveedor', 'creador')
            ->where('codigo', $codigo)
            ->first();

        if (!$this->carta) {
            abort(404, 'Carta no encontrada');
        }
    }

    public function accept()
    {
        $this->carta->update([
            'estado' => 'aceptada',
            'fecha_respuesta' => now()
        ]);

        session()->flash('success', 'Invitación aceptada exitosamente');
    }

    public function reject()
    {
        $this->carta->update([
            'estado' => 'rechazada',
            'fecha_respuesta' => now()
        ]);

        session()->flash('success', 'Invitación rechazada');
    }
}; ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Invitación a Proyecto FAO</h1>
            <p class="text-gray-600">Código: {{ $carta->codigo }}</p>
        </div>

        <!-- Contenido de la carta -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">{{ $carta->nombre_proyecto }}</h2>
            <p class="text-gray-700 mb-4">{{ $carta->descripcion_servicios }}</p>

            <!-- Más detalles de la carta... -->
        </div>

        <!-- Botones de acción -->
        @if($carta->estado === 'enviada')
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex gap-4">
                    <button wire:click="accept" class="bg-green-600 text-white px-6 py-3 rounded-lg">
                        Aceptar Invitación
                    </button>
                    <button wire:click="reject" class="bg-red-600 text-white px-6 py-3 rounded-lg">
                        Rechazar
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
