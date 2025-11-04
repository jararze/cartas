<?php
use App\Models\Actividad;
use Livewire\Volt\Component;

new class extends Component {
    public Actividad $actividad;
    public $totalSeguimientos;
    public $progresoInicial;
    public $progresoActual;
    public $incrementoTotal;

    public function mount(Actividad $actividad): void
    {
        // Cargar la actividad con sus relaciones
        $this->actividad = $actividad->load([
            'producto.carta',
            'seguimientos' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        // Calcular métricas
        $this->totalSeguimientos = $this->actividad->seguimientos->count();
        $this->progresoInicial = $this->actividad->seguimientos->last()?->progreso_anterior ?? 0;
        $this->progresoActual = $this->actividad->progreso;
        $this->incrementoTotal = $this->progresoActual - $this->progresoInicial;
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('cartas.show', $actividad->producto->carta->id) }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        📋 Historial de Seguimientos
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $actividad->nombre }}
                    </p>
                </div>
            </div>

            <a href="{{ route('cartas.show', $actividad->producto->carta->id) }}"
               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Seguimiento
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Información de la Carta y Producto -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Carta</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $actividad->producto->carta->codigo }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $actividad->producto->carta->nombre }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Producto</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $actividad->producto->nombre }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Línea Presupuestaria</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $actividad->linea_presupuestaria }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Progreso Actual -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Progreso Actual</h3>
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($progresoActual, 1) }}%
                        </p>
                        @if($incrementoTotal > 0)
                            <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                                +{{ number_format($incrementoTotal, 1) }}% desde inicio
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Presupuesto -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Presupuesto</h3>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            ${{ number_format($actividad->monto, 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total asignado</p>
                    </div>
                </div>

                <!-- Ejecutado -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Ejecutado</h3>
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            ${{ number_format($actividad->gasto_acumulado, 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $actividad->monto > 0 ? number_format(($actividad->gasto_acumulado / $actividad->monto) * 100, 1) : 0 }}% del presupuesto
                        </p>
                    </div>
                </div>

                <!-- Saldo -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Saldo</h3>
                            <svg class="w-8 h-8 {{ ($actividad->monto - $actividad->gasto_acumulado) < 0 ? 'text-red-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <p class="text-2xl font-bold {{ ($actividad->monto - $actividad->gasto_acumulado) < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                            ${{ number_format(abs($actividad->monto - $actividad->gasto_acumulado), 2) }}
                        </p>
                        @if(($actividad->monto - $actividad->gasto_acumulado) < 0)
                            <p class="text-xs text-red-600 dark:text-red-400 mt-1">⚠️ Sobregiro</p>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Disponible</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Timeline de Seguimientos -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                        Línea de Tiempo ({{ $totalSeguimientos }} registros)
                    </h3>

                    @if($actividad->seguimientos->count() > 0)
                        <div class="space-y-6">
                            @foreach($actividad->seguimientos as $index => $seguimiento)
                                <div class="relative pl-10">
                                    <!-- Línea vertical -->
                                    @if($index !== $actividad->seguimientos->count() - 1)
                                        <div class="absolute left-4 top-10 bottom-0 w-0.5 bg-gray-300 dark:bg-gray-600"></div>
                                    @endif

                                    <!-- Punto del timeline -->
                                    <div class="absolute left-0 top-2 w-8 h-8 rounded-full bg-purple-600 border-4 border-white dark:border-gray-800 flex items-center justify-center">
                                        <span class="text-white text-xs font-bold">{{ $actividad->seguimientos->count() - $index }}</span>
                                    </div>

                                    <!-- Contenido del seguimiento -->
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-5 hover:shadow-md transition">
                                        <!-- Header -->
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <p class="font-semibold text-gray-900 dark:text-white">
                                                        {{ $seguimiento->responsable }}
                                                    </p>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $seguimiento->created_at->format('d/m/Y H:i') }}
                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        ({{ $seguimiento->created_at->diffForHumans() }})
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="flex gap-2">
                                                <span class="px-3 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded-full text-sm font-semibold">
                                                    {{ number_format($seguimiento->progreso_anterior, 1) }}% → {{ number_format($seguimiento->progreso_nuevo, 1) }}%
                                                </span>
                                                <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-sm font-semibold">
                                                    ${{ number_format($seguimiento->monto_gastado, 2) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Métricas del seguimiento -->
                                        <div class="grid grid-cols-4 gap-4 mb-4 p-3 bg-white dark:bg-gray-800 rounded-lg">
                                            <div>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Incremento</p>
                                                <p class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                                    +{{ number_format($seguimiento->progreso_nuevo - $seguimiento->progreso_anterior, 1) }}%
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Gasto Acum.</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                    ${{ number_format($seguimiento->gasto_acumulado, 2) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Saldo Después</p>
                                                <p class="text-sm font-bold {{ $seguimiento->saldo_disponible < 0 ? 'text-red-600' : 'text-blue-600' }}">
                                                    ${{ number_format(abs($seguimiento->saldo_disponible), 2) }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Eficiencia</p>
                                                <p class="text-sm font-bold {{ $seguimiento->indice_eficiencia >= 1 ? 'text-green-600' : 'text-yellow-600' }}">
                                                    {{ number_format($seguimiento->indice_eficiencia, 2) }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Descripción -->
                                        <div class="space-y-3">
                                            <div>
                                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">📝 Descripción:</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 p-3 rounded">
                                                    {{ $seguimiento->descripcion }}
                                                </p>
                                            </div>

                                            @if($seguimiento->logros)
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">✅ Logros:</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 p-3 rounded">
                                                        {{ $seguimiento->logros }}
                                                    </p>
                                                </div>
                                            @endif

                                            @if($seguimiento->dificultades)
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">⚠️ Dificultades:</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 p-3 rounded">
                                                        {{ $seguimiento->dificultades }}
                                                    </p>
                                                </div>
                                            @endif

                                            @if($seguimiento->proximos_pasos)
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">🎯 Próximos Pasos:</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 p-3 rounded">
                                                        {{ $seguimiento->proximos_pasos }}
                                                    </p>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Alertas y Tags -->
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @if($seguimiento->nivel_riesgo && $seguimiento->nivel_riesgo !== 'bajo')
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs font-medium">
                                                    ⚠️ Riesgo: {{ ucfirst($seguimiento->nivel_riesgo) }}
                                                </span>
                                            @endif

                                            @if($seguimiento->saldo_disponible < 0)
                                                <span class="px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-xs font-medium">
                                                    🚨 Sobrepresupuesto
                                                </span>
                                            @endif

                                            @if($seguimiento->etiquetas && is_array($seguimiento->etiquetas))
                                                @foreach($seguimiento->etiquetas as $etiqueta)
                                                    <span class="px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-xs">
                                                        #{{ $etiqueta }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Estado vacío -->
                        <div class="text-center py-12">
                            <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                No hay seguimientos registrados
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                Esta actividad aún no tiene registros de seguimiento.
                            </p>
                            <a href="{{ route('cartas.show', $actividad->producto->carta->id) }}"
                               class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Registrar Primer Seguimiento
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
