<?php

use App\Models\Producto;
use App\Models\Carta;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterCarta = '';

    public function with(): array
    {
        $totalProductos = Producto::count();
        $presupuestoTotal = Producto::sum('presupuesto');

        $productosActivos = Producto::whereHas('carta', function($query) {
            $query->whereIn('estado', ['en_ejecucion', 'aceptada']);
        })->count();

        $progresoPromedio = \DB::table('productos')
            ->join('cartas', 'productos.carta_id', '=', 'cartas.id')
            ->whereIn('cartas.estado', ['en_ejecucion', 'aceptada'])
            ->selectRaw('AVG(
                (SELECT AVG(progreso) FROM actividades WHERE actividades.producto_id = productos.id)
            ) as promedio')
            ->value('promedio') ?? 0;

        $query = Producto::with(['carta', 'actividades'])
            ->when($this->search, function($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterCarta, function($q) {
                $q->where('carta_id', $this->filterCarta);
            })
            ->latest();

        $productos = $query->paginate(10);

        $productos->getCollection()->transform(function($producto) {
            $actividades = $producto->actividades;

            $producto->total_actividades = $actividades->count();
            $producto->actividades_completadas = $actividades->where('estado', 'finalizado')->count();
            $producto->progreso_promedio = $actividades->avg('progreso') ?? 0;
            $producto->gasto_total = $actividades->sum('gasto_acumulado');
            $producto->porcentaje_ejecucion = $producto->presupuesto > 0
                ? ($producto->gasto_total / $producto->presupuesto) * 100
                : 0;

            return $producto;
        });

        $cartas = Carta::whereIn('estado', ['en_ejecucion', 'aceptada', 'borrador'])->get();

        return [
            'kpis' => [
                'total_productos' => $totalProductos,
                'presupuesto_total' => $presupuestoTotal,
                'productos_activos' => $productosActivos,
                'progreso_promedio' => round($progresoPromedio, 1),
            ],
            'productos' => $productos,
            'cartas' => $cartas,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCarta()
    {
        $this->resetPage();
    }
}; ?>

<div title="Productos">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Productos</h1>
            <p class="text-gray-600 dark:text-gray-400">Gestión de productos y presupuestos</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Productos</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ number_format($kpis['total_productos']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Presupuesto Total</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                    ${{ number_format($kpis['presupuesto_total'], 2) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Productos Activos</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                    {{ number_format($kpis['productos_activos']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Progreso Promedio</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                    {{ $kpis['progreso_promedio'] }}%
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar productos..."
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />

                <select
                    wire:model.live="filterCarta"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todas las cartas</option>
                    @foreach($cartas as $carta)
                        <option value="{{ $carta->id }}">{{ $carta->codigo }}</option>
                    @endforeach
                </select>

                <button
                    wire:click="$set('search', '')"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Producto</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Carta</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Período</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Presupuesto</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Gastado</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Actividades</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Progreso</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($productos as $producto)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="py-4 px-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $producto->nombre }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                    {{ Str::limit($producto->descripcion, 60) }}
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $producto->carta->codigo }}
                                    </span>
                            </td>
                            <td class="py-4 px-4 text-sm text-gray-600 dark:text-gray-400">
                                <div>{{ \Carbon\Carbon::parse($producto->fecha_inicio)->format('d/m/Y') }}</div>
                                <div>{{ \Carbon\Carbon::parse($producto->fecha_fin)->format('d/m/Y') }}</div>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($producto->presupuesto, 2) }}
                                </div>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($producto->gasto_total, 2) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($producto->porcentaje_ejecucion, 1) }}%
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="text-sm">
                                    <span class="font-semibold text-green-600">{{ $producto->actividades_completadas }}</span>
                                    <span class="text-gray-500">/{{ $producto->total_actividades }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col items-center">
                                    <div class="text-sm font-semibold mb-1">{{ number_format($producto->progreso_promedio, 1) }}%</div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $producto->progreso_promedio }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <a href="{{ route('productos.show', $producto) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron productos
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $productos->links() }}
            </div>
        </div>
    </div>
</div>
