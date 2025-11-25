<?php

use App\Models\Producto;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public Producto $producto;
    public $nombre;
    public $descripcion;
    public $presupuesto;
    public $fecha_inicio;
    public $fecha_fin;

    public function mount(Producto $producto)
    {
        $this->producto = $producto;
        $this->nombre = $producto->nombre;
        $this->descripcion = $producto->descripcion;
        $this->presupuesto = $producto->presupuesto;
        $this->fecha_inicio = $producto->fecha_inicio->format('Y-m-d');
        $this->fecha_fin = $producto->fecha_fin->format('Y-m-d');
    }

    public function rules()
    {
        return [
            'nombre' => 'required|min:3|max:255',
            'descripcion' => 'required|min:10',
            'presupuesto' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ];
    }

    public function save()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $this->producto->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'presupuesto' => $this->presupuesto,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin' => $this->fecha_fin,
            ]);

            DB::commit();

            session()->flash('success', 'Producto actualizado exitosamente');
            return redirect()->route('productos.show', $this->producto);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }
}; ?>

<div title="Editar Producto">
    <div class="p-6 max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('productos.show', $producto) }}"
                   wire:navigate
                   class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Editar Producto</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $producto->carta->codigo }}</p>
                </div>
            </div>
        </div>

        <!-- Alerta de presupuesto sugerido -->
        @if($producto->carta->monto_total)
            @php
                $presupuestoSugerido = $producto->carta->monto_total / $producto->carta->productos->count();
                $totalProductos = $producto->carta->productos->sum('presupuesto');
                $excedeTotal = $totalProductos > $producto->carta->monto_total;
            @endphp

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">
                            💡 Información de Presupuesto
                        </p>
                        <div class="grid md:grid-cols-2 gap-3 text-sm text-blue-700 dark:text-blue-400">
                            <div>
                                <span class="font-medium">Presupuesto sugerido:</span>
                                <span class="block text-lg font-bold">${{ number_format($presupuestoSugerido, 2) }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Presupuesto total carta:</span>
                                <span class="block text-lg font-bold">${{ number_format($producto->carta->monto_total, 2) }}</span>
                            </div>
                        </div>

                        @if($excedeTotal)
                            <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded">
                                <p class="text-sm text-red-700 dark:text-red-400 font-medium">
                                    ⚠️ La suma de presupuestos de todos los productos (${{ number_format($totalProductos, 2) }})
                                    excede el presupuesto total de la carta.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Formulario -->
        <form wire:submit="save" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-6">
            <!-- Nombre -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nombre del Producto *
                </label>
                <input
                    type="text"
                    wire:model="nombre"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    placeholder="Ej: Capacitación de Recursos Humanos"
                >
                @error('nombre')
                <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Descripción -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Descripción *
                </label>
                <textarea
                    wire:model="descripcion"
                    rows="4"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    placeholder="Describe los objetivos y alcance de este producto..."
                ></textarea>
                @error('descripcion')
                <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Presupuesto -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Presupuesto Estimado *
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-500 dark:text-gray-400">$</span>
                    <input
                        type="number"
                        step="0.01"
                        wire:model.live="presupuesto"
                        class="w-full pl-8 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        placeholder="0.00"
                    >
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Nota: El presupuesto real se calculará automáticamente sumando todas las actividades
                </p>
                @error('presupuesto')
                <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Fechas -->
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fecha Inicio *
                    </label>
                    <input
                        type="date"
                        wire:model="fecha_inicio"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                    @error('fecha_inicio')
                    <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fecha Fin *
                    </label>
                    <input
                        type="date"
                        wire:model="fecha_fin"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                    @error('fecha_fin')
                    <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Botones -->
            <div class="flex gap-4 pt-4">
                <button
                    type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                    Guardar Cambios
                </button>
                <a
                    href="{{ route('productos.show', $producto) }}"
                    wire:navigate
                    class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-semibold py-3 px-6 rounded-lg transition text-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
