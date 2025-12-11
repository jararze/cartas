<?php

use Livewire\Volt\Component;
use App\Models\Carta;

new class extends Component {
    public $tipoReporte = 'resumen';
    public $formato = 'pdf';
    public $fechaInicio;
    public $fechaFin;
    public $cartaId = null;
    public $estado = null;

    public function mount()
    {
        $this->fechaInicio = now()->startOfMonth()->format('Y-m-d');
        $this->fechaFin = now()->format('Y-m-d');
    }

    public function generarReporte()
    {
        $params = [
            'tipo' => $this->tipoReporte,
            'formato' => $this->formato,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'carta_id' => $this->cartaId,
            'estado' => $this->estado,
        ];

        $url = route('reportes.descargar', $params);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function getCartasProperty()
    {
        $query = Carta::orderBy('codigo');

        // Filtrar por proveedor si el usuario es proveedor
        $user = auth()->user();
        if ($user->hasRole('Proveedor') && $user->proveedor) {
            $query->where('proveedor_id', $user->proveedor->id);
        }

        return $query->get();
    }
}; ?>

<div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Generador de Reportes
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Selecciona el tipo de reporte y formato de exportación</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Panel de Reportes Disponibles --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Reportes Principales --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 px-6 py-4 border-b-2 border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Reportes Principales</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Resumen Ejecutivo --}}
                        <div wire:click="$set('tipoReporte', 'resumen')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all
                                    {{ $tipoReporte === 'resumen' ? 'border-blue-500 ring-2 ring-blue-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'resumen' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'resumen' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'resumen')
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Resumen Ejecutivo</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Vista general del sistema con
                                estadísticas clave y alertas</p>
                        </div>

                        {{-- Análisis Financiero --}}
                        <div wire:click="$set('tipoReporte', 'financiero')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all
                                    {{ $tipoReporte === 'financiero' ? 'border-green-500 ring-2 ring-green-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'financiero' ? 'bg-green-100 dark:bg-green-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'financiero' ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'financiero')
                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Análisis Financiero</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Presupuestos, ejecución y saldos
                                detallados por carta</p>
                        </div>

                        {{-- Avance de Actividades --}}
                        <div wire:click="$set('tipoReporte', 'avance')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all
                                    {{ $tipoReporte === 'avance' ? 'border-purple-500 ring-2 ring-purple-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'avance' ? 'bg-purple-100 dark:bg-purple-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'avance' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'avance')
                                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Avance de Actividades</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Progreso y estados de todas las
                                actividades</p>
                        </div>

                        {{-- Actividades Detalladas --}}
                        <div wire:click="$set('tipoReporte', 'actividades')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all
                                    {{ $tipoReporte === 'actividades' ? 'border-orange-500 ring-2 ring-orange-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'actividades' ? 'bg-orange-100 dark:bg-orange-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'actividades' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'actividades')
                                    <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Lista de Actividades</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Listado completo con responsables y
                                fechas</p>
                        </div>

                    </div>
                </div>

                {{-- Reportes Especializados --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 px-6 py-4 border-b-2 border-slate-200 dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Reportes Especializados</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Ejecutado vs Planificado --}}
                        <div wire:click="$set('tipoReporte', 'ejecutado_vs_planificado')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all
                                    {{ $tipoReporte === 'ejecutado_vs_planificado' ? 'border-red-500 ring-2 ring-red-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'ejecutado_vs_planificado' ? 'bg-red-100 dark:bg-red-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'ejecutado_vs_planificado' ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'ejecutado_vs_planificado')
                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Ejecutado vs
                                Planificado</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Comparación de presupuestos planificados
                                vs ejecutados</p>
                            <span
                                class="inline-block mt-2 px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-semibold rounded-full">NUEVO</span>
                        </div>

                        {{-- Plan de Trabajo --}}
                        <div wire:click="$set('tipoReporte', 'plan_trabajo')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all
                                    {{ $tipoReporte === 'plan_trabajo' ? 'border-cyan-500 ring-2 ring-cyan-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'plan_trabajo' ? 'bg-cyan-100 dark:bg-cyan-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'plan_trabajo' ? 'text-cyan-600 dark:text-cyan-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'plan_trabajo')
                                    <svg class="w-6 h-6 text-cyan-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Plan de Trabajo con
                                Gantt</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Productos, actividades y diagrama de
                                Gantt en Excel</p>
                            <span
                                class="inline-block mt-2 px-2 py-1 bg-cyan-100 dark:bg-cyan-900 text-cyan-800 dark:text-cyan-200 text-xs font-semibold rounded-full">NUEVO</span>
                        </div>

                        <div wire:click="$set('tipoReporte', 'lineas_presupuestarias')"
                             class="cursor-pointer group bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 p-6 shadow-sm hover:shadow-md transition-all {{ $tipoReporte === 'lineas_presupuestarias' ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200 dark:border-slate-700' }}">
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="p-3 rounded-lg {{ $tipoReporte === 'lineas_presupuestarias' ? 'bg-indigo-100 dark:bg-indigo-900' : 'bg-slate-200 dark:bg-slate-700' }}">
                                    <svg
                                        class="w-6 h-6 {{ $tipoReporte === 'lineas_presupuestarias' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-600 dark:text-gray-400' }}"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                @if($tipoReporte === 'lineas_presupuestarias')
                                    <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                              d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Por Líneas
                                Presupuestarias</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Desglose por línea → carta → producto →
                                actividad</p>
                            <span
                                class="inline-block mt-2 px-2 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 text-xs font-semibold rounded-full">NUEVO</span>
                        </div>

                    </div>
                </div>

            </div>

            {{-- Panel de Configuración --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden sticky top-6">
                    <div
                        class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 px-6 py-4">
                        <h2 class="text-xl font-bold text-white">Configuración</h2>
                    </div>

                    <form wire:submit="generarReporte" class="p-6 space-y-6">

                        {{-- Formato --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Formato de
                                Exportación</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" wire:click="$set('formato', 'pdf')"
                                        class="px-4 py-3 rounded-lg border-2 font-medium transition-all
                                        {{ $formato === 'pdf' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'border-slate-200 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:border-blue-300' }}">
                                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    PDF
                                </button>
                                <button type="button" wire:click="$set('formato', 'excel')"
                                        class="px-4 py-3 rounded-lg border-2 font-medium transition-all
                                        {{ $formato === 'excel' ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'border-slate-200 dark:border-slate-600 text-gray-700 dark:text-gray-300 hover:border-green-300' }}">
                                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Excel
                                </button>
                            </div>
                        </div>

                        {{-- Filtro por Fechas --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Rango de
                                Fechas</label>
                            <div class="space-y-3">
                                <input type="date" wire:model="fechaInicio"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                <input type="date" wire:model="fechaFin"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                            </div>
                        </div>

                        {{-- Filtro por Carta --}}
                        @if($this->cartas->count() > 1)
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Filtrar
                                    por Carta (opcional)</label>
                                <select wire:model="cartaId"
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                    <option value="">Todas las cartas ({{ $this->cartas->count() }})</option>
                                    @foreach($this->cartas as $carta)
                                        <option value="{{ $carta->id }}">{{ $carta->codigo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($this->cartas->count() === 1)
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Carta</label>
                                <div
                                    class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                        {{ $this->cartas->first()->codigo }}
                                    </p>
                                    <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                                        {{ $this->cartas->first()->nombre_proyecto }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- Filtro por Estado --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Estado
                                (opcional)</label>
                            <select wire:model="estado"
                                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
                                <option value="">Todos los estados</option>
                                <option value="borrador">Borrador</option>
                                <option value="enviada">Enviada</option>
                                <option value="en_ejecucion">En Ejecución</option>
                                <option value="finalizada">Finalizada</option>
                            </select>
                        </div>

                        {{-- Botón Generar --}}
                        <button type="submit"
                                class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all border-2 border-blue-500 dark:border-blue-600 shadow-lg font-bold text-lg">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Generar Reporte
                            </span>
                        </button>

                    </form>
                </div>
            </div>

        </div>

    </div>
</div>
