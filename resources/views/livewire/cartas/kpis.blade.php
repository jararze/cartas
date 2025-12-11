<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Carta;
use App\Models\Kpi;
use App\Models\KpiValor;
use App\Models\Producto;
use App\Models\Actividad;
use Illuminate\Support\Str;

new class extends Component {
    public Carta $carta;

    // Modales
    public bool $showSeleccionarModal = false;
    public bool $showCrearModal = false;
    public bool $showRegistrarValorModal = false;
    public bool $showHistorialModal = false;

    // Selección de predefinidos
    public array $predefinidosSeleccionados = [];

    // Formulario KPI personalizado
    public ?int $editandoKpiId = null;
    public string $nombre = '';
    public string $descripcion = '';
    public string $categoria = 'social';
    public ?int $producto_id = null;
    public ?int $actividad_id = null;
    public string $unidad_medida = '';
    public ?float $meta = null;
    public ?float $linea_base = null;
    public string $frecuencia = 'mensual';
    public string $fuente_verificacion = '';
    public string $tipo_umbral = 'mayor_mejor';
    public ?float $umbral_min = null;
    public ?float $umbral_max = null;

    // Registrar valor
    public ?Kpi $kpiSeleccionado = null;
    public ?float $nuevo_valor = null;
    public string $valor_observaciones = '';
    public string $valor_fecha = '';

    // Historial
    public ?Kpi $kpiHistorial = null;

    // Listas dinámicas
    public $productos = [];
    public $actividades = [];

    // Definición de KPIs predefinidos
    public array $kpisPredefinidosConfig = [
        'financieros' => [
            'titulo' => '💰 Financieros',
            'kpis' => [
                'ejecucion_presupuestaria' => [
                    'nombre' => 'Ejecución Presupuestaria',
                    'descripcion' => 'Porcentaje del presupuesto total que ha sido ejecutado',
                    'unidad' => '%',
                    'tipo_umbral' => 'rango',
                ],
                'cpi' => [
                    'nombre' => 'CPI - Índice de Rendimiento de Costo',
                    'descripcion' => 'Eficiencia del gasto (>1 = bajo presupuesto, <1 = sobre presupuesto)',
                    'unidad' => 'índice',
                    'tipo_umbral' => 'mayor_mejor',
                ],
                'burn_rate' => [
                    'nombre' => 'Burn Rate',
                    'descripcion' => 'Velocidad promedio de gasto diario en USD',
                    'unidad' => '$/día',
                    'tipo_umbral' => 'menor_mejor',
                ],
                'variacion_costos' => [
                    'nombre' => 'Variación de Costos',
                    'descripcion' => 'Diferencia entre presupuesto planificado y ejecutado',
                    'unidad' => '$',
                    'tipo_umbral' => 'menor_mejor',
                ],
            ]
        ],
        'tiempo' => [
            'titulo' => '⏱️ Tiempo',
            'kpis' => [
                'avance_temporal' => [
                    'nombre' => 'Avance Temporal',
                    'descripcion' => 'Porcentaje del tiempo total del proyecto transcurrido',
                    'unidad' => '%',
                    'tipo_umbral' => 'rango',
                ],
                'spi' => [
                    'nombre' => 'SPI - Índice de Rendimiento de Cronograma',
                    'descripcion' => 'Eficiencia del cronograma (>1 = adelantado, <1 = atrasado)',
                    'unidad' => 'índice',
                    'tipo_umbral' => 'mayor_mejor',
                ],
                'dias_restantes' => [
                    'nombre' => 'Días Restantes',
                    'descripcion' => 'Días calendario hasta la fecha de fin del proyecto',
                    'unidad' => 'días',
                    'tipo_umbral' => 'mayor_mejor',
                ],
            ]
        ],
        'progreso' => [
            'titulo' => '📈 Progreso',
            'kpis' => [
                'progreso_general' => [
                    'nombre' => 'Progreso General',
                    'descripcion' => 'Porcentaje promedio de avance de todas las actividades',
                    'unidad' => '%',
                    'tipo_umbral' => 'mayor_mejor',
                ],
                'actividades_completadas' => [
                    'nombre' => 'Actividades Completadas',
                    'descripcion' => 'Número de actividades finalizadas vs total',
                    'unidad' => 'unidades',
                    'tipo_umbral' => 'mayor_mejor',
                ],
                'eficiencia' => [
                    'nombre' => 'Eficiencia',
                    'descripcion' => 'Relación entre progreso técnico y ejecución presupuestaria',
                    'unidad' => '%',
                    'tipo_umbral' => 'mayor_mejor',
                ],
            ]
        ],
        'riesgo' => [
            'titulo' => '⚠️ Riesgo',
            'kpis' => [
                'actividades_atrasadas' => [
                    'nombre' => 'Actividades Atrasadas',
                    'descripcion' => 'Número de actividades que superaron su fecha de fin',
                    'unidad' => 'unidades',
                    'tipo_umbral' => 'menor_mejor',
                ],
                'indice_riesgo' => [
                    'nombre' => 'Índice de Riesgo',
                    'descripcion' => 'Evaluación general del riesgo del proyecto (0-100)',
                    'unidad' => 'puntos',
                    'tipo_umbral' => 'menor_mejor',
                ],
            ]
        ],
    ];

    public function mount(Carta $carta): void
    {
        $this->carta = $carta;
        $this->productos = $carta->productos()->get();
        $this->valor_fecha = now()->format('Y-m-d');

        // Cargar KPIs predefinidos ya activados
        $kpisActivos = $carta->kpis()->where('tipo', 'predefinido')->pluck('codigo')->toArray();
        $this->predefinidosSeleccionados = $kpisActivos;
    }

    public function updatedProductoId($value): void
    {
        $this->actividad_id = null;
        $this->actividades = [];

        if ($value) {
            $this->actividades = Actividad::where('producto_id', $value)->get();
        }
    }

    // ==================== MODAL SELECCIONAR PREDEFINIDOS ====================

    public function abrirSeleccionarModal(): void
    {
        $kpisActivos = $this->carta->kpis()->where('tipo', 'predefinido')->pluck('codigo')->toArray();
        $this->predefinidosSeleccionados = $kpisActivos;
        $this->showSeleccionarModal = true;
    }

    public function toggleTodos(): void
    {
        $todosLosKpis = [];
        foreach ($this->kpisPredefinidosConfig as $categoria) {
            foreach ($categoria['kpis'] as $clave => $kpi) {
                $todosLosKpis[] = $clave;
            }
        }

        if (count($this->predefinidosSeleccionados) === count($todosLosKpis)) {
            $this->predefinidosSeleccionados = [];
        } else {
            $this->predefinidosSeleccionados = $todosLosKpis;
        }
    }

    public function guardarSeleccionPredefinidos(): void
    {
        // Eliminar los que ya no están seleccionados
        $this->carta->kpis()
            ->where('tipo', 'predefinido')
            ->whereNotIn('codigo', $this->predefinidosSeleccionados)
            ->delete();

        // Agregar los nuevos seleccionados
        foreach ($this->predefinidosSeleccionados as $codigo) {
            $existe = $this->carta->kpis()->where('codigo', $codigo)->exists();

            if (!$existe) {
                // Buscar la configuración del KPI
                foreach ($this->kpisPredefinidosConfig as $categoriaKey => $categoria) {
                    if (isset($categoria['kpis'][$codigo])) {
                        $config = $categoria['kpis'][$codigo];

                        $this->carta->kpis()->create([
                            'codigo' => $codigo,
                            'nombre' => $config['nombre'],
                            'descripcion' => $config['descripcion'],
                            'unidad_medida' => $config['unidad'],
                            'tipo_umbral' => $config['tipo_umbral'],
                            'tipo' => 'predefinido',
                            'activo' => true,
                            'categoria' => $this->mapearCategoriaPredefinido($categoriaKey),
                            'creado_por' => auth()->id(),
                        ]);
                        break;
                    }
                }
            }
        }

        // Recalcular valores
        $this->recalcularKpisPredefinidos();

        $this->showSeleccionarModal = false;
    }

    private function mapearCategoriaPredefinido(string $categoriaKey): string
    {
        return match($categoriaKey) {
            'financieros' => 'economico',
            'tiempo' => 'otro',
            'progreso' => 'productivo',
            'riesgo' => 'otro',
            default => 'otro',
        };
    }

    // ==================== MODAL CREAR/EDITAR KPI ====================

    public function abrirCrearModal(): void
    {
        $this->resetFormularioKpi();
        $this->showCrearModal = true;
    }

    public function editarKpi(int $kpiId): void
    {
        $kpi = Kpi::findOrFail($kpiId);

        $this->editandoKpiId = $kpi->id;
        $this->nombre = $kpi->nombre;
        $this->descripcion = $kpi->descripcion ?? '';
        $this->categoria = $kpi->categoria ?? 'otro';
        $this->producto_id = $kpi->producto_id;
        $this->actividad_id = $kpi->actividad_id;
        $this->unidad_medida = $kpi->unidad_medida ?? '';
        $this->meta = $kpi->meta;
        $this->linea_base = $kpi->linea_base;
        $this->frecuencia = $kpi->frecuencia ?? 'mensual';
        $this->fuente_verificacion = $kpi->fuente_verificacion ?? '';
        $this->tipo_umbral = $kpi->tipo_umbral ?? 'mayor_mejor';
        $this->umbral_min = $kpi->umbral_min;
        $this->umbral_max = $kpi->umbral_max;

        if ($this->producto_id) {
            $this->actividades = Actividad::where('producto_id', $this->producto_id)->get();
        }

        $this->showCrearModal = true;
    }

    public function guardarKpiPersonalizado(): void
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string',
            'frecuencia' => 'required|string',
        ]);

        $data = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria' => $this->categoria,
            'producto_id' => $this->producto_id ?: null,
            'actividad_id' => $this->actividad_id ?: null,
            'unidad_medida' => $this->unidad_medida,
            'meta' => $this->meta,
            'linea_base' => $this->linea_base,
            'frecuencia' => $this->frecuencia,
            'fuente_verificacion' => $this->fuente_verificacion,
            'tipo_umbral' => $this->tipo_umbral,
            'umbral_min' => $this->umbral_min,
            'umbral_max' => $this->umbral_max,
            'tipo' => 'personalizado',
            'activo' => true,
        ];

        if ($this->editandoKpiId) {
            $kpi = Kpi::findOrFail($this->editandoKpiId);
            $kpi->update($data);
        } else {
            $data['codigo'] = Str::slug($this->nombre) . '-' . now()->timestamp;
            $data['carta_id'] = $this->carta->id;
            $data['proxima_medicion'] = now()->addDays($this->getDiasFrecuencia($this->frecuencia));
            $data['creado_por'] = auth()->id();
            Kpi::create($data);
        }

        $this->resetFormularioKpi();
        $this->showCrearModal = false;
    }

    public function eliminarKpi(int $kpiId): void
    {
        $kpi = Kpi::findOrFail($kpiId);
        $kpi->valores()->delete();
        $kpi->delete();
    }

    private function resetFormularioKpi(): void
    {
        $this->editandoKpiId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->categoria = 'social';
        $this->producto_id = null;
        $this->actividad_id = null;
        $this->unidad_medida = '';
        $this->meta = null;
        $this->linea_base = null;
        $this->frecuencia = 'mensual';
        $this->fuente_verificacion = '';
        $this->tipo_umbral = 'mayor_mejor';
        $this->umbral_min = null;
        $this->umbral_max = null;
        $this->actividades = [];
    }

    // ==================== MODAL REGISTRAR VALOR ====================

    public function abrirRegistrarValor(int $kpiId): void
    {
        $this->kpiSeleccionado = Kpi::with('valores')->findOrFail($kpiId);
        $this->nuevo_valor = null;
        $this->valor_observaciones = '';
        $this->valor_fecha = now()->format('Y-m-d');
        $this->showRegistrarValorModal = true;
    }

    public function guardarValor(): void
    {
        $this->validate([
            'nuevo_valor' => 'required|numeric',
            'valor_fecha' => 'required|date',
        ]);

        $ultimoValor = $this->kpiSeleccionado->valores()->latest()->first();
        $valorAnterior = $ultimoValor ? $ultimoValor->valor : $this->kpiSeleccionado->linea_base;

        // Calcular tendencia
        $tendencia = 'estable';
        $cambio_porcentual = null;

        if ($valorAnterior && $valorAnterior != 0) {
            $diferencia = $this->nuevo_valor - $valorAnterior;
            $cambio_porcentual = round(($diferencia / $valorAnterior) * 100, 2);

            if ($diferencia > 0) $tendencia = 'subiendo';
            elseif ($diferencia < 0) $tendencia = 'bajando';
        }

        // Detectar alerta
        $alerta = null;
        $tipo_umbral = $this->kpiSeleccionado->tipo_umbral;
        $umbral_min = $this->kpiSeleccionado->umbral_min;
        $umbral_max = $this->kpiSeleccionado->umbral_max;

        if ($tipo_umbral === 'mayor_mejor' && $umbral_min !== null) {
            if ($this->nuevo_valor < $umbral_min) {
                $alerta = $this->nuevo_valor < ($umbral_min * 0.8) ? 'critica' : 'advertencia';
            }
        } elseif ($tipo_umbral === 'menor_mejor' && $umbral_max !== null) {
            if ($this->nuevo_valor > $umbral_max) {
                $alerta = $this->nuevo_valor > ($umbral_max * 1.2) ? 'critica' : 'advertencia';
            }
        } elseif ($tipo_umbral === 'rango') {
            if (($umbral_min !== null && $this->nuevo_valor < $umbral_min) ||
                ($umbral_max !== null && $this->nuevo_valor > $umbral_max)) {
                $alerta = 'advertencia';
            }
        }

        // Crear registro
        $this->kpiSeleccionado->valores()->create([
            'valor' => $this->nuevo_valor,
            'fecha_calculo' => $this->valor_fecha,
            'notas' => $this->valor_observaciones,
            'tendencia' => $tendencia,
            'porcentaje_cambio' => $cambio_porcentual,
            'en_alerta' => $alerta !== null,
            'tipo_alerta' => $alerta ?? 'normal',
            'calculado_por' => auth()->id(),
        ]);

        // Actualizar KPI
        $this->kpiSeleccionado->update([
            'valor_actual' => $this->nuevo_valor,
            'tendencia' => $tendencia,
            'ultima_medicion' => $this->valor_fecha,
            'proxima_medicion' => now()->addDays($this->getDiasFrecuencia($this->kpiSeleccionado->frecuencia)),
        ]);

        $this->showRegistrarValorModal = false;
    }

    // ==================== MODAL HISTORIAL ====================

    public function abrirHistorial(int $kpiId): void
    {
        $this->kpiHistorial = Kpi::with(['valores' => function($q) {
            $q->orderBy('fecha_calculo', 'desc')->limit(20);
        }])->findOrFail($kpiId);
        $this->showHistorialModal = true;
    }

    // ==================== UTILIDADES ====================

    public function cerrarModales(): void
    {
        $this->showSeleccionarModal = false;
        $this->showCrearModal = false;
        $this->showRegistrarValorModal = false;
        $this->showHistorialModal = false;
    }

    private function getDiasFrecuencia(?string $frecuencia): int
    {
        return match($frecuencia) {
            'diario' => 1,
            'semanal' => 7,
            'quincenal' => 15,
            'mensual' => 30,
            'trimestral' => 90,
            'semestral' => 180,
            'anual' => 365,
            default => 30,
        };
    }

    public function recalcularKpisPredefinidos(): void
    {
        $kpisPredefinidos = $this->carta->kpis()->where('tipo', 'predefinido')->get();

        foreach ($kpisPredefinidos as $kpi) {
            $valor = $this->calcularValorPredefinido($kpi->codigo);
            $kpi->update([
                'valor_actual' => $valor,
                'ultima_medicion' => now(),
            ]);
        }
    }

    private function calcularValorPredefinido(string $clave): ?float
    {
        $presupuestoTotal = $this->carta->productos->sum(fn($p) => $p->actividades->sum('monto_presupuestado'));
        $gastoTotal = $this->carta->productos->sum(fn($p) => $p->actividades->sum('monto_ejecutado'));
        $actividades = $this->carta->productos->flatMap->actividades;
        $totalActividades = $actividades->count();
        $completadas = $actividades->where('estado', 'completada')->count();

        return match($clave) {
            'ejecucion_presupuestaria' => $presupuestoTotal > 0 ? round(($gastoTotal / $presupuestoTotal) * 100, 2) : 0,
            'progreso_general' => $totalActividades > 0 ? round($actividades->avg('progreso'), 2) : 0,
            'actividades_completadas' => $completadas,
            'actividades_atrasadas' => $actividades->filter(fn($a) => $a->fecha_fin < now() && $a->estado !== 'completada')->count(),
            'dias_restantes' => max(0, now()->diffInDays($this->carta->fecha_fin, false)),
            'cpi' => $gastoTotal > 0 ? round(($presupuestoTotal * ($actividades->avg('progreso') / 100)) / $gastoTotal, 2) : 1,
            'spi' => $this->calcularSPI(),
            'burn_rate' => $this->calcularBurnRate($gastoTotal),
            'variacion_costos' => $gastoTotal - ($presupuestoTotal * ($actividades->avg('progreso') / 100)),
            'avance_temporal' => $this->calcularAvanceTemporal(),
            'eficiencia' => $this->calcularEficiencia($actividades, $presupuestoTotal, $gastoTotal),
            'indice_riesgo' => $this->calcularIndiceRiesgo($actividades),
            default => null,
        };
    }

    private function calcularSPI(): float
    {
        $diasTotales = $this->carta->fecha_inicio->diffInDays($this->carta->fecha_fin);
        $diasTranscurridos = $this->carta->fecha_inicio->diffInDays(now());
        $avancePlanificado = $diasTotales > 0 ? ($diasTranscurridos / $diasTotales) * 100 : 0;
        $progresoReal = $this->carta->productos->flatMap->actividades->avg('progreso') ?? 0;

        return $avancePlanificado > 0 ? round($progresoReal / $avancePlanificado, 2) : 1;
    }

    private function calcularBurnRate($gastoTotal): float
    {
        $diasTranscurridos = max(1, $this->carta->fecha_inicio->diffInDays(now()));
        return round($gastoTotal / $diasTranscurridos, 2);
    }

    private function calcularAvanceTemporal(): float
    {
        $diasTotales = $this->carta->fecha_inicio->diffInDays($this->carta->fecha_fin);
        $diasTranscurridos = $this->carta->fecha_inicio->diffInDays(now());
        return $diasTotales > 0 ? round(($diasTranscurridos / $diasTotales) * 100, 2) : 0;
    }

    private function calcularEficiencia($actividades, $presupuestoTotal, $gastoTotal): float
    {
        $progreso = $actividades->avg('progreso') ?? 0;
        $ejecucion = $presupuestoTotal > 0 ? ($gastoTotal / $presupuestoTotal) * 100 : 0;
        return $ejecucion > 0 ? round(($progreso / $ejecucion) * 100, 2) : 100;
    }

    private function calcularIndiceRiesgo($actividades): float
    {
        $atrasadas = $actividades->filter(fn($a) => $a->fecha_fin < now() && $a->estado !== 'completada')->count();
        $total = $actividades->count();
        return $total > 0 ? round(($atrasadas / $total) * 100, 2) : 0;
    }

    public function with(): array
    {
        return [
            'kpisPredefinidos' => $this->carta->kpis()->where('tipo', 'predefinido')->where('activo', true)->get(),
            'kpisPersonalizados' => $this->carta->kpis()->where('tipo', 'personalizado')->where('activo', true)->get(),
            'totalKpis' => $this->carta->kpis()->where('activo', true)->count(),
            'kpisConAlerta' => $this->carta->kpis()->where('activo', true)->whereHas('valores', fn($q) => $q->where('en_alerta', true))->count(),
            'kpisRequierenMedicion' => $this->carta->kpis()->where('activo', true)->where('proxima_medicion', '<=', now())->count(),
        ];
    }
}; ?>

<div class="space-y-6">

    {{-- ==================== HEADER ==================== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">📊 KPIs de la Carta</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $carta->codigo }} - {{ $carta->nombre }}</p>
        </div>

        @if($totalKpis > 0)
            <div class="flex items-center gap-3">
                <button wire:click="abrirSeleccionarModal"
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Predefinidos
                </button>
                <button wire:click="abrirCrearModal"
                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 shadow-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crear KPI
                </button>
            </div>
        @endif
    </div>

    {{-- ==================== ESTADO VACÍO ==================== --}}
    @if($totalKpis === 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-12 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">No hay KPIs configurados</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-8">
                    Configura indicadores para monitorear el desempeño de tu proyecto. Puedes elegir KPIs predefinidos o crear indicadores personalizados.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button wire:click="abrirSeleccionarModal"
                            class="px-6 py-3 bg-white dark:bg-slate-700 border-2 border-blue-500 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-50 dark:hover:bg-slate-600 transition flex items-center justify-center gap-2 font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Seleccionar Predefinidos
                    </button>

                    <button wire:click="abrirCrearModal"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 shadow-lg transition flex items-center justify-center gap-2 font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crear KPI Personalizado
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- ==================== RESUMEN GENERAL ==================== --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $totalKpis }} KPIs activos</span>
                </div>
                @if($kpisConAlerta > 0)
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                        <span class="text-red-600 dark:text-red-400 font-medium">{{ $kpisConAlerta }} con alertas</span>
                    </div>
                @endif
                @if($kpisRequierenMedicion > 0)
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">{{ $kpisRequierenMedicion }} requieren medición</span>
                    </div>
                @endif
                <button wire:click="recalcularKpisPredefinidos" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Recalcular
                </button>
            </div>
        </div>

        {{-- ==================== KPIs PREDEFINIDOS ==================== --}}
        @if($kpisPredefinidos->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    KPIs Predefinidos
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($kpisPredefinidos as $kpi)
                        @php
                            $colorClases = match($kpi->categoria) {
                                'financieros' => 'from-green-500 to-emerald-600',
                                'tiempo' => 'from-blue-500 to-cyan-600',
                                'progreso' => 'from-purple-500 to-indigo-600',
                                'riesgo' => 'from-red-500 to-orange-600',
                                default => 'from-gray-500 to-slate-600',
                            };
                            $bgClases = match($kpi->categoria) {
                                'financieros' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                                'tiempo' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                                'progreso' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
                                'riesgo' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                                default => 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800',
                            };
                        @endphp

                        <div class="relative group">
                            @php
                                $tieneAlerta = $kpi->valores()->where('en_alerta', true)->exists();
                            @endphp
                            <div class="{{ $bgClases }} border {{ $tieneAlerta ? 'ring-2 ring-red-400 border-red-300' : '' }} rounded-xl p-4 hover:shadow-md transition cursor-pointer relative"
                                 wire:click="abrirHistorial({{ $kpi->id }})">

                                @if($tieneAlerta)
                                    <div class="absolute -top-1.5 -right-1.5">
                                        <span class="flex h-4 w-4">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 items-center justify-center text-white text-xs">!</span>
                                        </span>
                                    </div>
                                @endif

                                {{-- Valor principal --}}
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 truncate" title="{{ $kpi->nombre }}">
                                        {{ Str::limit($kpi->nombre, 18) }}
                                    </p>
                                    <p class="text-2xl font-bold bg-gradient-to-r {{ $colorClases }} bg-clip-text text-transparent">
                                        {{ number_format($kpi->valor_actual ?? 0, $kpi->unidad_medida === '%' ? 1 : 0) }}
                                        <span class="text-sm">{{ $kpi->unidad_medida }}</span>
                                    </p>

                                    {{-- Tendencia --}}
                                    @if($kpi->tendencia)
                                        <span class="text-xs {{ $kpi->tendencia === 'subiendo' ? 'text-green-600' : ($kpi->tendencia === 'bajando' ? 'text-red-600' : 'text-gray-500') }}">
                                            {{ $kpi->tendencia === 'subiendo' ? '↑' : ($kpi->tendencia === 'bajando' ? '↓' : '→') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ==================== KPIs PERSONALIZADOS ==================== --}}
        @if($kpisPersonalizados->count() > 0)
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    KPIs Personalizados
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($kpisPersonalizados as $kpi)
                        @php
                            $tieneAlerta = $kpi->valores()->where('en_alerta', true)->exists();
                            $categoriaEmoji = match($kpi->categoria) {
                                'social' => '🧑‍🤝‍🧑',
                                'productivo' => '🌾',
                                'ambiental' => '🌳',
                                'economico' => '💰',
                                'infraestructura' => '🏗️',
                                'capacitacion' => '📚',
                                'calidad' => '⭐',
                                default => '📊',
                            };
                            $requiereMedicion = $kpi->proxima_medicion && $kpi->proxima_medicion <= now();
                            $porcentajeMeta = $kpi->meta > 0 ? min(100, ($kpi->valor_actual / $kpi->meta) * 100) : 0;
                        @endphp

                        <div class="relative border {{ $tieneAlerta ? 'ring-2 ring-red-400 border-red-300 dark:border-red-600' : ($requiereMedicion ? 'border-yellow-300 dark:border-yellow-700 bg-yellow-50/50 dark:bg-yellow-900/10' : 'border-gray-200 dark:border-slate-700') }} rounded-xl p-4 hover:shadow-md transition">

                            {{-- Badge de alerta --}}
                            @if($tieneAlerta)
                                <div class="absolute -top-1.5 -left-1.5">
                                    <span class="flex h-5 w-5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 items-center justify-center text-white text-xs font-bold">!</span>
                                    </span>
                                </div>
                            @endif

                            {{-- Badge requiere medición --}}
                            @if($requiereMedicion)
                                <div class="absolute -top-2 -right-2">
                                    <span class="px-2 py-0.5 bg-red-500 text-white text-xs rounded-full animate-pulse">
                                        Medir
                                    </span>
                                </div>
                            @endif

                            {{-- Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white truncate" title="{{ $kpi->nombre }}">
                                        {{ $categoriaEmoji }} {{ $kpi->nombre }}
                                    </p>
                                    @if($kpi->producto || $kpi->actividad)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            📍 {{ $kpi->actividad ? $kpi->actividad->nombre : $kpi->producto->nombre }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Menú acciones --}}
                                <div class="flex items-center gap-1">
                                    <button wire:click="editarKpi({{ $kpi->id }})" class="p-1 text-gray-400 hover:text-blue-600" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="eliminarKpi({{ $kpi->id }})" wire:confirm="¿Eliminar este KPI?" class="p-1 text-gray-400 hover:text-red-600" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Valor actual y meta --}}
                            <div class="text-center py-3 border-y border-gray-100 dark:border-slate-700 mb-3">
                                <p class="text-3xl font-bold text-gray-800 dark:text-white">
                                    {{ number_format($kpi->valor_actual ?? 0, 1) }}
                                    <span class="text-sm font-normal text-gray-500">{{ $kpi->unidad_medida }}</span>
                                </p>
                                @if($kpi->meta)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Meta: {{ number_format($kpi->meta, 1) }} {{ $kpi->unidad_medida }}
                                    </p>
                                    {{-- Barra de progreso --}}
                                    <div class="mt-2 w-full bg-gray-200 dark:bg-slate-600 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all"
                                             style="width: {{ $porcentajeMeta }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ number_format($porcentajeMeta, 0) }}% de la meta</p>
                                @endif
                            </div>

                            {{-- Info adicional --}}
                            <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                                <div>
                                    <span class="block text-gray-400">Última</span>
                                    {{ $kpi->ultima_medicion ? $kpi->ultima_medicion->format('d/m/Y') : 'Sin datos' }}
                                </div>
                                <div>
                                    <span class="block text-gray-400">Próxima</span>
                                    @if($kpi->proxima_medicion)
                                        <span class="{{ $requiereMedicion ? 'text-red-600 font-semibold' : '' }}">
                                            {{ $kpi->proxima_medicion->format('d/m/Y') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>

                            {{-- Botones de acción --}}
                            <div class="flex gap-2">
                                <button wire:click="abrirRegistrarValor({{ $kpi->id }})"
                                        class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                                    + Registrar
                                </button>
                                <button wire:click="abrirHistorial({{ $kpi->id }})"
                                        class="px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300 text-xs font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition">
                                    📈
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- ==================== MODAL SELECCIONAR PREDEFINIDOS ==================== --}}
    @if($showSeleccionarModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
             wire:click.self="cerrarModales">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">📋 Seleccionar KPIs Predefinidos</h3>
                        <button wire:click="cerrarModales" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Seleccionar todos --}}
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   wire:click="toggleTodos"
                                   {{ count($predefinidosSeleccionados) === 13 ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Seleccionar todos</span>
                        </label>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ count($predefinidosSeleccionados) }} seleccionados
                        </span>
                    </div>

                    {{-- Categorías --}}
                    @foreach($kpisPredefinidosConfig as $categoriaKey => $categoria)
                        <div>
                            <h4 class="font-bold text-gray-800 dark:text-white mb-3">{{ $categoria['titulo'] }}</h4>
                            <div class="space-y-2">
                                @foreach($categoria['kpis'] as $clave => $kpi)
                                    <label class="flex items-start gap-3 p-3 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition">
                                        <input type="checkbox"
                                               wire:model="predefinidosSeleccionados"
                                               value="{{ $clave }}"
                                               class="mt-1 w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800 dark:text-white">{{ $kpi['nombre'] }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $kpi['descripcion'] }}</p>
                                        </div>
                                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-slate-600 px-2 py-1 rounded">
                                            {{ $kpi['unidad'] }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                        <button wire:click="cerrarModales"
                                class="px-4 py-2.5 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                            Cancelar
                        </button>
                        <button wire:click="guardarSeleccionPredefinidos"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 shadow-lg font-medium">
                            Guardar Selección ({{ count($predefinidosSeleccionados) }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ==================== MODAL CREAR/EDITAR KPI ==================== --}}
    @if($showCrearModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
             wire:click.self="cerrarModales">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $editandoKpiId ? 'Editar KPI' : '✨ Crear KPI Personalizado' }}
                        </h3>
                        <button wire:click="cerrarModales" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form wire:submit="guardarKpiPersonalizado" class="p-6 space-y-5">

                    {{-- Nombre --}}
                    <div x-data="{ showHelp: false }" class="relative">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre del Indicador <span class="text-red-600">*</span>
                            <button type="button" @click="showHelp = !showHelp"
                                    class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                ?
                            </button>
                        </label>
                        <div x-show="showHelp" x-transition @click.away="showHelp = false"
                             class="absolute z-10 top-0 left-48 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                            <p class="font-semibold mb-1">💡 Nombre del Indicador</p>
                            <p>Un nombre claro y descriptivo que identifique qué estás midiendo.</p>
                            <p class="mt-2 text-gray-300">Ejemplos: "Participación de mujeres", "Hectáreas reforestadas", "Familias beneficiadas"</p>
                            <div class="absolute -left-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-r-8 border-r-gray-900"></div>
                        </div>
                        <input type="text" wire:model="nombre"
                               placeholder="Ej: Participación de mujeres en talleres"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                        @error('nombre') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Descripción --}}
                    <div x-data="{ showHelp: false }" class="relative">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Descripción
                            <button type="button" @click="showHelp = !showHelp"
                                    class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                ?
                            </button>
                        </label>
                        <div x-show="showHelp" x-transition @click.away="showHelp = false"
                             class="absolute z-10 top-0 left-32 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                            <p class="font-semibold mb-1">💡 Descripción</p>
                            <p>Explica qué mide este indicador, cómo se obtiene el dato y por qué es importante.</p>
                            <div class="absolute -left-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-r-8 border-r-gray-900"></div>
                        </div>
                        <textarea wire:model="descripcion" rows="2"
                                  placeholder="¿Qué mide este indicador y cómo se obtiene el dato?"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"></textarea>
                    </div>

                    {{-- Categoría y Frecuencia --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div x-data="{ showHelp: false }" class="relative">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Categoría <span class="text-red-600">*</span>
                                <button type="button" @click="showHelp = !showHelp"
                                        class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                    ?
                                </button>
                            </label>
                            <div x-show="showHelp" x-transition @click.away="showHelp = false"
                                 class="absolute z-10 top-8 left-0 w-72 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                                <p class="font-semibold mb-2">💡 Categorías disponibles</p>
                                <ul class="space-y-1 text-gray-300">
                                    <li>🧑‍🤝‍🧑 <strong>Social:</strong> Género, participación, beneficiarios</li>
                                    <li>🌾 <strong>Productivo:</strong> Cosechas, rendimientos, producción</li>
                                    <li>🌳 <strong>Ambiental:</strong> Reforestación, agua, conservación</li>
                                    <li>💰 <strong>Económico:</strong> Ingresos, empleos, ventas</li>
                                    <li>🏗️ <strong>Infraestructura:</strong> Riego, caminos, construcciones</li>
                                    <li>📚 <strong>Capacitación:</strong> Talleres, asistentes, aprobación</li>
                                    <li>⭐ <strong>Calidad:</strong> Satisfacción, adopción de prácticas</li>
                                    <li>📊 <strong>Otro:</strong> Indicadores que no encajan arriba</li>
                                </ul>
                            </div>
                            <select wire:model="categoria"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                <option value="social">🧑‍🤝‍🧑 Social</option>
                                <option value="productivo">🌾 Productivo</option>
                                <option value="ambiental">🌳 Ambiental</option>
                                <option value="economico">💰 Económico</option>
                                <option value="infraestructura">🏗️ Infraestructura</option>
                                <option value="capacitacion">📚 Capacitación</option>
                                <option value="calidad">⭐ Calidad</option>
                                <option value="otro">📊 Otro</option>
                            </select>
                        </div>

                        <div x-data="{ showHelp: false }" class="relative">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Frecuencia <span class="text-red-600">*</span>
                                <button type="button" @click="showHelp = !showHelp"
                                        class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                    ?
                                </button>
                            </label>
                            <div x-show="showHelp" x-transition @click.away="showHelp = false"
                                 class="absolute z-10 top-0 right-0 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                                <p class="font-semibold mb-1">💡 Frecuencia de Medición</p>
                                <p>¿Cada cuánto tiempo debes registrar un nuevo valor?</p>
                                <p class="mt-2 text-gray-300">El sistema te alertará cuando sea momento de una nueva medición.</p>
                                <div class="absolute -right-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-l-8 border-l-gray-900"></div>
                            </div>
                            <select wire:model="frecuencia"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                <option value="unico">📌 Única vez</option>
                                <option value="diario">📅 Diario</option>
                                <option value="semanal">📆 Semanal</option>
                                <option value="quincenal">🗓️ Quincenal</option>
                                <option value="mensual">📊 Mensual</option>
                                <option value="trimestral">📈 Trimestral</option>
                                <option value="semestral">📉 Semestral</option>
                                <option value="anual">🗂️ Anual</option>
                            </select>
                        </div>
                    </div>

                    {{-- Unidad, Línea Base y Meta --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div x-data="{ showHelp: false }" class="relative">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Unidad
                                <button type="button" @click="showHelp = !showHelp"
                                        class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                    ?
                                </button>
                            </label>
                            <div x-show="showHelp" x-transition @click.away="showHelp = false"
                                 class="absolute z-10 top-0 left-20 w-56 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                                <p class="font-semibold mb-1">💡 Unidad de Medida</p>
                                <p>En qué unidad se expresa el valor del indicador.</p>
                                <div class="absolute -left-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-r-8 border-r-gray-900"></div>
                            </div>
                            <select wire:model="unidad_medida"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                <option value="">-- Seleccionar --</option>
                                <optgroup label="Porcentajes">
                                    <option value="%">% (Porcentaje)</option>
                                </optgroup>
                                <optgroup label="Cantidades">
                                    <option value="unidades">Unidades</option>
                                    <option value="personas">Personas</option>
                                    <option value="familias">Familias</option>
                                    <option value="comunidades">Comunidades</option>
                                </optgroup>
                                <optgroup label="Superficie">
                                    <option value="ha">ha (Hectáreas)</option>
                                    <option value="m²">m² (Metros cuadrados)</option>
                                    <option value="km²">km² (Kilómetros cuadrados)</option>
                                </optgroup>
                                <optgroup label="Peso">
                                    <option value="kg">kg (Kilogramos)</option>
                                    <option value="ton">ton (Toneladas)</option>
                                    <option value="qq">qq (Quintales)</option>
                                    <option value="lb">lb (Libras)</option>
                                </optgroup>
                                <optgroup label="Volumen">
                                    <option value="lt">lt (Litros)</option>
                                    <option value="m³">m³ (Metros cúbicos)</option>
                                    <option value="gl">gl (Galones)</option>
                                </optgroup>
                                <optgroup label="Dinero">
                                    <option value="$">$ (Dólares)</option>
                                    <option value="Bs">Bs (Bolivianos)</option>
                                    <option value="USD">USD</option>
                                </optgroup>
                                <optgroup label="Tiempo">
                                    <option value="días">Días</option>
                                    <option value="horas">Horas</option>
                                    <option value="meses">Meses</option>
                                </optgroup>
                                <optgroup label="Rendimiento">
                                    <option value="kg/ha">kg/ha</option>
                                    <option value="ton/ha">ton/ha</option>
                                    <option value="qq/ha">qq/ha</option>
                                </optgroup>
                                <optgroup label="Otros">
                                    <option value="árboles">Árboles</option>
                                    <option value="talleres">Talleres</option>
                                    <option value="eventos">Eventos</option>
                                    <option value="puntos">Puntos (1-10)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div x-data="{ showHelp: false }" class="relative">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Línea Base
                                <button type="button" @click="showHelp = !showHelp"
                                        class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                    ?
                                </button>
                            </label>
                            <div x-show="showHelp" x-transition @click.away="showHelp = false"
                                 class="absolute z-10 top-0 left-24 w-56 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                                <p class="font-semibold mb-1">💡 Línea Base</p>
                                <p>Valor inicial o punto de partida antes de la intervención.</p>
                                <p class="mt-2 text-gray-300">Ejemplo: Si antes había 20% de mujeres, la línea base es 20.</p>
                                <div class="absolute -left-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-r-8 border-r-gray-900"></div>
                            </div>
                            <input type="number" wire:model="linea_base" step="0.01"
                                   placeholder="Valor inicial"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                        </div>

                        <div x-data="{ showHelp: false }" class="relative">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Meta
                                <button type="button" @click="showHelp = !showHelp"
                                        class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                    ?
                                </button>
                            </label>
                            <div x-show="showHelp" x-transition @click.away="showHelp = false"
                                 class="absolute z-10 top-0 right-0 w-56 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                                <p class="font-semibold mb-1">💡 Meta Objetivo</p>
                                <p>Valor que esperas alcanzar al final del proyecto.</p>
                                <p class="mt-2 text-gray-300">El sistema mostrará tu progreso hacia esta meta.</p>
                                <div class="absolute -right-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-l-8 border-l-gray-900"></div>
                            </div>
                            <input type="number" wire:model="meta" step="0.01"
                                   placeholder="Valor a alcanzar"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                        </div>
                    </div>

                    {{-- Asociación a Producto/Actividad --}}
                    <div x-data="{ showHelp: false }" class="relative bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                📍 Asociar a Producto/Actividad (opcional)
                            </h4>
                            <button type="button" @click="showHelp = !showHelp"
                                    class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                ?
                            </button>
                        </div>
                        <div x-show="showHelp" x-transition @click.away="showHelp = false"
                             class="absolute z-10 top-0 right-4 w-72 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                            <p class="font-semibold mb-1">💡 Asociación</p>
                            <p>Puedes vincular este KPI a un producto o actividad específica de la carta.</p>
                            <ul class="mt-2 space-y-1 text-gray-300">
                                <li>• <strong>Sin asociar:</strong> KPI general de la carta</li>
                                <li>• <strong>Solo producto:</strong> KPI del producto</li>
                                <li>• <strong>Producto + Actividad:</strong> KPI específico de una actividad</li>
                            </ul>
                            <div class="absolute -right-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-l-8 border-l-gray-900"></div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            {{-- Carta (solo lectura) --}}
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">📋 Carta</label>
                                <div class="w-full px-3 py-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg text-sm text-blue-800 dark:text-blue-200 font-medium truncate" title="{{ $carta->nombre }}">
                                    {{ $carta->codigo ?? 'CARTA' }} - {{ Str::limit($carta->nombre, 15) }}
                                </div>
                            </div>

                            {{-- Producto --}}
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Producto</label>
                                <select wire:model.live="producto_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-600 dark:text-white text-sm">
                                    <option value="">-- Todos --</option>
                                    @foreach($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ Str::limit($producto->nombre, 20) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Actividad --}}
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Actividad</label>
                                <select wire:model="actividad_id"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-600 dark:text-white text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    {{ !$producto_id ? 'disabled' : '' }}>
                                    <option value="">-- Todas --</option>
                                    @foreach($actividades as $actividad)
                                        <option value="{{ $actividad->id }}">{{ Str::limit($actividad->nombre, 20) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Indicador visual de asociación --}}
                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Este KPI se asociará a:</span>
                            @if($actividad_id)
                                <span class="text-purple-600 dark:text-purple-400 font-semibold">Actividad específica</span>
                            @elseif($producto_id)
                                <span class="text-blue-600 dark:text-blue-400 font-semibold">Producto seleccionado</span>
                            @else
                                <span class="text-green-600 dark:text-green-400 font-semibold">Carta general ({{ $carta->codigo }})</span>
                            @endif
                        </div>
                    </div>

                    {{-- Umbrales de Alerta --}}
                    <div x-data="{ showHelp: false }" class="relative bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                ⚠️ Configuración de Alertas (opcional)
                            </h4>
                            <button type="button" @click="showHelp = !showHelp"
                                    class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                ?
                            </button>
                        </div>
                        <div x-show="showHelp" x-transition @click.away="showHelp = false"
                             class="absolute z-10 top-0 right-4 w-80 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                            <p class="font-semibold mb-1">💡 Alertas por Umbral</p>
                            <p>Define cuándo el sistema debe mostrarte una alerta:</p>
                            <ul class="mt-2 space-y-1 text-gray-300">
                                <li>• <strong>Mayor es mejor:</strong> Alerta si el valor baja del mínimo (ej: participación)</li>
                                <li>• <strong>Menor es mejor:</strong> Alerta si el valor sube del máximo (ej: costos, retrasos)</li>
                                <li>• <strong>Rango:</strong> Alerta si sale del rango definido</li>
                            </ul>
                            <div class="absolute -right-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-l-8 border-l-gray-900"></div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Tipo de Umbral</label>
                                <select wire:model="tipo_umbral"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-600 dark:text-white text-sm">
                                    <option value="mayor_mejor">↑ Mayor es mejor</option>
                                    <option value="menor_mejor">↓ Menor es mejor</option>
                                    <option value="rango">↔ Rango específico</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Umbral Mínimo</label>
                                <input type="number" wire:model="umbral_min" step="0.01"
                                       placeholder="Mín."
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-600 dark:text-white text-sm">
                            </div>

                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Umbral Máximo</label>
                                <input type="number" wire:model="umbral_max" step="0.01"
                                       placeholder="Máx."
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-600 dark:text-white text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Fuente de Verificación --}}
                    <div x-data="{ showHelp: false }" class="relative">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Fuente de Verificación
                            <button type="button" @click="showHelp = !showHelp"
                                    class="w-5 h-5 bg-gray-200 dark:bg-slate-600 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-blue-500 hover:text-white transition">
                                ?
                            </button>
                        </label>
                        <div x-show="showHelp" x-transition @click.away="showHelp = false"
                             class="absolute z-10 top-0 left-48 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                            <p class="font-semibold mb-1">💡 Fuente de Verificación</p>
                            <p>¿De dónde obtienes los datos para este indicador?</p>
                            <p class="mt-2 text-gray-300">Ejemplos: Listas de asistencia, informes de campo, fotografías, registros de ventas, encuestas.</p>
                            <div class="absolute -left-2 top-3 w-0 h-0 border-t-8 border-t-transparent border-b-8 border-b-transparent border-r-8 border-r-gray-900"></div>
                        </div>
                        <input type="text" wire:model="fuente_verificacion"
                               placeholder="Ej: Listas de asistencia, informes de campo, fotografías..."
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                        <button type="button" wire:click="cerrarModales"
                                class="px-4 py-2.5 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 shadow-lg font-medium">
                            {{ $editandoKpiId ? 'Actualizar' : 'Crear' }} KPI
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ==================== MODAL REGISTRAR VALOR ==================== --}}
    @if($showRegistrarValorModal && $kpiSeleccionado)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
             wire:click.self="cerrarModales">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-md w-full">
                <div class="border-b border-gray-200 dark:border-slate-700 px-6 py-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">📝 Registrar Valor</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $kpiSeleccionado->nombre }}</p>
                </div>

                <form wire:submit="guardarValor" class="p-6 space-y-4">
                    {{-- Info actual --}}
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Actual</p>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                {{ number_format($kpiSeleccionado->valor_actual ?? 0, 1) }}
                            </p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Meta</p>
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($kpiSeleccionado->meta ?? 0, 1) }}
                            </p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Mediciones</p>
                            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                {{ $kpiSeleccionado->valores->count() }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nuevo Valor <span class="text-red-600">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" wire:model="nuevo_valor" step="0.01" required
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white pr-16"
                                   placeholder="0.00">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                {{ $kpiSeleccionado->unidad_medida }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Fecha <span class="text-red-600">*</span>
                        </label>
                        <input type="date" wire:model="valor_fecha" required
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observaciones
                        </label>
                        <textarea wire:model="valor_observaciones" rows="2"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                  placeholder="Notas adicionales..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                        <button type="button" wire:click="cerrarModales"
                                class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ==================== MODAL HISTORIAL ==================== --}}
    @if($showHistorialModal && $kpiHistorial)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
             wire:click.self="cerrarModales">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">📈 Historial de Mediciones</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $kpiHistorial->nombre }}</p>
                        </div>
                        <button wire:click="cerrarModales" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    {{-- Resumen --}}
                    <div class="grid grid-cols-4 gap-3 mb-6">
                        <div class="text-center bg-gray-50 dark:bg-slate-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Línea Base</p>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">{{ number_format($kpiHistorial->linea_base ?? 0, 1) }}</p>
                        </div>
                        <div class="text-center bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Actual</p>
                            <p class="text-lg font-bold text-blue-600">{{ number_format($kpiHistorial->valor_actual ?? 0, 1) }}</p>
                        </div>
                        <div class="text-center bg-green-50 dark:bg-green-900/30 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Meta</p>
                            <p class="text-lg font-bold text-green-600">{{ number_format($kpiHistorial->meta ?? 0, 1) }}</p>
                        </div>
                        <div class="text-center bg-purple-50 dark:bg-purple-900/30 rounded-lg p-3">
                            <p class="text-xs text-gray-500">Mediciones</p>
                            <p class="text-lg font-bold text-purple-600">{{ $kpiHistorial->valores->count() }}</p>
                        </div>
                    </div>

                    {{-- Gráfico simple de barras --}}
                    @if($kpiHistorial->valores->count() > 0)
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Evolución</h4>
                            <div class="flex items-end gap-1 h-32 bg-gray-50 dark:bg-slate-700 rounded-lg p-3">
                                @php
                                    $valores = $kpiHistorial->valores->sortBy('fecha')->take(20);
                                    $maxValor = $valores->max('valor') ?: 1;
                                @endphp
                                @foreach($valores as $valor)
                                    @php
                                        $altura = ($valor->valor / $maxValor) * 100;
                                    @endphp
                                    <div class="flex-1 bg-gradient-to-t from-blue-500 to-purple-500 rounded-t transition-all hover:opacity-80"
                                         style="height: {{ $altura }}%"
                                         title="{{ $valor->fecha_calculo?->format('d/m/Y') }}: {{ number_format($valor->valor, 1) }}"></div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tabla de valores --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="text-left px-3 py-2 font-medium text-gray-700 dark:text-gray-300">Fecha</th>
                                    <th class="text-right px-3 py-2 font-medium text-gray-700 dark:text-gray-300">Valor</th>
                                    <th class="text-right px-3 py-2 font-medium text-gray-700 dark:text-gray-300">Cambio</th>
                                    <th class="text-left px-3 py-2 font-medium text-gray-700 dark:text-gray-300">Observaciones</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-slate-600">
                                @foreach($kpiHistorial->valores->sortByDesc('fecha_calculo') as $valor)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">
                                            {{ $valor->fecha_calculo->format('d/m/Y') }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-white">
                                            {{ number_format($valor->valor, 1) }} {{ $kpiHistorial->unidad_medida }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            @if($valor->porcentaje_cambio)
                                                <span class="{{ $valor->porcentaje_cambio > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $valor->porcentaje_cambio > 0 ? '+' : '' }}{{ number_format($valor->porcentaje_cambio, 1) }}%
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400 truncate max-w-[150px]" title="{{ $valor->notas }}">
                                            {{ $valor->notas ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No hay mediciones registradas aún</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>
