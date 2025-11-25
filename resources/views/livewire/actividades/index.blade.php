<?php

use App\Models\Actividad;
use App\Models\Producto;
use App\Models\Carta;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterEstado = '';
    public $filterProducto = '';
    public $filterCarta = '';
    public $filterPrioridad = '';

    public function with(): array
    {
        $user = auth()->user();

        // FILTRO BASE: Si es proveedor, solo sus actividades
        $baseQuery = Actividad::query();

        if ($user->hasRole('Proveedor') && $user->proveedor) {
            $baseQuery->whereHas('producto.carta', function($q) use ($user) {
                $q->where('proveedor_id', $user->proveedor->id);
            });
        }

        // KPIs con filtro de proveedor
        $totalActividades = (clone $baseQuery)->count();
        $presupuestoTotal = (clone $baseQuery)->sum('monto');
        $gastoTotal = (clone $baseQuery)->sum('gasto_acumulado');

        $actividadesCompletadas = (clone $baseQuery)->where('estado', 'finalizado')->count();
        $actividadesEnCurso = (clone $baseQuery)->where('estado', 'en_curso')->count();
        $actividadesPendientes = (clone $baseQuery)->where('estado', 'pendiente')->count();
        $actividadesAtrasadas = (clone $baseQuery)->where('estado', 'atrasado')->count();

        $progresoPromedio = (clone $baseQuery)->whereIn('estado', ['en_curso', 'finalizado'])
            ->avg('progreso') ?? 0;

        // Query de actividades con filtros
        $query = (clone $baseQuery)->with(['producto.carta', 'responsable'])
            ->when($this->search, function($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterEstado, function($q) {
                $q->where('estado', $this->filterEstado);
            })
            ->when($this->filterProducto, function($q) {
                $q->where('producto_id', $this->filterProducto);
            })
            ->when($this->filterCarta, function($q) {
                $q->whereHas('producto', function($query) {
                    $query->where('carta_id', $this->filterCarta);
                });
            })
            ->when($this->filterPrioridad, function($q) {
                $q->where('prioridad', $this->filterPrioridad);
            })
            ->latest();

        $actividades = $query->paginate(15);

        // Calcular días restantes y porcentaje de ejecución
        $actividades->getCollection()->transform(function($actividad) {
            $actividad->dias_restantes = Carbon::parse($actividad->fecha_fin)->diffInDays(Carbon::now(), false);
            $actividad->porcentaje_ejecucion = $actividad->monto > 0
                ? ($actividad->gasto_acumulado / $actividad->monto) * 100
                : 0;
            return $actividad;
        });

        // Productos y cartas disponibles según rol
        $productosQuery = Producto::with('carta');
        $cartasQuery = Carta::whereIn('estado', ['en_ejecucion', 'aceptada', 'borrador']);

        if ($user->hasRole('Proveedor') && $user->proveedor) {
            $productosQuery->whereHas('carta', function($q) use ($user) {
                $q->where('proveedor_id', $user->proveedor->id);
            });

            $cartasQuery->where('proveedor_id', $user->proveedor->id);
        }

        $productos = $productosQuery->get();
        $cartas = $cartasQuery->get();

        return [
            'kpis' => [
                'total' => $totalActividades,
                'completadas' => $actividadesCompletadas,
                'en_curso' => $actividadesEnCurso,
                'pendientes' => $actividadesPendientes,
                'atrasadas' => $actividadesAtrasadas,
                'presupuesto_total' => $presupuestoTotal,
                'gasto_total' => $gastoTotal,
                'progreso_promedio' => round($progresoPromedio, 1),
                'porcentaje_ejecucion' => $presupuestoTotal > 0
                    ? round(($gastoTotal / $presupuestoTotal) * 100, 1)
                    : 0,
            ],
            'actividades' => $actividades,
            'productos' => $productos,
            'cartas' => $cartas,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterEstado()
    {
        $this->resetPage();
    }

    public function updatingFilterProducto()
    {
        $this->resetPage();
    }

    public function updatingFilterCarta()
    {
        $this->resetPage();
    }

    public function updatingFilterPrioridad()
    {
        $this->resetPage();
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

    public function getPrioridadColor($prioridad)
    {
        return match($prioridad) {
            'baja' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'media' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'alta' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            'critica' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}; ?>

<div title="Actividades">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Actividades</h1>
            <p class="text-gray-600 dark:text-gray-400">Seguimiento y control de actividades</p>
        </div>

        <!-- KPIs Principales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Actividades</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ number_format($kpis['total']) }}
                </div>
                <div class="mt-2 flex gap-2 text-xs">
                    <span class="text-green-600">✓ {{ $kpis['completadas'] }}</span>
                    <span class="text-blue-600">→ {{ $kpis['en_curso'] }}</span>
                    <span class="text-gray-600">○ {{ $kpis['pendientes'] }}</span>
                    <span class="text-red-600">⚠ {{ $kpis['atrasadas'] }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Presupuesto Total</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    ${{ number_format($kpis['presupuesto_total'], 2) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Gasto Total</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">
                    ${{ number_format($kpis['gasto_total'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $kpis['porcentaje_ejecucion'] }}% ejecutado
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Progreso Promedio</div>
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                    {{ $kpis['progreso_promedio'] }}%
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar actividades..."
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                />

                <select
                    wire:model.live="filterEstado"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_curso">En Curso</option>
                    <option value="finalizado">Finalizado</option>
                    <option value="atrasado">Atrasado</option>
                    <option value="cancelado">Cancelado</option>
                </select>

                <select
                    wire:model.live="filterCarta"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todas las cartas</option>
                    @foreach($cartas as $carta)
                        <option value="{{ $carta->id }}">{{ $carta->codigo }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="filterPrioridad"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todas las prioridades</option>
                    <option value="baja">Baja</option>
                    <option value="media">Media</option>
                    <option value="alta">Alta</option>
                    <option value="critica">Crítica</option>
                </select>

                <button
                    wire:click="$set('search', '')"
                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                >
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Tabla de Actividades -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Actividad</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Producto / Carta</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Estado</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Prioridad</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Presupuesto</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Gastado</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Progreso</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Fecha Fin</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($actividades as $actividad)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="py-4 px-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $actividad->nombre }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $actividad->linea_presupuestaria }}</div>
                                @if($actividad->responsable)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        👤 {{ $actividad->responsable->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ Str::limit($actividad->producto->nombre, 30) }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $actividad->producto->carta->codigo }}
                                    </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getEstadoColor($actividad->estado) }}">
                                        {{ ucfirst($actividad->estado) }}
                                    </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getPrioridadColor($actividad->prioridad) }}">
                                        {{ ucfirst($actividad->prioridad) }}
                                    </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($actividad->monto, 2) }}
                                </div>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($actividad->gasto_acumulado, 2) }}
                                </div>
                                <div class="text-xs {{ $actividad->porcentaje_ejecucion > 100 ? 'text-red-600' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ number_format($actividad->porcentaje_ejecucion, 1) }}%
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-col items-center">
                                    <div class="text-sm font-semibold mb-1">{{ number_format($actividad->progreso, 1) }}%</div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $actividad->progreso }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($actividad->fecha_fin)->format('d/m/Y') }}
                                </div>
                                @if($actividad->dias_restantes < 0)
                                    <div class="text-xs text-red-600">
                                        Vencida
                                    </div>
                                @elseif($actividad->dias_restantes <= 7)
                                    <div class="text-xs text-orange-600">
                                        {{ $actividad->dias_restantes }} días
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                <a href="{{ route('actividades.show', $actividad) }}"
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
                            <td colspan="9" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron actividades
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $actividades->links() }}
            </div>
        </div>
    </div>
</div>
