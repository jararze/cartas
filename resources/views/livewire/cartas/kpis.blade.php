<?php

use Livewire\Volt\Component;
use App\Models\Carta;
use App\Models\Kpi;
use App\Models\KpiValor;

new class extends Component {
    public Carta $carta;
    public $activeTab = 'predefinidos';
    public $kpisPredefinidos = [];
    public $kpisPersonalizados = [];
    public $kpisActivos = [];
    
    // Form para crear KPI personalizado
    public $showCrearModal = false;
    public $editandoKpi = null;
    public $nombre = '';
    public $descripcion = '';
    public $formula = 'sum';
    public $umbral_min = null;
    public $umbral_max = null;
    public $tipo_umbral = 'mayor_mejor';
    public $tipo_visualizacion = 'numero';
    public $unidad_medida = '';

    public function mount(Carta $carta): void
    {
        $this->carta = $carta->load('productos.actividades');
        $this->cargarKpis();
    }

    public function cargarKpis(): void
    {
        // Cargar KPIs activos de la carta
        $this->kpisActivos = $this->carta->kpis()
            ->with('ultimoValor')
            ->activos()
            ->orderBy('orden')
            ->get();

        // Inicializar KPIs predefinidos disponibles
        $this->kpisPredefinidos = $this->obtenerKpisPredefinidosDisponibles();
        
        // Cargar KPIs personalizados
        $this->kpisPersonalizados = $this->carta->kpis()
            ->personalizados()
            ->with('ultimoValor')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function obtenerKpisPredefinidosDisponibles(): array
    {
        return [
            // Financieros
            [
                'codigo' => 'ejecucion_presupuestal',
                'nombre' => 'Ejecución Presupuestal',
                'descripcion' => 'Porcentaje del presupuesto total ejecutado',
                'categoria' => 'Financieros',
                'unidad' => '%',
                'umbral_min' => 0,
                'umbral_max' => 100,
                'tipo_umbral' => 'rango',
                'activo' => $this->esKpiActivo('ejecucion_presupuestal'),
            ],
            [
                'codigo' => 'variacion_presupuestal',
                'nombre' => 'Variación Presupuestal',
                'descripcion' => 'Diferencia entre gasto real y presupuesto planificado',
                'categoria' => 'Financieros',
                'unidad' => '$',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'activo' => $this->esKpiActivo('variacion_presupuestal'),
            ],
            [
                'codigo' => 'burn_rate',
                'nombre' => 'Burn Rate',
                'descripcion' => 'Velocidad de gasto diario promedio',
                'categoria' => 'Financieros',
                'unidad' => '$/día',
                'tipo_umbral' => 'mayor_mejor',
                'activo' => $this->esKpiActivo('burn_rate'),
            ],
            [
                'codigo' => 'cpi',
                'nombre' => 'CPI (Cost Performance Index)',
                'descripcion' => 'Índice de rendimiento de costos',
                'categoria' => 'Financieros',
                'unidad' => '',
                'umbral_min' => 1,
                'tipo_umbral' => 'mayor_mejor',
                'activo' => $this->esKpiActivo('cpi'),
            ],
            
            // Tiempo
            [
                'codigo' => 'tiempo_transcurrido',
                'nombre' => '% Tiempo Transcurrido',
                'descripcion' => 'Porcentaje del tiempo del proyecto transcurrido',
                'categoria' => 'Tiempo',
                'unidad' => '%',
                'tipo_umbral' => 'rango',
                'activo' => $this->esKpiActivo('tiempo_transcurrido'),
            ],
            [
                'codigo' => 'spi',
                'nombre' => 'SPI (Schedule Performance Index)',
                'descripcion' => 'Índice de rendimiento de cronograma',
                'categoria' => 'Tiempo',
                'unidad' => '',
                'umbral_min' => 1,
                'tipo_umbral' => 'mayor_mejor',
                'activo' => $this->esKpiActivo('spi'),
            ],
            [
                'codigo' => 'dias_retraso',
                'nombre' => 'Días de Retraso',
                'descripcion' => 'Días de retraso estimados respecto al cronograma',
                'categoria' => 'Tiempo',
                'unidad' => 'días',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'activo' => $this->esKpiActivo('dias_retraso'),
            ],
            
            // Progreso
            [
                'codigo' => 'progreso_general',
                'nombre' => '% Completitud General',
                'descripcion' => 'Promedio ponderado de progreso de todas las actividades',
                'categoria' => 'Progreso',
                'unidad' => '%',
                'umbral_min' => 0,
                'tipo_umbral' => 'mayor_mejor',
                'activo' => $this->esKpiActivo('progreso_general'),
            ],
            [
                'codigo' => 'actividades_completadas',
                'nombre' => 'Actividades Completadas',
                'descripcion' => 'Número de actividades finalizadas',
                'categoria' => 'Progreso',
                'unidad' => '',
                'tipo_umbral' => 'mayor_mejor',
                'activo' => $this->esKpiActivo('actividades_completadas'),
            ],
            [
                'codigo' => 'productividad',
                'nombre' => 'Productividad',
                'descripcion' => 'Progreso promedio por día',
                'categoria' => 'Progreso',
                'unidad' => '%/día',
                'tipo_umbral' => 'mayor_mejor',
                'activo' => $this->esKpiActivo('productividad'),
            ],
            
            // Riesgo
            [
                'codigo' => 'actividades_riesgo',
                'nombre' => 'Actividades en Riesgo',
                'descripcion' => 'Número de actividades con alertas o problemas',
                'categoria' => 'Riesgo',
                'unidad' => '',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'activo' => $this->esKpiActivo('actividades_riesgo'),
            ],
            [
                'codigo' => 'sobrepresupuestos',
                'nombre' => 'Sobrepresupuestos',
                'descripcion' => 'Actividades que exceden su presupuesto',
                'categoria' => 'Riesgo',
                'unidad' => '',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'activo' => $this->esKpiActivo('sobrepresupuestos'),
            ],
            [
                'codigo' => 'actividades_atrasadas',
                'nombre' => 'Actividades Atrasadas',
                'descripcion' => 'Actividades que están fuera de cronograma',
                'categoria' => 'Riesgo',
                'unidad' => '',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'activo' => $this->esKpiActivo('actividades_atrasadas'),
            ],
        ];
    }

    public function esKpiActivo(string $codigo): bool
    {
        return $this->carta->kpis()
            ->where('codigo', $codigo)
            ->where('activo', true)
            ->exists();
    }

    public function toggleKpiPredefinido(string $codigo): void
    {
        $kpi = $this->carta->kpis()->where('codigo', $codigo)->first();
        
        if ($kpi) {
            $kpi->update(['activo' => !$kpi->activo]);
        } else {
            // Crear el KPI si no existe
            $config = collect($this->obtenerKpisPredefinidosDisponibles())
                ->firstWhere('codigo', $codigo);
            
            if ($config) {
                $kpi = $this->carta->kpis()->create([
                    'nombre' => $config['nombre'],
                    'descripcion' => $config['descripcion'],
                    'tipo' => 'predefinido',
                    'codigo' => $codigo,
                    'unidad_medida' => $config['unidad'],
                    'umbral_min' => $config['umbral_min'] ?? null,
                    'umbral_max' => $config['umbral_max'] ?? null,
                    'tipo_umbral' => $config['tipo_umbral'],
                    'tipo_visualizacion' => 'numero',
                    'activo' => true,
                    'creado_por' => auth()->id(),
                ]);
                
                // Calcular valor inicial
                $kpi->calcularValor();
            }
        }
        
        $this->cargarKpis();
    }

    public function recalcularKpis(): void
    {
        foreach ($this->kpisActivos as $kpi) {
            $kpi->calcularValor();
        }
        
        $this->cargarKpis();
        session()->flash('message', 'KPIs recalculados exitosamente');
    }

    public function abrirModalCrear(): void
    {
        $this->reset(['nombre', 'descripcion', 'formula', 'umbral_min', 'umbral_max', 'tipo_umbral', 'tipo_visualizacion', 'unidad_medida']);
        $this->editandoKpi = null;
        $this->showCrearModal = true;
    }

    public function editarKpi($kpiId): void
    {
        $kpi = Kpi::findOrFail($kpiId);
        $this->editandoKpi = $kpi;
        $this->nombre = $kpi->nombre;
        $this->descripcion = $kpi->descripcion;
        $this->formula = $kpi->formula;
        $this->umbral_min = $kpi->umbral_min;
        $this->umbral_max = $kpi->umbral_max;
        $this->tipo_umbral = $kpi->tipo_umbral;
        $this->tipo_visualizacion = $kpi->tipo_visualizacion;
        $this->unidad_medida = $kpi->unidad_medida;
        $this->showCrearModal = true;
    }

    public function guardarKpiPersonalizado(): void
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'formula' => 'required|string',
            'tipo_umbral' => 'required|in:mayor_mejor,menor_mejor,rango',
            'tipo_visualizacion' => 'required|in:numero,porcentaje,moneda,grafico',
        ]);

        if ($this->editandoKpi) {
            $this->editandoKpi->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'formula' => $this->formula,
                'umbral_min' => $this->umbral_min,
                'umbral_max' => $this->umbral_max,
                'tipo_umbral' => $this->tipo_umbral,
                'tipo_visualizacion' => $this->tipo_visualizacion,
                'unidad_medida' => $this->unidad_medida,
            ]);
        } else {
            $this->carta->kpis()->create([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'tipo' => 'personalizado',
                'formula' => $this->formula,
                'umbral_min' => $this->umbral_min,
                'umbral_max' => $this->umbral_max,
                'tipo_umbral' => $this->tipo_umbral,
                'tipo_visualizacion' => $this->tipo_visualizacion,
                'unidad_medida' => $this->unidad_medida,
                'activo' => true,
                'creado_por' => auth()->id(),
            ]);
        }

        $this->cargarKpis();
        $this->showCrearModal = false;
        session()->flash('message', $this->editandoKpi ? 'KPI actualizado' : 'KPI creado exitosamente');
    }

    public function eliminarKpi($kpiId): void
    {
        Kpi::findOrFail($kpiId)->delete();
        $this->cargarKpis();
        session()->flash('message', 'KPI eliminado exitosamente');
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-8 h-8 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Panel de KPIs
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $carta->codigo }} - {{ $carta->nombre_proyecto }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('cartas.show', $carta->id) }}" 
                        class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">
                        Volver a Carta
                    </a>
                    <button wire:click="recalcularKpis" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Recalcular
                    </button>
                </div>
            </div>

            @if (session()->has('message'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
                    {{ session('message') }}
                </div>
            @endif
        </div>

        {{-- Tabs --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg overflow-hidden">
            <div class="border-b border-gray-200 dark:border-slate-700">
                <nav class="flex -mb-px">
                    <button wire:click="$set('activeTab', 'predefinidos')" 
                        class="py-4 px-6 text-sm font-medium border-b-2 transition-colors
                            {{ $activeTab === 'predefinidos' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                        KPIs Predefinidos
                    </button>
                    <button wire:click="$set('activeTab', 'personalizados')" 
                        class="py-4 px-6 text-sm font-medium border-b-2 transition-colors
                            {{ $activeTab === 'personalizados' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                        KPIs Personalizados
                    </button>
                </nav>
            </div>

            {{-- Tab: KPIs Predefinidos --}}
            <div x-show="$wire.activeTab === 'predefinidos'" class="p-6">
                @php
                    $categorias = collect($kpisPredefinidos)->groupBy('categoria');
                @endphp

                @foreach($categorias as $categoria => $kpis)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <span class="w-2 h-8 bg-gradient-to-b from-blue-600 to-purple-600 rounded mr-3"></span>
                            {{ $categoria }}
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($kpis as $kpi)
                                @php
                                    $kpiActual = $this->kpisActivos->firstWhere('codigo', $kpi['codigo']);
                                    $ultimoValor = $kpiActual?->ultimoValor;
                                @endphp
                                
                                <div class="bg-gradient-to-br from-white to-gray-50 dark:from-slate-700 dark:to-slate-800 rounded-lg p-4 border-2 
                                    {{ $kpi['activo'] ? 'border-blue-500' : 'border-gray-200 dark:border-slate-600' }} 
                                    hover:shadow-md transition-all cursor-pointer"
                                    wire:click="toggleKpiPredefinido('{{ $kpi['codigo'] }}')">
                                    
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $kpi['nombre'] }}</h4>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $kpi['descripcion'] }}</p>
                                        </div>
                                        <input type="checkbox" 
                                            checked="{{ $kpi['activo'] }}" 
                                            class="ml-2 h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                            onclick="event.stopPropagation()">
                                    </div>

                                    @if($kpi['activo'] && $ultimoValor)
                                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-600">
                                            <div class="flex items-end justify-between">
                                                <div>
                                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                                        @if($kpi['unidad'] === '$')
                                                            ${{ number_format($ultimoValor->valor, 2) }}
                                                        @elseif($kpi['unidad'] === '%')
                                                            {{ number_format($ultimoValor->valor, 1) }}%
                                                        @else
                                                            {{ number_format($ultimoValor->valor, 2) }} {{ $kpi['unidad'] }}
                                                        @endif
                                                    </div>
                                                    @if($ultimoValor->tendencia)
                                                        <div class="text-sm {{ $ultimoValor->color_tendencia }} mt-1">
                                                            {{ $ultimoValor->icono_tendencia }}
                                                            @if($ultimoValor->porcentaje_cambio)
                                                                {{ abs($ultimoValor->porcentaje_cambio) }}%
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                @if($ultimoValor->en_alerta)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                        {{ $ultimoValor->tipo_alerta === 'critica' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                                        ⚠
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Gráficos de KPIs Activos --}}
                @if($kpisActivos->isNotEmpty())
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tendencias</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($kpisActivos->take(4) as $kpi)
                                @php
                                    $valores = $kpi->valores()->limit(10)->orderBy('fecha_calculo', 'asc')->get();
                                @endphp
                                
                                <div class="bg-white dark:bg-slate-700 rounded-lg p-4 shadow">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ $kpi->nombre }}</h4>
                                    <div class="h-32 flex items-end space-x-1">
                                        @foreach($valores as $valor)
                                            <div class="flex-1 bg-blue-600 rounded-t hover:bg-blue-700 transition-colors" 
                                                style="height: {{ $valores->max('valor') > 0 ? ($valor->valor / $valores->max('valor') * 100) : 0 }}%"
                                                title="{{ $valor->fecha_calculo->format('d/m/Y') }}: {{ $valor->valor }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tab: KPIs Personalizados --}}
            <div x-show="$wire.activeTab === 'personalizados'" class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tus KPIs Personalizados</h3>
                    <button wire:click="abrirModalCrear" 
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-colors shadow-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Crear KPI
                    </button>
                </div>

                @forelse($kpisPersonalizados as $kpi)
                    <div class="bg-white dark:bg-slate-700 rounded-lg p-6 mb-4 shadow-md">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $kpi->nombre }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $kpi->descripcion }}</p>
                                
                                @if($kpi->ultimoValor)
                                    <div class="mt-4">
                                        <div class="text-3xl font-bold text-blue-600">
                                            {{ number_format($kpi->ultimoValor->valor, 2) }} {{ $kpi->unidad_medida }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex space-x-2">
                                <button wire:click="editarKpi({{ $kpi->id }})" 
                                    class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-600 rounded">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="eliminarKpi({{ $kpi->id }})" 
                                    class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-slate-600 rounded">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Sin KPIs personalizados</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Crea tu primer KPI personalizado para comenzar.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Modal Crear/Editar KPI --}}
    @if($showCrearModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" 
            wire:click.self="$set('showCrearModal', false)">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ $editandoKpi ? 'Editar KPI' : 'Crear KPI Personalizado' }}
                    </h3>

                    <form wire:submit="guardarKpiPersonalizado" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre del KPI <span class="text-red-600">*</span>
                            </label>
                            <input type="text" wire:model="nombre" 
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            @error('nombre') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Descripción
                            </label>
                            <textarea wire:model="descripcion" rows="2"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Fórmula <span class="text-red-600">*</span>
                                </label>
                                <select wire:model="formula" 
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                    <option value="sum">Suma</option>
                                    <option value="avg">Promedio</option>
                                    <option value="count">Conteo</option>
                                    <option value="percentage">Porcentaje</option>
                                    <option value="ratio">Razón</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo Umbral <span class="text-red-600">*</span>
                                </label>
                                <select wire:model="tipo_umbral" 
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                    <option value="mayor_mejor">Mayor es mejor</option>
                                    <option value="menor_mejor">Menor es mejor</option>
                                    <option value="rango">Rango específico</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Umbral Mínimo
                                </label>
                                <input type="number" wire:model="umbral_min" step="0.01"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Umbral Máximo
                                </label>
                                <input type="number" wire:model="umbral_max" step="0.01"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Visualización <span class="text-red-600">*</span>
                                </label>
                                <select wire:model="tipo_visualizacion" 
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                    <option value="numero">Número</option>
                                    <option value="porcentaje">Porcentaje</option>
                                    <option value="moneda">Moneda</option>
                                    <option value="grafico">Gráfico</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Unidad de Medida
                                </label>
                                <input type="text" wire:model="unidad_medida" 
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                    placeholder="%, $, días, etc.">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" wire:click="$set('showCrearModal', false)" 
                                class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                                Cancelar
                            </button>
                            <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 shadow-lg">
                                {{ $editandoKpi ? 'Actualizar' : 'Crear' }} KPI
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
