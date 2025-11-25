<?php

use App\Models\Carta;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public Carta $carta;

    public function mount($codigo)
    {
        $this->carta = Carta::with(['proveedor', 'creador', 'productos'])
            ->where('codigo', $codigo)
            ->firstOrFail();

        // Actualizar estado a "vista" si estaba en "enviada"
        if ($this->carta->estado === 'enviada') {
            $this->carta->update([
                'estado' => 'vista',
                'fecha_vista' => now()
            ]);
        }
    }

    public function accept()
    {
        DB::beginTransaction();
        try {
            // Actualizar estado de la carta
            $this->carta->update([
                'estado' => 'aceptada',
                'fecha_respuesta' => now()
            ]);

            // Crear productos automáticamente
            $this->crearProductosIniciales();

            DB::commit();

            session()->flash('success', '¡Invitación aceptada exitosamente! Los productos han sido creados automáticamente.');
            $this->dispatch('carta-updated');

            // Redirigir al dashboard o a la vista de la carta
            return redirect()->route('cartas.show', $this->carta->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al aceptar invitación', [
                'error' => $e->getMessage(),
                'carta_id' => $this->carta->id,
            ]);

            session()->flash('error', 'Error al aceptar la invitación: ' . $e->getMessage());
        }
    }

    public function reject()
    {
        $this->carta->update([
            'estado' => 'rechazada',
            'fecha_respuesta' => now()
        ]);

        session()->flash('error', 'Has rechazado la invitación.');
        $this->dispatch('carta-updated');
    }

    /**
     * Crear productos iniciales desde productos_requeridos
     */
    protected function crearProductosIniciales()
    {
        // Verificar si ya tiene productos
        if ($this->carta->productos()->count() > 0) {
            Log::info('⚠️ Carta ya tiene productos, omitiendo creación automática', [
                'carta_id' => $this->carta->id,
                'productos_existentes' => $this->carta->productos()->count()
            ]);
            return;
        }

        $productosRequeridos = is_string($this->carta->productos_requeridos)
            ? json_decode($this->carta->productos_requeridos, true)
            : $this->carta->productos_requeridos;

        if (!is_array($productosRequeridos) || empty($productosRequeridos)) {
            Log::warning('⚠️ No hay productos requeridos para crear', [
                'carta_id' => $this->carta->id
            ]);
            return;
        }

        // Calcular presupuesto por producto
        $montoTotal = $this->carta->monto_total ?? 0;
        $cantidadProductos = count($productosRequeridos);
        $presupuestoPorProducto = $cantidadProductos > 0
            ? round($montoTotal / $cantidadProductos, 2)
            : 0;

        foreach ($productosRequeridos as $index => $productoNombre) {
            $producto = Producto::create([
                'carta_id' => $this->carta->id,
                'nombre' => $productoNombre,
                'descripcion' => 'Producto generado automáticamente desde la carta documento. Puede editar esta descripción y agregar más detalles.',
                'presupuesto' => $presupuestoPorProducto,
                'fecha_inicio' => $this->carta->fecha_inicio,
                'fecha_fin' => $this->carta->fecha_fin,
                'indicadores_kpi' => [], // Array vacío para KPIs
                'orden' => $index + 1,
            ]);

            Log::info('✅ Producto creado automáticamente', [
                'producto_id' => $producto->id,
                'nombre' => $productoNombre,
                'presupuesto' => $presupuestoPorProducto,
                'carta_id' => $this->carta->id,
            ]);
        }

        Log::info('🎉 Todos los productos creados exitosamente', [
            'carta_id' => $this->carta->id,
            'total_productos' => $cantidadProductos,
        ]);
    }
}; ?>

<section class="w-full">
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800"
         x-data="{ modalAceptar: false, modalRechazar: false }">
        <div class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">

            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-t-xl shadow-lg p-6 border-b-4 border-blue-600">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">FAO Bolivia</h1>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Invitación de Carta Documento</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($carta->estado === 'enviada' || $carta->estado === 'vista') bg-blue-100 text-blue-800
                        @elseif($carta->estado === 'aceptada') bg-green-100 text-green-800
                        @elseif($carta->estado === 'rechazada') bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($carta->estado) }}
                    </span>
                </div>
            </div>

            <!-- Contenido -->
            <div class="bg-white dark:bg-gray-800 shadow-lg p-6 space-y-6">

                <div class="border-l-4 border-blue-600 pl-4">
                    <p class="text-sm font-medium text-gray-500">Código de Carta</p>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $carta->codigo }}</h2>
                    <h3 class="text-xl text-gray-700 dark:text-gray-300 mt-2">{{ $carta->nombre_proyecto }}</h3>
                </div>

                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 border border-emerald-200">
                    <h4 class="font-semibold text-emerald-800 dark:text-emerald-300 mb-2">Dirigido a:</h4>
                    <p class="text-gray-900 dark:text-white font-medium">{{ $carta->proveedor->nombre }}</p>
                    @if($carta->proveedor->empresa)
                        <p class="text-gray-600 text-sm">{{ $carta->proveedor->empresa }}</p>
                    @endif
                    <p class="text-gray-600 text-sm">{{ $carta->proveedor->email }}</p>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Descripción del Servicio</h4>
                    <p class="text-gray-700 dark:text-gray-300">{{ $carta->descripcion_servicios }}</p>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Antecedentes</h4>
                    <p class="text-gray-700 dark:text-gray-300">{{ $carta->antecedentes }}</p>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Servicios Requeridos</h4>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-gray-700 dark:text-gray-300">{{ $carta->servicios_requeridos }}</p>
                    </div>
                </div>

                @if($carta->productos_requeridos && count($carta->productos_requeridos) > 0)
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Productos Requeridos</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($carta->productos_requeridos as $producto)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ $producto }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Fecha Inicio</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($carta->fecha_inicio)->format('d/m/Y') }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Fecha Fin</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($carta->fecha_fin)->format('d/m/Y') }}</p>
                    </div>
                    @if($carta->monto_total)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <p class="text-sm text-gray-500">Presupuesto</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($carta->monto_total, 2) }} {{ $carta->moneda }}</p>
                        </div>
                    @endif
                </div>

                @if($carta->archivos_adjuntos && count($carta->archivos_adjuntos) > 0)
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Archivos Adjuntos</h4>
                        <div class="space-y-2">
                            @foreach($carta->archivos_adjuntos as $archivo)
                                <a href="{{ Storage::url($archivo['path']) }}" target="_blank"
                                   class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $archivo['nombre_original'] }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($archivo['size'] / 1024, 2) }} KB</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($carta->document_path)
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold">Documento Oficial</p>
                                    <p class="text-sm text-gray-600">Carta documento en Word</p>
                                </div>
                            </div>
                            <a href="{{ asset($carta->document_path) }}" download
                               class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                                Descargar
                            </a>
                        </div>
                    </div>
                @endif

                @if($carta->mensaje_invitacion)
                    <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-2">Mensaje del Responsable FAO</h4>
                        <p class="text-gray-700">{{ $carta->mensaje_invitacion }}</p>
                    </div>
                @endif

                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Contacto FAO</h4>
                    <p class="text-gray-700"><span class="font-medium">Responsable:</span> {{ $carta->responsable_fao_nombre }}</p>
                    <p class="text-gray-700"><span class="font-medium">Email:</span> {{ $carta->responsable_fao_email }}</p>
                    @if($carta->responsable_fao_telefono)
                        <p class="text-gray-700"><span class="font-medium">Teléfono:</span> {{ $carta->responsable_fao_telefono }}</p>
                    @endif
                </div>

            </div>

            <!-- NUEVA SECCIÓN: Información de Referencia del Proyecto -->
            <div class="bg-white dark:bg-gray-800 shadow-lg p-6 space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-600 p-4 rounded-lg">
                    <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Información del Proyecto
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6 mt-4">
                        <!-- Período -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <span class="text-2xl">📅</span>
                                Período del Proyecto
                            </h4>
                            <div class="space-y-2">
                                <p class="text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Inicio:</span> {{ \Carbon\Carbon::parse($carta->fecha_inicio)->format('d/m/Y') }}
                                </p>
                                <p class="text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Fin:</span> {{ \Carbon\Carbon::parse($carta->fecha_fin)->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded px-2 py-1 inline-block">
                                    Duración: {{ \Carbon\Carbon::parse($carta->fecha_inicio)->diffInDays($carta->fecha_fin) }} días
                                </p>
                            </div>
                        </div>

                        <!-- Presupuesto Estimado -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <span class="text-2xl">💰</span>
                                Presupuesto Estimado
                            </h4>
                            <p class="text-4xl font-bold text-green-600 dark:text-green-400">
                                ${{ number_format($carta->monto_total, 2) }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $carta->moneda }}
                            </p>
                        </div>
                    </div>

                    <!-- Productos Requeridos -->
                    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <span class="text-2xl">📦</span>
                            Productos/Entregables Requeridos
                        </h4>

                        @if(is_array($carta->productos_requeridos) && count($carta->productos_requeridos) > 0)
                            <ul class="space-y-2 mb-4">
                                @foreach($carta->productos_requeridos as $index => $producto)
                                    <li class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">
                                {{ $index + 1 }}
                            </span>
                                        <div class="flex-1">
                                            <p class="text-gray-900 dark:text-white font-medium">{{ $producto }}</p>
                                            @if($carta->monto_total && count($carta->productos_requeridos) > 0)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
{{--                                                    Presupuesto estimado: <span class="font-semibold">${{ number_format($carta->monto_total / count($carta->productos_requeridos), 2) }}</span>--}}
                                                </p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Nota importante -->
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-green-800 dark:text-green-300 mb-1">
                                            ℹ️ Al aceptar esta invitación:
                                        </p>
                                        <ul class="text-sm text-green-700 dark:text-green-400 space-y-1 list-disc list-inside">
                                            <li>Estos productos se crearán automáticamente en el sistema</li>
                                            <li>Podrás gestionar cada producto individualmente</li>
                                            <li>Podrás crear actividades y asignar presupuestos específicos</li>
                                            <li>Los montos pueden ser ajustados según tus necesidades</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                                <p class="text-gray-500 dark:text-gray-400 italic">No se especificaron productos requeridos</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Botones -->
            @if($carta->estado === 'enviada' || $carta->estado === 'vista')
                <div class="bg-white dark:bg-gray-800 rounded-b-xl shadow-lg p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <flux:button @click="modalRechazar = true" variant="outline" class="flex-1">
                            Rechazar
                        </flux:button>
                        <flux:button @click="modalAceptar = true" variant="primary" class="flex-1">
                            Aceptar Invitación
                        </flux:button>
                    </div>
                </div>
            @endif

            @if($carta->estado === 'aceptada')
                <div class="bg-green-50 rounded-b-xl shadow-lg p-6 border-2 border-green-600">
                    <p class="text-green-800 font-bold">✓ Invitación Aceptada - {{ \Carbon\Carbon::parse($carta->fecha_respuesta)->format('d/m/Y H:i') }}</p>
                </div>
            @endif

            @if($carta->estado === 'rechazada')
                <div class="bg-red-50 rounded-b-xl shadow-lg p-6 border-2 border-red-600">
                    <p class="text-red-800 font-bold">✗ Invitación Rechazada - {{ \Carbon\Carbon::parse($carta->fecha_respuesta)->format('d/m/Y H:i') }}</p>
                </div>
            @endif

        </div>

        <!-- Modal Aceptar -->
        <div x-show="modalAceptar" style="display: none;"
             class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold mb-4">Aceptar Invitación</h3>
                <p class="text-gray-600 mb-6">¿Deseas aceptar esta carta documento?</p>
                <div class="space-y-3">
                    <flux:button wire:click="accept" @click="modalAceptar = false" variant="primary" class="w-full">
                        Sí, Aceptar
                    </flux:button>
                    <flux:button @click="modalAceptar = false" variant="ghost" class="w-full">
                        Cancelar
                    </flux:button>
                </div>
            </div>
        </div>

        <!-- Modal Rechazar -->
        <div x-show="modalRechazar" style="display: none;"
             class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold mb-4">Rechazar Invitación</h3>
                <p class="text-gray-600 mb-6">¿Estás seguro de rechazar esta carta?</p>
                <div class="space-y-3">
                    <flux:button wire:click="reject" @click="modalRechazar = false" variant="danger" class="w-full">
                        Sí, Rechazar
                    </flux:button>
                    <flux:button @click="modalRechazar = false" variant="ghost" class="w-full">
                        Cancelar
                    </flux:button>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
                {{ session('error') }}
            </div>
        @endif

    </div>
</section>
