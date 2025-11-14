<?php

use App\Models\Producto;
use Livewire\Volt\Component;

new class extends Component {
    public Producto $producto;

    public function mount(Producto $producto)
    {
        $this->producto = $producto->load([
            'carta.proveedor',
            'actividades.responsable',
            'actividades.seguimientos'
        ]);
    }

    public function with(): array
    {
        $actividades = $this->producto->actividades;

        // Estadísticas del producto
        $stats = [
            'total_actividades' => $actividades->count(),
            'completadas' => $actividades->where('estado', 'finalizado')->count(),
            'en_curso' => $actividades->where('estado', 'en_curso')->count(),
            'pendientes' => $actividades->where('estado', 'pendiente')->count(),
            'atrasadas' => $actividades->where('estado', 'atrasado')->count(),
            'presupuesto_total' => $actividades->sum('monto'),
            'gasto_total' => $actividades->sum('gasto_acumulado'),
            'progreso_promedio' => $actividades->avg('progreso') ?? 0,
        ];

        $stats['saldo_disponible'] = $stats['presupuesto_total'] - $stats['gasto_total'];
        $stats['porcentaje_ejecucion'] = $stats['presupuesto_total'] > 0
            ? ($stats['gasto_total'] / $stats['presupuesto_total']) * 100
            : 0;

        // Actividades agrupadas por estado
        $actividadesPorEstado = [
            'pendiente' => $actividades->where('estado', 'pendiente')->sortBy('fecha_inicio'),
            'en_curso' => $actividades->where('estado', 'en_curso')->sortBy('progreso')->reverse(),
            'finalizado' => $actividades->where('estado', 'finalizado')->sortByDesc('updated_at'),
            'atrasado' => $actividades->where('estado', 'atrasado')->sortBy('fecha_fin'),
        ];

        // Gastos por línea presupuestaria
        $gastosPorLinea = $actividades->groupBy('linea_presupuestaria')->map(function($acts) {
            return [
                'presupuesto' => $acts->sum('monto'),
                'gastado' => $acts->sum('gasto_acumulado'),
                'actividades' => $acts->count(),
            ];
        });

        return [
            'stats' => $stats,
            'actividadesPorEstado' => $actividadesPorEstado,
            'gastosPorLinea' => $gastosPorLinea,
        ];
    }

    public function getEstadoColor($estado)
    {
        return match($estado) {
            'pendiente' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'en_curso' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'finalizado' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'atrasado' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'cancelado' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}; ?>

<div title="Detalle del Producto">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('productos.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Productos</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">{{ $producto->nombre }}</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $producto->nombre }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $producto->descripcion }}</p>

                    <div class="flex flex-wrap gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Carta:</span>
                            <a href="{{ route('cartas.show', $producto->carta) }}" class="ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                                {{ $producto->carta->codigo }}
                            </a>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Período:</span>
                            <span class="ml-2 text-gray-900 dark:text-white font-medium">
                                {{ \Carbon\Carbon::parse($producto->fecha_inicio)->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($producto->fecha_fin)->format('d/m/Y') }}
                            </span>
                        </div>
                        @if($producto->carta->proveedor)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Proveedor:</span>
                                <span class="ml-2 text-gray-900 dark:text-white font-medium">
                                {{ $producto->carta->proveedor->nombre }}
                            </span>
                            </div>
                        @endif
                    </div>
                </div>

                <a href="{{ route('productos.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Presupuesto Total</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    ${{ number_format($stats['presupuesto_total'], 2) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Gastado</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">
                    ${{ number_format($stats['gasto_total'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ number_format($stats['porcentaje_ejecucion'], 1) }}% ejecutado
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Saldo Disponible</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">
                    ${{ number_format($stats['saldo_disponible'], 2) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Progreso Promedio</div>
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                    {{ number_format($stats['progreso_promedio'], 1) }}%
                </div>
            </div>
        </div>

        <!-- Resumen de Actividades -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Resumen de Actividades</h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_actividades'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $stats['completadas'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Completadas</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['en_curso'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">En Curso</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-600">{{ $stats['pendientes'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Pendientes</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-red-600">{{ $stats['atrasadas'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Atrasadas</div>
                </div>
            </div>
        </div>

        <!-- Gastos por Línea Presupuestaria -->
        @if($gastosPorLinea->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Gastos por Línea Presupuestaria</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Línea Presupuestaria</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Actividades</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Presupuesto</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Gastado</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">% Ejecución</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($gastosPorLinea as $linea => $datos)
                            <tr>
                                <td class="py-3 px-4 text-gray-900 dark:text-white font-medium">{{ $linea }}</td>
                                <td class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">{{ $datos['actividades'] }}</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">${{ number_format($datos['presupuesto'], 2) }}</td>
                                <td class="py-3 px-4 text-right text-gray-900 dark:text-white">${{ number_format($datos['gastado'], 2) }}</td>
                                <td class="py-3 px-4 text-right">
                                <span class="font-semibold {{ $datos['presupuesto'] > 0 && (($datos['gastado'] / $datos['presupuesto']) * 100) > 90 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                    {{ $datos['presupuesto'] > 0 ? number_format(($datos['gastado'] / $datos['presupuesto']) * 100, 1) : 0 }}%
                                </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Actividades por Estado -->
        @foreach(['atrasado' => 'Atrasadas', 'en_curso' => 'En Curso', 'pendiente' => 'Pendientes', 'finalizado' => 'Finalizadas'] as $estado => $titulo)
            @if($actividadesPorEstado[$estado]->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Actividades {{ $titulo }} ({{ $actividadesPorEstado[$estado]->count() }})
                    </h2>
                    <div class="space-y-3">
                        @foreach($actividadesPorEstado[$estado] as $actividad)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $actividad->nombre }}</h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getEstadoColor($actividad->estado) }}">
                                        {{ ucfirst($actividad->estado) }}
                                    </span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $actividad->descripcion }}</p>
                                        <div class="flex flex-wrap gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">Presupuesto:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ number_format($actividad->monto, 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Gastado:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ number_format($actividad->gasto_acumulado, 2) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Fecha fin:</span>
                                                <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($actividad->fecha_fin)->format('d/m/Y') }}</span>
                                            </div>
                                            @if($actividad->responsable)
                                                <div>
                                                    <span class="text-gray-500">Responsable:</span>
                                                    <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $actividad->responsable->name }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ number_format($actividad->progreso, 1) }}%</div>
                                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $actividad->progreso }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
