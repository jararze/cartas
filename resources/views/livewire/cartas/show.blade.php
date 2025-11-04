<?php

use App\Models\Carta;
use App\Models\Producto;
use App\Models\Actividad;
use Livewire\Volt\Component;

new class extends Component {
    public Carta $carta;
    public $showKPIModal = false;
    public $showCollaboratorsModal = false;
    public $showProductModal = false;
    public $showActivityModal = false;
    public $selectedProducto = null;

    // Formulario nuevo producto
    public $producto_nombre = '';
    public $producto_descripcion = '';
    public $producto_presupuesto = '';
    public $producto_fecha_inicio = '';
    public $producto_fecha_fin = '';
    public $producto_kpis = [];

    // Formulario nueva actividad
    public $actividad_nombre = '';
    public $actividad_descripcion = '';
    public $actividad_presupuesto = '';
    public $actividad_fecha_inicio = '';
    public $actividad_fecha_fin = '';
    public $actividad_linea_presupuestaria = '';

    // Colaboradores
    public $colaborador_email = '';
    public $colaborador_telefono = '';
    public $colaborador_mensaje = '';

    public $showSeguimientoModal = false;
    public $selectedActividad = null;

// Formulario de seguimiento
    public $nuevo_progreso = '';
    public $nuevo_gasto = '';
    public $descripcion_avance = '';
    public $responsable_avance = '';

    public $logros = '';
    public $dificultades = '';
    public $proximos_pasos = '';
    public $proxima_revision = '';
    public $nivel_riesgo = 'bajo';
    public $riesgos_identificados = '';
    public $acciones_correctivas = '';
    public $etiquetas = [];
    public $observaciones = '';
    public $justificacion_sobregiro = '';
    public $archivos = [];
    public $imagenes = [];

    public function mount(Carta $carta): void
    {
        $this->carta = $carta->load(['productos.actividades', 'colaboradores']);
    }

    public function with(): array
    {
        $totalPresupuesto = $this->carta->productos->sum('presupuesto') ?? 0;
        $totalEjecutado = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });
        $saldoDisponible = $totalPresupuesto - $totalEjecutado;
        $porcentajeEjecutado = $totalPresupuesto > 0 ? round(($totalEjecutado / $totalPresupuesto) * 100) : 0;

        $totalActividades = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->count();
        });
        $actividadesCompletadas = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->where('progreso', 100)->count();
        });
        $actividadesEnCurso = $this->carta->productos->sum(function ($producto) {
            return $producto->actividades->where('progreso', '>', 0)->where('progreso', '<', 100)->count();
        });
        $actividadesPendientes = $totalActividades - $actividadesCompletadas - $actividadesEnCurso;

        $progresoGeneral = $totalActividades > 0 ?
            round($this->carta->productos->sum(function ($producto) {
                    return $producto->actividades->avg('progreso') ?? 0;
                }) / $this->carta->productos->count()) : 0;

        return [
            'totalPresupuesto' => $totalPresupuesto,
            'totalEjecutado' => $totalEjecutado,
            'saldoDisponible' => $saldoDisponible,
            'porcentajeEjecutado' => $porcentajeEjecutado,
            'totalActividades' => $totalActividades,
            'actividadesCompletadas' => $actividadesCompletadas,
            'actividadesEnCurso' => $actividadesEnCurso,
            'actividadesPendientes' => $actividadesPendientes,
            'progresoGeneral' => $progresoGeneral,
        ];
    }

    public function openSeguimientoModal($actividadId): void
    {
        $this->selectedActividad = Actividad::find($actividadId);

        // Limpiar TODOS los campos del formulario
        $this->reset([
            'nuevo_progreso',
            'nuevo_gasto',
            'descripcion_avance',
            'responsable_avance',
            'logros',
            'dificultades',
            'proximos_pasos',
            'proxima_revision',
            'nivel_riesgo',
            'riesgos_identificados',
            'acciones_correctivas',
            'etiquetas',
            'observaciones',
            'justificacion_sobregiro',
            'archivos',
            'imagenes'
        ]);

        // Establecer valores por defecto
        if ($this->selectedActividad) {
            $this->nuevo_progreso = $this->selectedActividad->progreso;
            $this->nivel_riesgo = 'bajo'; // Valor por defecto
        }

        $this->showSeguimientoModal = true;
    }

    public function registrarSeguimiento(): void
    {
        // Validación básica
        $this->validate([
            'nuevo_progreso' => 'required|numeric|min:0|max:100',
            'nuevo_gasto' => 'required|numeric|min:0',
            'descripcion_avance' => 'required|string|min:10',
            'responsable_avance' => 'required|string|min:3',
            'logros' => 'nullable|string',
            'dificultades' => 'nullable|string',
            'proximos_pasos' => 'nullable|string',
            'proxima_revision' => 'nullable|date',
            'nivel_riesgo' => 'required|in:bajo,medio,alto,critico',
            'riesgos_identificados' => 'nullable|string',
            'acciones_correctivas' => 'nullable|string',
            'etiquetas' => 'nullable|array',
            'observaciones' => 'nullable|string',
            'justificacion_sobregiro' => 'nullable|string',
        ], [
            'nuevo_progreso.required' => 'El progreso es obligatorio',
            'nuevo_progreso.min' => 'El progreso debe ser al menos 0',
            'nuevo_progreso.max' => 'El progreso no puede ser mayor a 100',
            'nuevo_gasto.required' => 'El monto gastado es obligatorio',
            'nuevo_gasto.min' => 'El monto gastado no puede ser negativo',
            'descripcion_avance.required' => 'La descripción del avance es obligatoria',
            'descripcion_avance.min' => 'La descripción debe tener al menos 10 caracteres',
            'responsable_avance.required' => 'El responsable es obligatorio',
            'responsable_avance.min' => 'El nombre del responsable debe tener al menos 3 caracteres',
            'nivel_riesgo.required' => 'Debe seleccionar un nivel de riesgo',
        ]);

        // Validaciones de lógica de negocio
        if ($this->nuevo_progreso < $this->selectedActividad->progreso) {
            session()->flash('error', '❌ El nuevo progreso no puede ser menor al actual');
            return;
        }

        $nuevoGastoAcumulado = $this->selectedActividad->gasto_acumulado + $this->nuevo_gasto;
        $saldoDisponible = $this->selectedActividad->monto - $nuevoGastoAcumulado;
        $excedePpto = $saldoDisponible < 0;

        // Si excede presupuesto, requiere justificación
        if ($excedePpto && empty($this->justificacion_sobregiro)) {
            session()->flash('error', '❌ Se requiere justificación para el sobrepresupuesto');
            return;
        }

        // Calcular métricas
        $progresoAnterior = $this->selectedActividad->progreso;
        $gastoAnterior = $this->selectedActividad->gasto_acumulado;
        $variacionPresupuesto = $this->nuevo_gasto - ($this->selectedActividad->monto - $gastoAnterior);
        $variacionPorcentaje = $this->selectedActividad->monto > 0
            ? (($nuevoGastoAcumulado / $this->selectedActividad->monto) * 100) - (($gastoAnterior / $this->selectedActividad->monto) * 100)
            : 0;

        // Calcular índice de eficiencia
        $indiceEficiencia = $nuevoGastoAcumulado > 0
            ? ($this->nuevo_progreso / (($nuevoGastoAcumulado / $this->selectedActividad->monto) * 100))
            : 0;

        // Determinar nuevo estado
        $nuevoEstado = match (true) {
            $this->nuevo_progreso == 100 => 'finalizado',
            $this->nuevo_progreso > 0 => 'en_curso',
            default => 'pendiente'
        };

        // Si está atrasado (fecha_fin pasada y progreso < 100)
        $estaAtrasado = now()->gt($this->selectedActividad->fecha_fin) && $this->nuevo_progreso < 100;
        if ($estaAtrasado) {
            $nuevoEstado = 'atrasado';
        }

        // Crear registro de seguimiento
        $seguimiento = $this->selectedActividad->seguimientos()->create([
            'progreso_anterior' => $progresoAnterior,
            'progreso_nuevo' => $this->nuevo_progreso,
            'monto_gastado' => $this->nuevo_gasto,
            'gasto_acumulado_anterior' => $gastoAnterior,
            'gasto_acumulado_nuevo' => $nuevoGastoAcumulado,
            'descripcion_avance' => $this->descripcion_avance,
            'logros' => $this->logros,
            'dificultades' => $this->dificultades,
            'proximos_pasos' => $this->proximos_pasos,
            'proxima_revision' => $this->proxima_revision,
            'responsable_nombre' => $this->responsable_avance,
            'observaciones' => $this->observaciones,
            'estado_anterior' => $this->selectedActividad->estado,
            'estado_nuevo' => $nuevoEstado,
            'excede_presupuesto' => $excedePpto,
            'esta_atrasado' => $estaAtrasado,
            'variacion_presupuesto' => $variacionPresupuesto,
            'variacion_presupuesto_porcentaje' => $variacionPorcentaje,
            'indice_eficiencia' => $indiceEficiencia,
            'nivel_riesgo' => $this->nivel_riesgo,
            'riesgos_identificados' => $this->riesgos_identificados,
            'acciones_correctivas' => $this->acciones_correctivas,
            'etiquetas' => $this->etiquetas,
            'registrado_por' => auth()->id(),
            'fecha_registro' => now(),
            'estado_revision' => $excedePpto ? 'requiere_cambios' : 'pendiente',
        ]);

        // Si hay justificación de sobregiro, guardarla en observaciones
        if ($excedePpto && $this->justificacion_sobregiro) {
            $seguimiento->update([
                'observaciones' => "JUSTIFICACIÓN SOBREPRESUPUESTO: {$this->justificacion_sobregiro}\n\n".($this->observaciones ?? '')
            ]);
        }

        // Actualizar la actividad
        $this->selectedActividad->update([
            'progreso' => $this->nuevo_progreso,
            'gasto_acumulado' => $nuevoGastoAcumulado,
            'estado' => $nuevoEstado,
        ]);

        // Manejo de archivos (implementar según tu sistema de storage)
        // $this->handleFileUploads($seguimiento);

        $this->showSeguimientoModal = false;
        $this->carta->refresh();

        $mensaje = $excedePpto
            ? '⚠️ Seguimiento registrado. ATENCIÓN: Se excedió el presupuesto en $'.number_format(abs($saldoDisponible),
                2)
            : '✅ Seguimiento registrado exitosamente';

        session()->flash('message', $mensaje);
    }

    public function openProductModal(): void
    {
        $this->reset([
            'producto_nombre', 'producto_descripcion', 'producto_presupuesto', 'producto_fecha_inicio',
            'producto_fecha_fin'
        ]);
        $this->showProductModal = true;
    }

    public function createProducto(): void
    {
        $this->validate([
            'producto_nombre' => 'required|min:3',
            'producto_descripcion' => 'required|min:10',
            'producto_presupuesto' => 'required|numeric|min:0',
            'producto_fecha_inicio' => 'required|date',
            'producto_fecha_fin' => 'required|date|after:producto_fecha_inicio',
        ]);

        $this->carta->productos()->create([
            'nombre' => $this->producto_nombre,
            'descripcion' => $this->producto_descripcion,
            'presupuesto' => $this->producto_presupuesto,
            'fecha_inicio' => $this->producto_fecha_inicio,
            'fecha_fin' => $this->producto_fecha_fin,
            'indicadores_kpi' => [],
            'orden' => $this->carta->productos->count() + 1,
        ]);

        $this->showProductModal = false;
        $this->carta->refresh();

        session()->flash('message', '✅ Producto creado exitosamente');
    }

    public function openActivityModal($productoId): void
    {
        $this->selectedProducto = $productoId;
        $this->reset([
            'actividad_nombre', 'actividad_descripcion', 'actividad_presupuesto', 'actividad_fecha_inicio',
            'actividad_fecha_fin', 'actividad_linea_presupuestaria'
        ]);
        $this->showActivityModal = true;
    }

    public function createActividad(): void
    {
        $this->validate([
            'actividad_nombre' => 'required|min:3',
            'actividad_descripcion' => 'required|min:10',
            'actividad_presupuesto' => 'required|numeric|min:0',
            'actividad_fecha_inicio' => 'required|date',
            'actividad_fecha_fin' => 'required|date|after:actividad_fecha_inicio',
            'actividad_linea_presupuestaria' => 'required',
        ]);

        $producto = Producto::find($this->selectedProducto);
        $producto->actividades()->create([
            'nombre' => $this->actividad_nombre,
            'descripcion' => $this->actividad_descripcion,
            'monto' => $this->actividad_presupuesto,
            'fecha_inicio' => $this->actividad_fecha_inicio,
            'fecha_fin' => $this->actividad_fecha_fin,
            'linea_presupuestaria' => $this->actividad_linea_presupuestaria,
            'estado' => 'pendiente',
            'progreso' => 0,
            'gasto_acumulado' => 0,
        ]);

        $this->showActivityModal = false;
        $this->carta->refresh();

        session()->flash('message', '✅ Actividad creada exitosamente');
    }

    public function openCollaboratorsModal(): void
    {
        $this->reset(['colaborador_email', 'colaborador_telefono', 'colaborador_mensaje']);
        $this->showCollaboratorsModal = true;
    }

    public function inviteCollaborator(): void
    {
        $this->validate([
            'colaborador_email' => 'required|email',
            'colaborador_mensaje' => 'nullable|string|max:500',
        ]);

        // Aquí implementarías la lógica de invitación
        // Por ejemplo, crear un registro en la tabla colaboradores
        // y enviar email/WhatsApp

        session()->flash('message', '✅ Invitación enviada exitosamente');
        $this->showCollaboratorsModal = false;
    }

    public function getEstadoClass($estado): string
    {
        return match ($estado) {
            'finalizado' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
            'en_progreso' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
            'pendiente' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        };
    }

    public function getProgressColor($progreso): string
    {
        if ($progreso >= 100) return 'bg-purple-500 dark:bg-purple-600';
        if ($progreso >= 70) return 'bg-green-500 dark:bg-green-600';
        if ($progreso >= 40) return 'bg-blue-500 dark:bg-blue-600';
        if ($progreso >= 20) return 'bg-yellow-500 dark:bg-yellow-600';
        return 'bg-gray-300 dark:bg-gray-600';
    }

    /**
     * Calcular el nuevo saldo después de registrar el gasto
     */
    public function getNuevoSaldoCalculado()
    {
        if (!$this->selectedActividad) {
            return 0;
        }

        $nuevoGastoNumerico = floatval($this->nuevo_gasto ?? 0);
        $montoActividad = floatval($this->selectedActividad->monto);
        $gastoAcumulado = floatval($this->selectedActividad->gasto_acumulado);

        return $montoActividad - ($gastoAcumulado + $nuevoGastoNumerico);
    }

    /**
     * Verificar si excede el presupuesto
     */
    public function getExcedePresupuestoCalculado()
    {
        return $this->getNuevoSaldoCalculado() < 0;
    }

    /**
     * Calcular índice de eficiencia
     */
    public function getIndiceEficienciaCalculado()
    {
        if (!$this->selectedActividad) {
            return 0;
        }

        $nuevoProgresoNumerico = floatval($this->nuevo_progreso ?? 0);
        $nuevoGastoNumerico = floatval($this->nuevo_gasto ?? 0);
        $gastoAcumulado = floatval($this->selectedActividad->gasto_acumulado);
        $monto = floatval($this->selectedActividad->monto);

        if ($nuevoProgresoNumerico <= 0 || $nuevoGastoNumerico <= 0) {
            return 0;
        }

        $gastoAcumuladoNuevo = $gastoAcumulado + $nuevoGastoNumerico;

        if ($gastoAcumuladoNuevo <= 0 || $monto <= 0) {
            return 0;
        }

        return $nuevoProgresoNumerico / (($gastoAcumuladoNuevo / $monto) * 100);
    }
}; ?>

<div class="min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('cartas.index') }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                   wire:navigate>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $carta->codigo }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">{{ $carta->nombre_proyecto }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto lg:px-8 py-8">

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div
                class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('message') }}</p>
            </div>
        @endif

        <!-- Resumen Ejecutivo -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Resumen Ejecutivo</h2>
                <div class="flex gap-3">
                    <button wire:click="$set('showKPIModal', true)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Ver KPIs
                    </button>
                    <button wire:click="openCollaboratorsModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Colaboradores
                    </button>
                </div>
            </div>

            <!-- Métricas Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Presupuesto Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        USD {{ number_format($totalPresupuesto, 2) }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ $carta->productos->count() }} productos</p>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-l-4 border-green-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ejecutado</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        USD {{ number_format($totalEjecutado, 2) }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400">{{ $porcentajeEjecutado }}% del
                        presupuesto</p>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border-l-4 border-purple-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Saldo Disponible</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        USD {{ number_format($saldoDisponible, 2) }}</p>
                    <p class="text-xs text-purple-600 dark:text-purple-400">{{ 100 - $porcentajeEjecutado }}%
                        restante</p>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border-l-4 border-orange-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Progreso General</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $progresoGeneral }}%</p>
                    <p class="text-xs text-orange-600 dark:text-orange-400">{{ $actividadesCompletadas }}
                        /{{ $totalActividades }} actividades</p>
                </div>
            </div>

            <!-- Barra de Ejecución -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ejecución Presupuestaria</span>
                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $porcentajeEjecutado }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-blue-600 dark:bg-blue-500 h-3 rounded-full transition-all"
                         style="width: {{ $porcentajeEjecutado }}%"></div>
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-3 gap-6">
                <div class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg mx-auto mb-2">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $actividadesCompletadas }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Completadas</p>
                </div>

                <div class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg mx-auto mb-2">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $actividadesEnCurso }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">En Curso</p>
                </div>

                <div class="text-center">
                    <div
                        class="flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg mx-auto mb-2">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $actividadesPendientes }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pendientes</p>
                </div>
            </div>
        </div>

        <!-- Información del Proyecto y Colaboradores -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Información del Proyecto -->
            <div
                class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Proyecto</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Responsable FAO</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $carta->creador->name ?? 'No asignado' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Estado</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($carta->estado === 'aceptada') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                            @elseif($carta->estado === 'en_ejecucion') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                            @elseif($carta->estado === 'enviada') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                            {{ ucfirst(str_replace('_', ' ', $carta->estado)) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $carta->fecha_inicio ? \Carbon\Carbon::parse($carta->fecha_inicio)->format('d/m/Y') : 'No definida' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha Fin</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ $carta->fecha_fin ? \Carbon\Carbon::parse($carta->fecha_fin)->format('d/m/Y') : 'No definida' }}
                        </p>
                    </div>
                </div>

                @if($carta->descripcion_servicios)
                    <div class="mt-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Descripción</p>
                        <p class="text-gray-900 dark:text-white text-sm leading-relaxed">{{ $carta->descripcion_servicios }}</p>
                    </div>
                @endif
            </div>

            <!-- Colaboradores -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Colaboradores</h3>
                    <button wire:click="openCollaboratorsModal"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Invitar
                    </button>
                </div>

                <div class="space-y-3">
                    <!-- Creador del proyecto -->
                    <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div
                            class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                            {{ substr($carta->creador->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $carta->creador->name ?? 'Usuario' }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Responsable FAO</p>
                        </div>
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">Admin</span>
                    </div>

                    <!-- Proveedor -->
                    @if($carta->proveedor)
                        <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div
                                class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                {{ substr($carta->proveedor->nombre ?? 'P', 0, 2) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $carta->proveedor->nombre }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Proveedor</p>
                            </div>
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium">Externo</span>
                        </div>
                    @endif

                    <!-- Colaboradores adicionales -->
                    @forelse($carta->colaboradores as $colaborador)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div
                                class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white font-medium text-sm">
                                {{ substr($colaborador->name ?? 'C', 0, 2) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $colaborador->name }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ $colaborador->email }}</p>
                            </div>
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Colaborador</span>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay colaboradores adicionales</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Plan de Trabajo: Productos y Actividades MEJORADO -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Plan de Trabajo</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Productos, actividades y seguimiento
                            integrado</p>
                    </div>
                    <button wire:click="openProductModal"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Producto
                    </button>
                </div>
            </div>

            @if($carta->productos->isEmpty())
                <!-- Estado vacío -->
                <div class="p-12 text-center">
                    <div
                        class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">No hay productos
                        registrados</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Comienza creando el primer producto del
                        proyecto</p>
                    <button wire:click="openProductModal"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crear Primer Producto
                    </button>
                </div>
            @else
                <!-- Lista de productos -->
                <div id="productosContainer">
                    @foreach($carta->productos as $producto)
                        @php
                            $presupuestoProducto = $producto->actividades->sum('monto');
                            $gastoProducto = $producto->actividades->sum('gasto_acumulado');
                            $saldoProducto = $presupuestoProducto - $gastoProducto;
                            $progresoProducto = $producto->actividades->avg('progreso') ?? 0;
                            $ejecucionProducto = $presupuestoProducto > 0 ? round(($gastoProducto / $presupuestoProducto) * 100) : 0;

                            $estadoClass = match($producto->actividades->where('progreso', 100)->count()) {
                                $producto->actividades->count() => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                0 => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                            };
                        @endphp

                        <div class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                            <!-- Cabecera del producto -->
                            <div class="p-6 hover:bg-gray-500 dark:hover:bg-gray-750 transition-colors">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-3">
                                            <button
                                                x-data="{ open: false }"
                                                @click="open = !open; $refs.content{{ $producto->id }}.classList.toggle('hidden')"
                                                class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                                <svg
                                                    class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
                                                    :class="{ 'rotate-180': open }"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $producto->nombre }}</h3>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $estadoClass }}">
                                        @if($producto->actividades->where('progreso', 100)->count() == $producto->actividades->count())
                                                    Finalizado
                                                @elseif($producto->actividades->where('progreso', '>', 0)->count() > 0)
                                                    En Progreso
                                                @else
                                                    Pendiente
                                                @endif
                                    </span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $producto->actividades->count() }} actividad(es)
                                    </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 ml-9">{{ $producto->descripcion }}</p>

                                        <!-- Métricas del producto -->
                                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-4 ml-9">
                                            <div class="bg-gray-500 dark:bg-gray-700 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Presupuesto</p>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                                    ${{ number_format($presupuestoProducto, 2) }}
                                                </p>
                                            </div>
                                            <div class="bg-gray-500 dark:bg-gray-700 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Ejecutado</p>
                                                <p class="text-sm font-bold text-green-600 dark:text-green-400">
                                                    ${{ number_format($gastoProducto, 2) }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ejecucionProducto }}
                                                    %</p>
                                            </div>
                                            <div class="bg-gray-500 dark:bg-gray-700 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Saldo</p>
                                                <p class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                                    ${{ number_format($saldoProducto, 2) }}
                                                </p>
                                            </div>
                                            <div class="bg-gray-500 dark:bg-gray-700 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Periodo</p>
                                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    {{ \Carbon\Carbon::parse($producto->fecha_inicio)->format('d/m/Y') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($producto->fecha_fin)->format('d/m/Y') }}
                                                </p>
                                            </div>
                                            <div class="bg-gray-500 dark:bg-gray-700 rounded-lg p-3">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Progreso</p>
                                                <p class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                                    {{ round($progresoProducto) }}%
                                                </p>
                                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mt-1">
                                                    <div class="bg-purple-600 dark:bg-purple-400 h-2 rounded-full"
                                                         style="width: {{ round($progresoProducto) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button wire:click="openActivityModal({{ $producto->id }})"
                                            class="ml-4 inline-flex items-center gap-2 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Nueva Actividad
                                    </button>
                                </div>
                            </div>

                            <!-- Actividades del producto (acordeón) -->
                            <div x-ref="content{{ $producto->id }}" class="hidden">
                                <div class="px-6 pb-6 bg-gray-800 dark:bg-gray-700">
                                    @if($producto->actividades->isEmpty())
                                        <div class="text-center py-8">
                                            <p class="text-gray-500 dark:text-gray-400 mb-4">No hay actividades
                                                registradas</p>
                                            <button wire:click="openActivityModal({{ $producto->id }})"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Crear Primera Actividad
                                            </button>
                                        </div>
                                    @else
                                        <div class="space-y-3">
                                            @foreach($producto->actividades as $actividad)
                                                @php
                                                    $saldoActividad = $actividad->monto - $actividad->gasto_acumulado;
                                                    $ejecucionActividad = $actividad->monto > 0 ? round(($actividad->gasto_acumulado / $actividad->monto) * 100) : 0;

                                                    $estadoActividadClass = match($actividad->estado) {
                                                        'finalizado' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                        'en_curso' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                        'atrasado' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                                                    };
                                                @endphp

                                                <div
                                                    class="bg-white dark:bg-gray-800 rounded-lg p-5 shadow-sm border border-gray-200 dark:border-gray-700">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-3">
                                                        <span
                                                            class="text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                            #{{ $actividad->id }}
                                                        </span>
                                                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $actividad->nombre }}</h4>
                                                                <span
                                                                    class="px-2 py-0.5 rounded-full text-xs font-medium {{ $estadoActividadClass }}">
                                                            {{ ucfirst($actividad->estado) }}
                                                        </span>
                                                            </div>

                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $actividad->descripcion }}</p>

                                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                                <div>
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                                        Línea Presupuestaria</p>
                                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $actividad->linea_presupuestaria }}</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                                        Presupuesto</p>
                                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                                        ${{ number_format($actividad->monto, 2) }}
                                                                    </p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                                        Ejecutado</p>
                                                                    <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                                                                        ${{ number_format($actividad->gasto_acumulado, 2) }}
                                                                    </p>
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ejecucionActividad }}
                                                                        %</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                                        Saldo</p>
                                                                    <p class="text-sm font-semibold {{ $saldoActividad < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                                                                        ${{ number_format($saldoActividad, 2) }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div class="mt-4">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span
                                                                        class="text-xs font-medium text-gray-600 dark:text-gray-400">Progreso Técnico</span>
                                                                    <span
                                                                        class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                                                {{ $actividad->progreso }}%
                                                            </span>
                                                                </div>
                                                                <div
                                                                    class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                                    <div
                                                                        class="bg-purple-600 dark:bg-purple-400 h-2 rounded-full transition-all"
                                                                        style="width: {{ $actividad->progreso }}%"></div>
                                                                </div>
                                                            </div>

                                                            @if($saldoActividad < 0)
                                                                <div
                                                                    class="mt-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-3 rounded">
                                                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                                                                        ⚠️ Presupuesto excedido en
                                                                        ${{ number_format(abs($saldoActividad), 2) }}
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="flex gap-2">
                                                            <button wire:click="openSeguimientoModal({{ $actividad->id }})"
                                                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                                </svg>
                                                                Registrar Seguimiento
                                                            </button>

                                                            <a href="{{ route('actividades.historial', $actividad->id) }}"
                                                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                </svg>
                                                                Ver Historial
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Nuevo Producto -->
    <div x-data="{ show: @entangle('showProductModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Nuevo Producto</h2>
                    <button wire:click="$set('showProductModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="createProducto" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Producto
                        *</label>
                    <input wire:model="producto_nombre" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    @error('producto_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción *</label>
                    <textarea wire:model="producto_descripcion" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    @error('producto_descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presupuesto (USD)
                            *</label>
                        <input wire:model="producto_presupuesto" type="number" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('producto_presupuesto') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio
                            *</label>
                        <input wire:model="producto_fecha_inicio" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('producto_fecha_inicio') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Fin
                            *</label>
                        <input wire:model="producto_fecha_fin" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('producto_fecha_fin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showProductModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">
                        Crear Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nueva Actividad -->
    <div x-data="{ show: @entangle('showActivityModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Nueva Actividad</h2>
                    <button wire:click="$set('showActivityModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="createActividad" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de la
                        Actividad *</label>
                    <input wire:model="actividad_nombre" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    @error('actividad_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción *</label>
                    <textarea wire:model="actividad_descripcion" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    @error('actividad_descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presupuesto (USD)
                            *</label>
                        <input wire:model="actividad_presupuesto" type="number" step="0.01"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('actividad_presupuesto') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Línea
                            Presupuestaria *</label>
                        <select wire:model="actividad_linea_presupuestaria"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">Seleccionar...</option>
                            <option value="Consultoría">Consultoría</option>
                            <option value="Equipamiento">Equipamiento</option>
                            <option value="Logística">Logística</option>
                            <option value="Capacitación">Capacitación</option>
                            <option value="Recursos Humanos">Recursos Humanos</option>
                        </select>
                        @error('actividad_linea_presupuestaria') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio
                            *</label>
                        <input wire:model="actividad_fecha_inicio" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('actividad_fecha_inicio') <span
                            class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Fin
                            *</label>
                        <input wire:model="actividad_fecha_fin" type="date"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('actividad_fecha_fin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showActivityModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        Crear Actividad
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Colaboradores -->
    <div x-data="{ show: @entangle('showCollaboratorsModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Invitar Colaboradores</h2>
                    <button wire:click="$set('showCollaboratorsModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form wire:submit="inviteCollaborator" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email del Colaborador
                        *</label>
                    <input wire:model="colaborador_email" type="email"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    @error('colaborador_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono/WhatsApp
                        (Opcional)</label>
                    <input wire:model="colaborador_telefono" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensaje
                        Personalizado</label>
                    <textarea wire:model="colaborador_mensaje" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                              placeholder="Mensaje opcional para incluir en la invitación..."></textarea>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="$set('showCollaboratorsModal', false)"
                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                        Enviar Invitación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Seguimiento -->
    <div x-data="{ show: @entangle('showSeguimientoModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Registrar Seguimiento</h2>
                        @if($selectedActividad)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $selectedActividad->nombre }}</p>
                        @endif
                    </div>
                    <button wire:click="$set('showSeguimientoModal', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            @if($selectedActividad)
                <div class="p-6">
                    <!-- Resumen de la actividad -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Presupuesto Total</p>
                                <p class="font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($selectedActividad->monto, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Ejecutado Acumulado</p>
                                <p class="font-bold text-green-600 dark:text-green-400">
                                    ${{ number_format($selectedActividad->gasto_acumulado, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Saldo Disponible</p>
                                <p class="font-bold {{ ($selectedActividad->monto - $selectedActividad->gasto_acumulado) < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                                    ${{ number_format($selectedActividad->monto - $selectedActividad->gasto_acumulado, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Progreso Actual</p>
                                <p class="font-bold text-purple-600 dark:text-purple-400">{{ $selectedActividad->progreso }}
                                    %</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="registrarSeguimiento" class="space-y-6" x-data="{ tab: 'progreso' }">

                        <!-- Tabs de secciones -->
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-4">
                                <button type="button" @click="tab = 'progreso'"
                                        :class="tab === 'progreso' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    📊 Progreso y Presupuesto
                                </button>
                                <button type="button" @click="tab = 'detalles'"
                                        :class="tab === 'detalles' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    📝 Detalles
                                </button>
                                <button type="button" @click="tab = 'riesgos'"
                                        :class="tab === 'riesgos' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    ⚠️ Riesgos
                                </button>
                                <button type="button" @click="tab = 'evidencia'"
                                        :class="tab === 'evidencia' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                                    📎 Evidencia
                                </button>
                            </nav>
                        </div>

                        <!-- Tab 1: Progreso y Presupuesto -->
                        <div x-show="tab === 'progreso'" x-cloak class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nuevo Progreso (%) *
                                    </label>
                                    <input wire:model.live.number="nuevo_progreso" type="number"
                                           min="{{ $selectedActividad->progreso }}" max="100" step="1"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    @error('nuevo_progreso')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Actual: {{ $selectedActividad->progreso }}%
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Monto Gastado Esta Vez (USD) *
                                    </label>
                                    <input wire:model.live.number="nuevo_gasto" type="number" min="0" step="0.01"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    @error('nuevo_gasto')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror

                                    @php
                                        $nuevoSaldo = $this->getNuevoSaldoCalculado();
                                        $excedePpto = $this->getExcedePresupuestoCalculado();
                                    @endphp

                                    <p class="text-xs mt-1 {{ $excedePpto ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-500 dark:text-gray-400' }}">
                                        @if($excedePpto)
                                            ⚠️ Excede presupuesto por ${{ number_format(abs($nuevoSaldo), 2) }}
                                        @else
                                            Saldo después: ${{ number_format($nuevoSaldo, 2) }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Alerta si excede presupuesto -->
                            @if($excedePpto && $nuevo_gasto > 0)
                                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                  clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-bold text-red-800 dark:text-red-200">⚠️ ALERTA: Sobrepresupuesto</h4>
                                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                                Este gasto excederá el presupuesto en <span class="font-bold">${{ number_format(abs($nuevoSaldo), 2) }}</span>.
                                                Se requiere justificación y aprobación especial.
                                            </p>
                                            <div class="mt-3">
                                                <label class="block text-sm font-medium text-red-800 dark:text-red-200 mb-1">
                                                    Justificación del Sobregiro *
                                                </label>
                                                <textarea wire:model="justificacion_sobregiro" rows="2"
                                                          class="w-full px-3 py-2 border border-red-300 dark:border-red-600 rounded-lg focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                          placeholder="Explique por qué es necesario exceder el presupuesto..."></textarea>
                                                @error('justificacion_sobregiro')
                                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Indicador de eficiencia -->
                            @if($nuevo_progreso > 0 && $nuevo_gasto > 0)
                                @php
                                    // Convertir todos los valores a números
                                    $nuevoProgresoNum = floatval($nuevo_progreso ?? 0);
                                    $progresoActualNum = floatval($selectedActividad->progreso ?? 0);
                                    $nuevoGastoNum = floatval($nuevo_gasto ?? 0);
                                    $gastoAcumuladoNum = floatval($selectedActividad->gasto_acumulado ?? 0);
                                    $montoNum = floatval($selectedActividad->monto ?? 1);

                                    // Cálculos
                                    $progresoIncremento = $nuevoProgresoNum - $progresoActualNum;
                                    $gastoAcumuladoNuevo = $gastoAcumuladoNum + $nuevoGastoNum;

                                    // Calcular porcentaje de gasto
                                    $porcentajeGasto = $montoNum > 0 ? ($gastoAcumuladoNuevo / $montoNum) * 100 : 0;

                                    // Calcular índice de eficiencia de manera segura
                                    if ($gastoAcumuladoNuevo > 0 && $montoNum > 0 && $nuevoProgresoNum > 0) {
                                        $indiceEficiencia = $nuevoProgresoNum / ((($gastoAcumuladoNuevo / $montoNum) * 100));
                                    } else {
                                        $indiceEficiencia = 0;
                                    }

                                    // Determinar color según eficiencia
                                    if ($indiceEficiencia >= 1) {
                                        $eficienteColor = 'green';
                                        $eficienteTexto = '✅ Eficiente (Progreso ≥ Gasto)';
                                    } elseif ($indiceEficiencia >= 0.8) {
                                        $eficienteColor = 'yellow';
                                        $eficienteTexto = '⚠️ Aceptable';
                                    } else {
                                        $eficienteColor = 'red';
                                        $eficienteTexto = '🔴 Ineficiente (Gasto > Progreso)';
                                    }
                                @endphp

                                <div class="bg-{{ $eficienteColor }}-50 dark:bg-{{ $eficienteColor }}-900/20 border border-{{ $eficienteColor }}-200 dark:border-{{ $eficienteColor }}-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-{{ $eficienteColor }}-800 dark:text-{{ $eficienteColor }}-200">
                        Índice de Eficiencia: {{ number_format($indiceEficiencia, 2) }}
                    </span>
                                        <span class="text-xs text-{{ $eficienteColor }}-600 dark:text-{{ $eficienteColor }}-400">
                        {{ $eficienteTexto }}
                    </span>
                                    </div>
                                    <div class="mt-2 text-xs text-{{ $eficienteColor }}-700 dark:text-{{ $eficienteColor }}-300">
                                        <p>Progreso: {{ number_format($nuevoProgresoNum, 1) }}% | Gasto: {{ number_format($porcentajeGasto, 1) }}%</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Tab 2: Detalles -->
                        <div x-show="tab === 'detalles'" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción del Avance *
                                </label>
                                <textarea wire:model="descripcion_avance" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Describa los avances realizados..."></textarea>
                                @error('descripcion_avance')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Responsable del Avance *
                                </label>
                                <input wire:model="responsable_avance" type="text"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                       placeholder="Nombre del responsable">
                                @error('responsable_avance')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Logros Alcanzados
                                </label>
                                <textarea wire:model="logros" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué se logró en este periodo?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Dificultades Encontradas
                                </label>
                                <textarea wire:model="dificultades" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué obstáculos se presentaron?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Próximos Pasos
                                </label>
                                <textarea wire:model="proximos_pasos" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué se planea hacer a continuación?"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Próxima Fecha de Revisión
                                </label>
                                <input wire:model="proxima_revision" type="date"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <!-- Tab 3: Riesgos -->
                        <div x-show="tab === 'riesgos'" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nivel de Riesgo
                                </label>
                                <select wire:model="nivel_riesgo"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    <option value="bajo">🟢 Bajo - Todo en orden</option>
                                    <option value="medio">🟡 Medio - Requiere atención</option>
                                    <option value="alto">🟠 Alto - Necesita acción inmediata</option>
                                    <option value="critico">🔴 Crítico - Riesgo severo</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Riesgos Identificados
                                </label>
                                <textarea wire:model="riesgos_identificados" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Liste los riesgos detectados..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Acciones Correctivas Propuestas
                                </label>
                                <textarea wire:model="acciones_correctivas" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="¿Qué acciones se tomarán para mitigar los riesgos?"></textarea>
                            </div>

                            <!-- Etiquetas -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Etiquetas (Tags)
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['urgente', 'requiere_revision', 'en_riesgo', 'cambio_alcance', 'retraso', 'sobrepresupuesto'] as $tag)
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="etiquetas" value="{{ $tag }}"
                                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $tag)) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Evidencia -->
                        <div x-show="tab === 'evidencia'" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Archivos Adjuntos
                                </label>
                                <input wire:model="archivos" type="file" multiple accept=".pdf,.doc,.docx,.xls,.xlsx"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF, Word, Excel - Máx. 10MB por archivo</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Imágenes/Fotos
                                </label>
                                <input wire:model="imagenes" type="file" multiple accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG, PNG - Máx. 5MB por imagen</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Observaciones Adicionales
                                </label>
                                <textarea wire:model="observaciones" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                          placeholder="Cualquier información adicional relevante..."></textarea>
                            </div>
                        </div>

                        <!-- Resumen Final -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-900 dark:text-blue-200 mb-2">📋 Resumen del Registro</h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Progreso:</span>
                                    <span class="font-bold text-blue-900 dark:text-blue-100">
                    {{ number_format($selectedActividad->progreso ?? 0, 1) }}% → {{ number_format($nuevo_progreso ?? 0, 1) }}%
                </span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Gasto Acumulado:</span>
                                    <span class="font-bold text-blue-900 dark:text-blue-100">
                    @php
                        $gastoTotalCalculado = floatval($selectedActividad->gasto_acumulado ?? 0) + floatval($nuevo_gasto ?? 0);
                    @endphp
                    ${{ number_format($gastoTotalCalculado, 2) }}
                </span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Gasto Esta Vez:</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">
                    ${{ number_format(floatval($nuevo_gasto ?? 0), 2) }}
                </span>
                                </div>
                                <div>
                                    <span class="text-blue-700 dark:text-blue-300">Saldo Resultante:</span>
                                    <span class="font-bold {{ $this->getExcedePresupuestoCalculado() ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                    ${{ number_format(abs($this->getNuevoSaldoCalculado()), 2) }}
                                        @if($this->getExcedePresupuestoCalculado())
                                            <span class="text-xs">(Exceso)</span>
                                        @endif
                </span>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="$set('showSeguimientoModal', false)"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold">
                                💾 Guardar Seguimiento
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</div>


