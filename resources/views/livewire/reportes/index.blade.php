<?php
use Livewire\Volt\Component;
use App\Services\ReporteService;
use App\Models\Carta;

new class extends Component {
    public $tipoReporte = 'resumen';
    public $fechaInicioDescarga;
    public $fechaFinDescarga;
    public $cartaSeleccionadaDescarga = null;
    public $estadoFiltroDescarga = null;
    public $formato = 'pdf';
    public $vistaActual = 'estadisticas';
    public $filtroCartaVista = null;
    public $filtroEstadoVista = null;

    public function mount()
    {
        // Solo para los filtros de descarga
        $this->fechaInicioDescarga = now()->startOfMonth()->format('Y-m-d');
        $this->fechaFinDescarga = now()->format('Y-m-d');
    }

    public function limpiarFiltrosVista()
    {
        $this->filtroCartaVista = null;
        $this->filtroEstadoVista = null;
    }

    private function getReportesDisponibles()
    {
        return [
            'resumen' => 'Resumen Ejecutivo',
            'financiero' => 'Análisis Financiero Detallado',
            'avance' => 'Avance de Actividades',
            'comparativo' => 'Comparativo de Cartas',
            'linea_presupuestaria' => 'Análisis por Línea Presupuestaria',
        ];
    }

    public function cambiarVista($vista)
    {
        $this->vistaActual = $vista;

    }

    public function generarReporte()
    {
        // Usar los filtros de DESCARGA
        $url = route('reportes.descargar', [
            'tipo' => $this->tipoReporte,
            'formato' => $this->formato,
            'fecha_inicio' => $this->fechaInicioDescarga,
            'fecha_fin' => $this->fechaFinDescarga,
            'carta_id' => $this->cartaSeleccionadaDescarga,
            'estado' => $this->estadoFiltroDescarga,
        ]);

        $this->js("window.open('{$url}', '_blank')");
    }


    public function getEstadisticasProperty()
    {
        $service = new ReporteService();
        return $service->getEstadisticasGenerales();
    }

    public function getDatosFinancierosProperty()
    {
        $service = new ReporteService();
        return $service->getDatosFinancieros([
            'carta_id' => $this->filtroCartaVista,
            'estado' => $this->filtroEstadoVista,
        ]);
    }

    public function getDatosAvanceProperty()
    {
        $service = new ReporteService();
        // Sin filtros - mostrar TODO
        return $service->getReporteAvanceActividades([]);
    }

    public function getAnalisisLineasProperty()
    {
        $service = new ReporteService();
        // Sin filtros - mostrar TODO
        return $service->getAnalisisPorLineaPresupuestaria([]);
    }

    public function getAlertasProperty()
    {
        $service = new ReporteService();
        return $service->getAlertasYRiesgos();
    }

    public function getCartasProperty()
    {
        return Carta::select('id', 'codigo', 'nombre_proyecto')->get();
    }

    public function getReportesDisponiblesProperty()
    {
        return [
            'resumen' => 'Resumen Ejecutivo',
            'financiero' => 'Análisis Financiero Detallado',
            'avance' => 'Avance de Actividades',
            'comparativo' => 'Comparativo de Cartas',
            'linea_presupuestaria' => 'Análisis por Línea Presupuestaria',
        ];
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        Centro de Reportes & Analytics
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">Análisis inteligente de datos y generación de reportes</p>
                </div>

                <button wire:click="generarReporte"
                        class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Generar Reporte
                </button>
            </div>
        </div>

        <!-- Indicador de carga -->
        <div wire:loading wire:target="cambiarVista" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-700 dark:text-gray-300">Cargando datos...</p>
            </div>
        </div>

        <!-- Filtros de Visualización (Opcional) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6 p-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Filtros de Visualización</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Carta</label>
                    <select wire:model.live="filtroCartaVista" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todas las cartas</option>
                        @foreach($this->cartas as $carta)
                            <option value="{{ $carta->id }}">{{ $carta->codigo }} - {{ $carta->nombre_proyecto }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estado</label>
                    <select wire:model.live="filtroEstadoVista" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_curso">En Curso</option>
                        <option value="finalizado">Finalizado</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button wire:click="limpiarFiltrosVista" class="w-full px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition">
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Navegación de Vistas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6 p-2">
            <div class="flex flex-wrap gap-2">
                <button wire:click="cambiarVista('estadisticas')"
                        class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $vistaActual === 'estadisticas' ? 'bg-gradient-to-r from-blue-500 to-purple-600 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    📊 Estadísticas
                </button>
                <button wire:click="cambiarVista('financiero')"
                        class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $vistaActual === 'financiero' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    💰 Financiero
                </button>
                <button wire:click="cambiarVista('avance')"
                        class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $vistaActual === 'avance' ? 'bg-gradient-to-r from-orange-500 to-red-600 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    📈 Avances
                </button>
                <button wire:click="cambiarVista('lineaPresupuestaria')"
                        class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $vistaActual === 'lineaPresupuestaria' ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    📋 Líneas Presupuestarias
                </button>
                <button wire:click="cambiarVista('alertas')"
                        class="px-4 py-2 rounded-lg font-medium transition-all duration-200 {{ $vistaActual === 'alertas' ? 'bg-gradient-to-r from-red-500 to-pink-600 text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    🚨 Alertas
                    @if($this->alertas['total_alertas'] > 0)
                        <span class="ml-1 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">{{ $this->alertas['total_alertas'] }}</span>
                    @endif
                </button>
            </div>
        </div>

        <!-- Vista: Estadísticas Generales -->
        @if($vistaActual === 'estadisticas')
            <div wire:key="vista-estadisticas">
                <!-- KPIs Principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Total Cartas -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">Total</span>
                        </div>
                        <p class="text-sm text-blue-100 mb-1">Total Cartas</p>
                        <p class="text-4xl font-bold">{{ $this->estadisticas['total_cartas'] }}</p>
                        <div class="flex items-center gap-4 mt-3 text-sm">
                            <span>✅ {{ $this->estadisticas['cartas_activas'] }} activas</span>
                            <span>🏁 {{ $this->estadisticas['cartas_finalizadas'] }} finalizadas</span>
                        </div>
                    </div>

                    <!-- Presupuesto Total -->
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold bg-white/20 px-3 py-1 rounded-full">USD</span>
                        </div>
                        <p class="text-sm text-green-100 mb-1">Presupuesto Total</p>
                        <p class="text-4xl font-bold">${{ number_format($this->estadisticas['total_presupuesto'], 0) }}</p>
                        <div class="mt-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span>Ejecutado</span>
                                <span>${{ number_format($this->estadisticas['total_ejecutado'], 0) }}</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-2">
                                <div class="bg-white h-2 rounded-full" style="width: {{ $this->estadisticas['ejecucion_presupuestaria'] }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividades -->
                    <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm text-purple-100 mb-1">Actividades</p>
                        <p class="text-4xl font-bold">{{ $this->estadisticas['actividades_total'] }}</p>
                        <div class="grid grid-cols-2 gap-2 mt-3 text-xs">
                            <div class="bg-white/20 rounded-lg p-2">
                                <span class="block">✅ Completadas</span>
                                <span class="font-bold">{{ $this->estadisticas['actividades_completadas'] }}</span>
                            </div>
                            <div class="bg-white/20 rounded-lg p-2">
                                <span class="block">🔄 En curso</span>
                                <span class="font-bold">{{ $this->estadisticas['actividades_en_curso'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progreso Promedio -->
                    <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm text-orange-100 mb-1">Progreso Promedio</p>
                        <p class="text-4xl font-bold">{{ number_format($this->estadisticas['progreso_promedio'], 1) }}%</p>
                        <div class="mt-3">
                            <div class="w-full bg-white/20 rounded-full h-3">
                                <div class="bg-white h-3 rounded-full transition-all duration-500" style="width: {{ $this->estadisticas['progreso_promedio'] }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Detalladas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Estado de Actividades -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">📊</span>
                            Estado de Actividades
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">✅ Completadas</span>
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->estadisticas['actividades_completadas'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">🔄 En Curso</span>
                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->estadisticas['actividades_en_curso'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">⏳ Pendientes</span>
                                <span class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $this->estadisticas['actividades_pendientes'] }}</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                <span class="text-gray-700 dark:text-gray-300 font-medium">⚠️ Atrasadas</span>
                                <span class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->estadisticas['actividades_atrasadas'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen Financiero -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">💰</span>
                            Resumen Financiero
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600 dark:text-gray-400">Presupuesto Total</span>
                                    <span class="font-bold text-gray-900 dark:text-white">${{ number_format($this->estadisticas['total_presupuesto'], 2) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-gray-400 dark:bg-gray-500 h-2 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600 dark:text-gray-400">Ejecutado</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">${{ number_format($this->estadisticas['total_ejecutado'], 2) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ $this->estadisticas['ejecucion_presupuestaria'] }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($this->estadisticas['ejecucion_presupuestaria'], 1) }}%</span>
                            </div>

                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600 dark:text-gray-400">Saldo Disponible</span>
                                    <span class="font-bold text-blue-600 dark:text-blue-400">${{ number_format($this->estadisticas['saldo_disponible'], 2) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" style="width: {{ 100 - $this->estadisticas['ejecucion_presupuestaria'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Vista: Financiero -->
        @if($vistaActual === 'financiero')
            <div wire:key="vista-financiero" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Análisis Financiero Detallado</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Código</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Proyecto</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Presupuesto</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Ejecutado</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Saldo</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">% Ejecución</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Estado</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->datosFinancieros ?? [] as $carta)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $carta['codigo'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $carta['nombre_proyecto'] }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">${{ number_format($carta['presupuesto_total'], 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-green-600 dark:text-green-400">${{ number_format($carta['ejecutado_total'], 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-blue-600 dark:text-blue-400">${{ number_format($carta['saldo'], 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full" style="width: {{ $carta['porcentaje_ejecucion'] }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($carta['porcentaje_ejecucion'], 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($carta['estado'] === 'en_ejecucion') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($carta['estado'] === 'finalizada') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $carta['estado'])) }}
                                </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No hay datos financieros disponibles con los filtros seleccionados
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Vista: Avance de Actividades -->
        @if($vistaActual === 'avance')
            <div wire:key="vista-avance" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Avance de Actividades</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Carta</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Producto</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Actividad</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Progreso</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Presupuesto</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Ejecutado</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Estado</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->datosAvance ?? [] as $actividad)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $actividad['carta'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $actividad['producto'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $actividad['nombre'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-orange-500 to-red-500 h-2 rounded-full" style="width: {{ $actividad['progreso_real'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $actividad['progreso_real'] ?? 0 }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">${{ number_format($actividad['presupuesto'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-green-600 dark:text-green-400">${{ number_format($actividad['ejecutado'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                @if(($actividad['estado'] ?? '') === 'finalizado') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif(($actividad['estado'] ?? '') === 'en_curso') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif(($actividad['estado'] ?? '') === 'atrasado') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $actividad['estado'] ?? 'pendiente')) }}
                            </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No hay datos de avance disponibles
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Vista: Líneas Presupuestarias -->
        @if($vistaActual === 'lineaPresupuestaria')
            <div wire:key="vista-lineaPresupuestaria" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Análisis por Línea Presupuestaria</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Línea Presupuestaria</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Presupuesto</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Ejecutado</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Saldo</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">% Ejecución</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Actividades</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($this->analisisLineas ?? [] as $linea)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $linea['linea_presupuestaria'] ?? $linea['nombre'] ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 dark:text-white">${{ number_format($linea['presupuesto'] ?? $linea['total_presupuesto'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-green-600 dark:text-green-400">${{ number_format($linea['ejecutado'] ?? $linea['total_ejecutado'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-blue-600 dark:text-blue-400">${{ number_format($linea['saldo'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" style="width: {{ $linea['porcentaje_ejecucion'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($linea['porcentaje_ejecucion'] ?? 0, 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm font-semibold text-gray-900 dark:text-white">{{ $linea['actividades_count'] ?? $linea['total_actividades'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No hay datos de líneas presupuestarias disponibles
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Vista: Alertas -->
        @if($vistaActual === 'alertas')
            <div wire:key="vista-alertas" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Actividades Atrasadas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Actividades Atrasadas</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Requieren atención inmediata</p>
                        </div>
                    </div>
                    <div class="text-center py-8">
                        <p class="text-5xl font-bold text-red-600 dark:text-red-400">{{ $this->alertas['atrasadas'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">actividades con retraso</p>
                    </div>
                </div>

                <!-- Exceden Presupuesto -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Exceden Presupuesto</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Sobrepasan el monto asignado</p>
                        </div>
                    </div>
                    <div class="text-center py-8">
                        <p class="text-5xl font-bold text-orange-600 dark:text-orange-400">{{ $this->alertas['exceden_presupuesto'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">actividades sobre presupuesto</p>
                    </div>
                </div>

                <!-- Riesgo Alto -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Riesgo Alto</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Actividades en situación crítica</p>
                        </div>
                    </div>
                    <div class="text-center py-8">
                        <p class="text-5xl font-bold text-yellow-600 dark:text-yellow-400">{{ $this->alertas['riesgo_alto'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">actividades de alto riesgo</p>
                    </div>
                </div>

                <!-- Próximas a Vencer -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Próximas a Vencer</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">En los próximos 7 días</p>
                        </div>
                    </div>
                    <div class="text-center py-8">
                        <p class="text-5xl font-bold text-blue-600 dark:text-blue-400">{{ $this->alertas['proximas_vencer'] }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">actividades por vencer</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Panel de Configuración de Reporte -->
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Configuración de Descarga
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Reporte</label>
                    <select wire:model="tipoReporte" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($this->reportesDisponibles as $key => $nombre)
                            <option value="{{ $key }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Formato</label>
                    <select wire:model="formato" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="pdf">📄 PDF</option>
                        <option value="excel">📊 Excel</option>
                        <option value="csv">📑 CSV</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Inicio</label>
                    <input type="date" wire:model="fechaInicioDescarga" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Fin</label>
                    <input type="date" wire:model="fechaFinDescarga" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>
    </div>
</div>
