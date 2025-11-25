<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Actividad;
use App\Models\SeguimientoActividad;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public Actividad $actividad;
    public $carta;
    public $producto;
    public $seguimientos;

    // Tabs
    public $activeTab = 'progreso';

    // Datos del formulario
    public $progreso = 0;
    public $monto_gastado = 0;
    public $descripcion_avance = '';
    public $logros = '';
    public $dificultades = '';
    public $proximos_pasos = '';
    public $responsable_nombre = '';
    public $observaciones = '';

    // Fechas
    public $nueva_fecha_inicio = null;
    public $nueva_fecha_fin = null;
    public $proxima_revision = null;

    // Riesgos
    public $nivel_riesgo = 'bajo';
    public $riesgos_identificados = '';
    public $acciones_correctivas = '';

    // Evidencia
    public $archivos_adjuntos = [];
    public $imagenes = [];
    public $etiquetas = [];
    public $nueva_etiqueta = '';

    public function mount(Actividad $actividad): void
    {
        $this->actividad = $actividad->load(['producto.carta', 'responsable']);
        $this->producto = $this->actividad->producto;
        $this->carta = $this->producto->carta;
        $this->seguimientos = $actividad->seguimientos()->with('registradoPor')->get();

        // Pre-cargar valores actuales
        $this->progreso = (float) $actividad->progreso;
        $this->responsable_nombre = $actividad->responsable?->name ?? auth()->user()->name;

        // Pre-cargar fechas
        $this->nueva_fecha_inicio = $actividad->fecha_inicio?->format('Y-m-d');
        $this->nueva_fecha_fin = $actividad->fecha_fin?->format('Y-m-d');
    }

    public function agregarEtiqueta(): void
    {
        if ($this->nueva_etiqueta && !in_array($this->nueva_etiqueta, $this->etiquetas)) {
            $this->etiquetas[] = $this->nueva_etiqueta;
            $this->nueva_etiqueta = '';
        }
    }

    public function eliminarEtiqueta($index): void
    {
        unset($this->etiquetas[$index]);
        $this->etiquetas = array_values($this->etiquetas);
    }

    public function guardarSeguimiento()
    {
        $this->validate([
            'progreso' => 'required|numeric|min:0|max:100',
            'monto_gastado' => 'required|numeric|min:0',
            'descripcion_avance' => 'required|string|min:10',
            'nivel_riesgo' => 'required|in:bajo,medio,alto,critico',
        ]);

        // Verificar que el progreso sea mayor o igual al actual
        if ($this->progreso < $this->actividad->progreso) {
            $this->addError('progreso', 'El progreso no puede ser menor al actual ('.$this->actividad->progreso.'%)');
            return;
        }

        // Procesar archivos adjuntos
        $archivosGuardados = [];
        if ($this->archivos_adjuntos) {
            foreach ($this->archivos_adjuntos as $archivo) {
                $path = $archivo->store('seguimientos/archivos', 'public');
                $archivosGuardados[] = [
                    'nombre' => $archivo->getClientOriginalName(),
                    'path' => $path,
                    'tipo' => $archivo->getClientMimeType(),
                ];
            }
        }

        // Procesar imágenes
        $imagenesGuardadas = [];
        if ($this->imagenes) {
            foreach ($this->imagenes as $imagen) {
                $path = $imagen->store('seguimientos/imagenes', 'public');
                $imagenesGuardadas[] = [
                    'nombre' => $imagen->getClientOriginalName(),
                    'path' => $path,
                ];
            }
        }

        $progresoAnterior = $this->actividad->progreso;
        $gastoAnterior = $this->actividad->gasto_acumulado;
        $estadoAnterior = $this->actividad->estado;

        // Actualizar actividad
        $this->actividad->progreso = $this->progreso;
        $this->actividad->gasto_acumulado += $this->monto_gastado;

        // Actualizar fechas reales
        if ($this->progreso > 0 && !$this->actividad->fecha_inicio_real) {
            $this->actividad->fecha_inicio_real = now();
        }

        if ($this->progreso >= 100) {
            $this->actividad->estado = 'finalizado';
            $this->actividad->fecha_fin_real = now();
        } elseif ($this->progreso > 0) {
            $this->actividad->estado = 'en_curso';
        }

        // Actualizar fechas planificadas si se modificaron
        if ($this->nueva_fecha_inicio) {
            $this->actividad->fecha_inicio = $this->nueva_fecha_inicio;
        }
        if ($this->nueva_fecha_fin) {
            $this->actividad->fecha_fin = $this->nueva_fecha_fin;
        }

        // Verificar atrasos
        if ($this->actividad->esta_atrasado && $this->actividad->estado !== 'finalizado') {
            $this->actividad->estado = 'atrasado';
        }

        $this->actividad->save();

        // Calcular métricas
        $variacionPresupuesto = $this->actividad->gasto_acumulado - $this->actividad->monto;
        $variacionPorcentaje = $this->actividad->monto > 0
            ? round(($variacionPresupuesto / $this->actividad->monto) * 100, 2)
            : 0;

        $indiceEficiencia = $this->progreso > 0
            ? round($this->progreso / (($this->actividad->gasto_acumulado / $this->actividad->monto) * 100), 2)
            : 0;

        // Crear seguimiento
        SeguimientoActividad::create([
            'actividad_id' => $this->actividad->id,
            'progreso_anterior' => $progresoAnterior,
            'progreso_nuevo' => $this->progreso,
            'monto_gastado' => $this->monto_gastado,
            'gasto_acumulado_anterior' => $gastoAnterior,
            'gasto_acumulado_nuevo' => $this->actividad->gasto_acumulado,
            'descripcion_avance' => $this->descripcion_avance,
            'logros' => $this->logros,
            'dificultades' => $this->dificultades,
            'proximos_pasos' => $this->proximos_pasos,
            'nueva_fecha_inicio' => $this->nueva_fecha_inicio,
            'nueva_fecha_fin' => $this->nueva_fecha_fin,
            'proxima_revision' => $this->proxima_revision,
            'responsable_nombre' => $this->responsable_nombre,
            'observaciones' => $this->observaciones,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $this->actividad->estado,
            'excede_presupuesto' => $this->actividad->excede_presupuesto,
            'esta_atrasado' => $this->actividad->esta_atrasado,
            'variacion_presupuesto' => $variacionPresupuesto,
            'variacion_presupuesto_porcentaje' => $variacionPorcentaje,
            'indice_eficiencia' => $indiceEficiencia,
            'nivel_riesgo' => $this->nivel_riesgo,
            'riesgos_identificados' => $this->riesgos_identificados,
            'acciones_correctivas' => $this->acciones_correctivas,
            'archivos_adjuntos' => $archivosGuardados,
            'imagenes' => $imagenesGuardadas,
            'etiquetas' => $this->etiquetas,
            'registrado_por' => auth()->id(),
            'fecha_registro' => now(),
        ]);

        session()->flash('message', 'Seguimiento registrado exitosamente');
        return redirect()->route('cartas.show', $this->carta->id);
    }

    public function volver()
    {
        return redirect()->route('cartas.show', $this->carta->id);
    }
}; ?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('cartas.show', $carta->id) }}"
                       class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        {{ $carta->codigo }}
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        <span
                            class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $producto->nombre }}</span>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                  clip-rule="evenodd"></path>
                        </svg>
                        <span
                            class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $actividad->nombre }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- Panel Principal --}}

        {{-- Resumen de Actividad - Header Mejorado --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-t-xl border-2 border-slate-200 dark:border-slate-700 px-8 py-6 border-b-0">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                                        @if($actividad->estado === 'finalizado') bg-green-500/20 text-green-100 border border-green-400/30
                                        @elseif($actividad->estado === 'en_curso') bg-blue-500/20 text-blue-100 border border-blue-400/30
                                        @elseif($actividad->estado === 'atrasado') bg-red-500/20 text-red-100 border border-red-400/30
                                        @else bg-yellow-500/20 text-yellow-100 border border-yellow-400/30
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $actividad->estado)) }}
                                    </span>
                            @if($actividad->prioridad)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            @if($actividad->prioridad === 'critica') bg-red-500/20 text-red-100
                                            @elseif($actividad->prioridad === 'alta') bg-orange-500/20 text-orange-100
                                            @elseif($actividad->prioridad === 'media') bg-yellow-500/20 text-yellow-100
                                            @else bg-green-500/20 text-green-100
                                            @endif">
                                            🔥 {{ ucfirst($actividad->prioridad) }}
                                        </span>
                            @endif
                        </div>
                        <h1 class="text-3xl font-bold text-white mb-2">{{ $actividad->nombre }}</h1>
                        <p class="text-blue-100 text-lg mb-6 max-w-3xl">{{ $actividad->descripcion }}</p>

                        {{-- Estadísticas Grid --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                                <p class="text-xs text-blue-200 mb-1">Progreso Actual</p>
                                <p class="text-3xl font-bold text-white">{{ number_format($actividad->progreso, 0) }}
                                    %</p>
                                <div class="mt-2 w-full bg-white/20 rounded-full h-2">
                                    <div class="bg-white rounded-full h-2 transition-all"
                                         style="width: {{ $actividad->progreso }}%"></div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                                <p class="text-xs text-blue-200 mb-1">Presupuesto</p>
                                <p class="text-2xl font-bold text-white">${{ number_format($actividad->monto, 2) }}</p>
                                <p class="text-xs text-blue-200 mt-1">Total asignado</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                                <p class="text-xs text-blue-200 mb-1">Ejecutado</p>
                                <p class="text-2xl font-bold text-white">
                                    ${{ number_format($actividad->gasto_acumulado, 2) }}</p>
                                <p class="text-xs text-blue-200 mt-1">{{ number_format($actividad->porcentaje_ejecutado, 1) }}
                                    % del total</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                                <p class="text-xs text-blue-200 mb-1">Saldo</p>
                                <p class="text-2xl font-bold text-white">
                                    ${{ number_format($actividad->saldo_disponible, 2) }}</p>
                                @if($actividad->excede_presupuesto)
                                    <p class="text-xs text-red-200 mt-1">⚠️ Sobre presupuesto</p>
                                @else
                                    <p class="text-xs text-green-200 mt-1">✓ Disponible</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info Adicional --}}
                <div class="flex flex-wrap items-center gap-6 mt-6 pt-6 border-t border-white/20 text-sm text-white">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ $actividad->fecha_inicio->format('d/m/Y') }} - {{ $actividad->fecha_fin->format('d/m/Y') }}</span>
                    </div>
                    @if($actividad->responsable)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>{{ $actividad->responsable->name }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span>{{ $actividad->linea_presupuestaria }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario de Seguimiento --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden">
            <div
                class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 px-8 py-6 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Registrar Nuevo Seguimiento</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Complete la información del avance realizado</p>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                <nav class="flex gap-8 px-8" aria-label="Tabs">
                    <button wire:click="$set('activeTab', 'progreso')"
                            class="group py-5 text-sm font-semibold border-b-2 transition-all relative
                                    {{ $activeTab === 'progreso' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Progreso
                                </span>
                    </button>
                    <button wire:click="$set('activeTab', 'detalles')"
                            class="group py-5 text-sm font-semibold border-b-2 transition-all
                                    {{ $activeTab === 'detalles' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Detalles
                                </span>
                    </button>
                    <button wire:click="$set('activeTab', 'riesgos')"
                            class="group py-5 text-sm font-semibold border-b-2 transition-all
                                    {{ $activeTab === 'riesgos' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Riesgos
                                </span>
                    </button>
                    <button wire:click="$set('activeTab', 'evidencia')"
                            class="group py-5 text-sm font-semibold border-b-2 transition-all
                                    {{ $activeTab === 'evidencia' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    Evidencia
                                </span>
                    </button>
                </nav>
            </div>

            <form wire:submit="guardarSeguimiento">
                <div class="p-8 space-y-8">

                    {{-- Tab: Progreso --}}
                    <div x-show="$wire.activeTab === 'progreso'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Progreso (%) <span class="text-red-600">*</span>
                                </label>
                                <input type="number" wire:model="progreso" step="0.01" min="0" max="100"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                @error('progreso') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Gasto en este seguimiento ($) <span class="text-red-600">*</span>
                                </label>
                                <input type="number" wire:model="monto_gastado" step="0.01" min="0"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                @error('monto_gastado') <span
                                    class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Descripción del Avance <span class="text-red-600">*</span>
                            </label>
                            <textarea wire:model="descripcion_avance" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="Describe brevemente el trabajo realizado en este periodo..."></textarea>
                            @error('descripcion_avance') <span
                                class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Responsable
                            </label>
                            <input type="text" wire:model="responsable_nombre"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                        </div>
                    </div>

                    {{-- Tab: Detalles --}}
                    <div x-show="$wire.activeTab === 'detalles'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Logros Alcanzados
                            </label>
                            <textarea wire:model="logros" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="¿Qué se logró en este periodo?"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Dificultades Encontradas
                            </label>
                            <textarea wire:model="dificultades" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="¿Qué obstáculos se presentaron?"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Próximos Pasos
                            </label>
                            <textarea wire:model="proximos_pasos" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="¿Qué viene después?"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Observaciones Adicionales
                            </label>
                            <textarea wire:model="observaciones" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="Comentarios adicionales..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nueva Fecha Inicio
                                </label>
                                <input type="date" wire:model="nueva_fecha_inicio"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nueva Fecha Fin
                                </label>
                                <input type="date" wire:model="nueva_fecha_fin"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Próxima Revisión
                                </label>
                                <input type="date" wire:model="proxima_revision"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Riesgos --}}
                    <div x-show="$wire.activeTab === 'riesgos'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nivel de Riesgo <span class="text-red-600">*</span>
                            </label>
                            <select wire:model="nivel_riesgo"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                <option value="bajo">Bajo - Sin riesgos significativos</option>
                                <option value="medio">Medio - Requiere atención</option>
                                <option value="alto">Alto - Riesgo considerable</option>
                                <option value="critico">Crítico - Requiere acción inmediata</option>
                            </select>
                            @error('nivel_riesgo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Riesgos Identificados
                            </label>
                            <textarea wire:model="riesgos_identificados" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="Describe los riesgos que pueden afectar el proyecto..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Acciones Correctivas
                            </label>
                            <textarea wire:model="acciones_correctivas" rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                      placeholder="¿Qué acciones se tomarán para mitigar los riesgos?"></textarea>
                        </div>
                    </div>

                    {{-- Tab: Evidencia --}}
                    <div x-show="$wire.activeTab === 'evidencia'" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Archivos Adjuntos
                            </label>
                            <input type="file" wire:model="archivos_adjuntos" multiple
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Documentos, PDFs, Excel, etc.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Imágenes
                            </label>
                            <input type="file" wire:model="imagenes" multiple accept="image/*"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Fotos del progreso, capturas de pantalla, etc.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Etiquetas
                            </label>
                            <div class="flex gap-2 mb-2">
                                <input type="text" wire:model="nueva_etiqueta"
                                       wire:keydown.enter.prevent="agregarEtiqueta"
                                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white"
                                       placeholder="Escribe una etiqueta y presiona Enter">
                                <button type="button" wire:click="agregarEtiqueta"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Agregar
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($etiquetas as $index => $etiqueta)
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $etiqueta }}
                                                <button type="button" wire:click="eliminarEtiqueta({{ $index }})"
                                                        class="ml-2 text-blue-600 hover:text-blue-800">
                                                    ×
                                                </button>
                                            </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Botones de Acción --}}
                <div class="px-8 py-6 bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-b-xl border-2 border-t-0 border-slate-200 dark:border-slate-700 flex justify-between items-center shadow-sm">
                    <button type="button" wire:click="volver"
                            class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all border-2 border-blue-500 dark:border-blue-600 shadow-sm font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Cancelar
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg font-semibold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardar Seguimiento
                    </button>
                </div>
            </form>
        </div>

        {{-- Historial de Seguimientos --}}
        @if($seguimientos->isNotEmpty())
            <div class="mt-8 bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition-shadow">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Historial de Seguimientos
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Registro completo de avances anteriores</p>
                </div>

                <div class="p-8">
                    <div class="space-y-6">
                        @foreach($seguimientos as $seg)
                            <div class="relative border-l-4
                                        @if($seg->progreso_nuevo >= 100) border-green-500
                                        @elseif($seg->progreso_nuevo >= 50) border-blue-500
                                        @else border-yellow-500
                                        @endif
                                        pl-8 pb-6 last:pb-0">

                                {{-- Indicador circular --}}
                                <div class="absolute -left-3 top-0 w-6 h-6 rounded-full border-4 border-white dark:border-slate-800
                                            @if($seg->progreso_nuevo >= 100) bg-green-500
                                            @elseif($seg->progreso_nuevo >= 50) bg-blue-500
                                            @else bg-yellow-500
                                            @endif"></div>

                                <div
                                    class="bg-gradient-to-br from-slate-50 to-white dark:from-slate-700 dark:to-slate-800 rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow border border-gray-100 dark:border-slate-700">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <p class="text-lg font-bold text-gray-900 dark:text-white">
                                                    {{ $seg->fecha_registro->format('d/m/Y') }}
                                                </p>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ $seg->fecha_registro->format('H:i') }}
                                                        </span>
                                                <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full
                                                            @if($seg->progreso_nuevo >= 100) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($seg->progreso_nuevo >= 50) bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                            @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @endif">
                                                            {{ number_format($seg->progreso_nuevo, 0) }}%
                                                        </span>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                            @if($seg->nivel_riesgo === 'critico') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                            @elseif($seg->nivel_riesgo === 'alto') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                                            @elseif($seg->nivel_riesgo === 'medio') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @else bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @endif">
                                                            {{ ucfirst($seg->nivel_riesgo) }}
                                                        </span>
                                            </div>
                                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                                {{ $seg->descripcion_avance }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-slate-600">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Gasto</p>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                                ${{ number_format($seg->monto_gastado, 2) }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Acumulado</p>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                                ${{ number_format($seg->gasto_acumulado_nuevo, 2) }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Responsable</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $seg->responsable_nombre }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Estado</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ ucfirst(str_replace('_', ' ', $seg->estado_nuevo)) }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($seg->logros || $seg->dificultades)
                                        <div
                                            class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-600 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @if($seg->logros)
                                                <div>
                                                    <p class="text-xs font-semibold text-green-600 dark:text-green-400 mb-1">
                                                        ✓ Logros</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $seg->logros }}</p>
                                                </div>
                                            @endif
                                            @if($seg->dificultades)
                                                <div>
                                                    <p class="text-xs font-semibold text-orange-600 dark:text-orange-400 mb-1">
                                                        ⚠ Dificultades</p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $seg->dificultades }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>

</div>
</div>
</div>
