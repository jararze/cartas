<?php

use App\Models\Carta;
use Livewire\Volt\Component;

new class extends Component {
    public $search = '';
    public $estado_filter = '';
    public $sort_filter = 'created_at';
    public $cartas = [];
    public $page = 1;
    public $hasMore = true;
    public $loading = false;

    public function mount(): void
    {
        $this->loadCartas();
    }

    public function updatedSearch(): void
    {
        $this->resetCartas();
    }

    public function updatedEstadoFilter(): void
    {
        $this->resetCartas();
    }

    public function updatedSortFilter(): void
    {
        $this->resetCartas();
    }

    public function resetCartas(): void
    {
        $this->cartas = [];
        $this->page = 1;
        $this->hasMore = true;
        $this->loadCartas();
    }

    public function loadMore(): void
    {
        if (!$this->hasMore || $this->loading) return;

        $this->page++;
        $this->loadCartas();
    }

    public function loadCartas(): void
    {
        $this->loading = true;

        $query = Carta::with(['proveedor', 'creador']);

        // Filtro de búsqueda
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('codigo', 'like', '%'.$this->search.'%')
                    ->orWhere('nombre_proyecto', 'like', '%'.$this->search.'%')
                    ->orWhereHas('proveedor', function ($proveedorQuery) {
                        $proveedorQuery->where('nombre', 'like', '%'.$this->search.'%')
                            ->orWhere('empresa', 'like', '%'.$this->search.'%');
                    });
            });
        }

        // Filtro de estado
        if ($this->estado_filter) {
            $query->where('estado', $this->estado_filter);
        }

        // Ordenamiento
        $query->orderBy($this->sort_filter, 'desc');

        $perPage = 6; // 6 cards por carga
        $newCartas = $query->skip(($this->page - 1) * $perPage)
            ->take($perPage)
            ->get();

        if ($newCartas->count() < $perPage) {
            $this->hasMore = false;
        }

        $this->cartas = array_merge($this->cartas, $newCartas->toArray());
        $this->loading = false;
    }

    public function with(): array
    {
        return [
            'stats' => [
                'total' => Carta::count(),
                'en_progreso' => Carta::whereIn('estado', ['en_ejecucion', 'aceptada'])->count(),
                'pendientes' => Carta::where('estado', 'enviada')->count(),
                'valor_total' => Carta::sum('monto_total') ?? 0,
            ]
        ];
    }

    public function getEstadoConfig($estado): array
    {
        return match ($estado) {
            'borrador' => [
                'class' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300', 'text' => 'Borrador'
            ],
            'enviada' => [
                'class' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
                'text' => 'Pendiente'
            ],
            'aceptada' => [
                'class' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400', 'text' => 'Aceptada'
            ],
            'rechazada' => [
                'class' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400', 'text' => 'Rechazada'
            ],
            'en_ejecucion' => [
                'class' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400', 'text' => 'En Progreso'
            ],
            'finalizada' => [
                'class' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                'text' => 'Finalizada'
            ],
            default => [
                'class' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300', 'text' => 'Sin Estado'
            ],
        };
    }

    public function getProgressColor($progreso): string
    {
        if ($progreso >= 70) return 'bg-green-500 dark:bg-green-600';
        if ($progreso >= 40) return 'bg-blue-500 dark:bg-blue-600';
        if ($progreso >= 20) return 'bg-yellow-500 dark:bg-yellow-600';
        return 'bg-gray-300 dark:bg-gray-600';
    }
}; ?>

<div class="p-4 sm:p-6 lg:px-8"
     x-data="{
        init() {
            const sentinel = document.getElementById('scroll-sentinel');
            if (sentinel) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            // Solo cargar si no está cargando y hay más contenido
                            if (!this.$wire.loading && this.$wire.hasMore) {
                                this.$wire.loadMore();
                            }
                        }
                    });
                }, {
                    root: null,
                    rootMargin: '50px',
                    threshold: 0.1
                });

                observer.observe(sentinel);
            }
        }
    }"
     x-init="init()"
>
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('cartas.index') }}"
           class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
           wire:navigate>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Sistema de Gestión de Cartas Documento FAO</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Gestión integral de cartas, productos y seguimiento de
                actividades</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Barra de búsqueda y filtros -->
        <div class="mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex flex-col md:flex-row gap-4 flex-1">
                <!-- Búsqueda -->
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        wire:model.live.debounce.500ms="search"
                        type="text"
                        placeholder="Buscar cartas..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400"
                    >
                </div>

                <!-- Filtros -->
                <select
                    wire:model.live="estado_filter"
                    class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                >
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="enviada">Pendiente</option>
                    <option value="aceptada">Aceptada</option>
                    <option value="en_ejecucion">En Progreso</option>
                    <option value="rechazada">Rechazada</option>
                    <option value="finalizada">Finalizada</option>
                </select>

                <select
                    wire:model.live="sort_filter"
                    class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                >
                    <option value="created_at">Ordenar por fecha</option>
                    <option value="nombre_proyecto">Ordenar por nombre</option>
                    <option value="monto_total">Ordenar por monto</option>
                    <option value="estado">Ordenar por estado</option>
                </select>
            </div>

            <!-- Botón Nueva Carta -->
            <flux:button variant="primary" href="{{ route('cartas.create') }}" wire:navigate class="whitespace-nowrap">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Carta
            </flux:button>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <!-- Total Cartas -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Cartas</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- En Progreso -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">En Progreso</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['en_progreso'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pendientes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pendientes</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pendientes'] }}</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Presupuesto Total -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Presupuesto Total</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            ${{ number_format($stats['valor_total']/1000, 0) }}K</p>
                    </div>
                    <div
                        class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Cartas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($cartas as $carta)
                @php
                    $cartaObj = (object) $carta;
                    $estadoConfig = $this->getEstadoConfig($cartaObj->estado);
                    $progreso = rand(10, 95); // Simulación del progreso
                @endphp

                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:scale-[1.02]">
                    <div class="p-6">
                        <!-- Header de la carta -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span
                                        class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                        {{ $cartaObj->codigo }}
                                    </span>
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-medium {{ $estadoConfig['class'] }}">
                                        {{ $estadoConfig['text'] }}
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                    {{ $cartaObj->nombre_proyecto }}
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                    {{ $cartaObj->descripcion_servicios }}
                                </p>
                            </div>

                            <button class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded ml-2">
                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Presupuesto</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    @if($cartaObj->monto_total)
                                        ${{ number_format($cartaObj->monto_total) }}
                                    @else
                                        No especificado
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Período</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    @if($cartaObj->fecha_inicio && $cartaObj->fecha_fin)
                                        {{ \Carbon\Carbon::parse($cartaObj->fecha_inicio)->format('m/Y') }}
                                        - {{ \Carbon\Carbon::parse($cartaObj->fecha_fin)->format('m/Y') }}
                                    @else
                                        Sin fechas
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Productos</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ is_array($cartaObj->productos_requeridos) ? count($cartaObj->productos_requeridos) : 0 }}
                                    producto{{ is_array($cartaObj->productos_requeridos) && count($cartaObj->productos_requeridos) !== 1 ? 's' : '' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Actividades</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">2 activas</p>
                            </div>
                        </div>

                        <!-- Progreso -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span
                                    class="text-xs font-medium text-gray-600 dark:text-gray-400">Progreso General</span>
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $progreso }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="{{ $this->getProgressColor($progreso) }} h-2 rounded-full transition-all"
                                     style="width: {{ $progreso }}%"></div>
                            </div>
                        </div>

                        <!-- Colaboradores -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <p class="text-xs text-gray-600 dark:text-gray-400 mr-2">Colaboradores:</p>
                                <div class="flex -space-x-2">
                                    @php
                                        $initials = '';
                                        if (isset($carta['proveedor']['nombre'])) {
                                            $names = explode(' ', $carta['proveedor']['nombre']);
                                            $initials = substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : '');
                                        }
                                    @endphp
                                    <div
                                        class="w-7 h-7 bg-blue-500 dark:bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-white dark:ring-gray-800">
                                        {{ $initials }}
                                    </div>
                                    @if(rand(0, 1))
                                        <div
                                            class="w-7 h-7 bg-green-500 dark:bg-green-600 rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-white dark:ring-gray-800">
                                            AC
                                        </div>
                                    @endif
                                    @if(rand(0, 1))
                                        <div
                                            class="w-7 h-7 bg-gray-400 dark:bg-gray-500 rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-white dark:ring-gray-800">
                                            +1
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Alertas -->
                        @if($cartaObj->estado === 'en_ejecucion' && rand(0, 2) === 0)
                            <div class="mb-4 p-2 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0" fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-xs text-red-700 dark:text-red-400 font-medium">1 actividad
                                        atrasada</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Footer con botón -->
                    <div class="px-6 pb-6">
                        <a href="{{ route('cartas.show', $cartaObj->id) }}"
                           wire:navigate
                           class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition text-sm text-center block">
                            Ver Detalle
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sentinel para infinite scroll -->
        <div id="scroll-sentinel" class="h-10 flex items-center justify-center">
            @if($loading)
                <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600 dark:text-blue-400"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Cargando más cartas...
                </div>
            @elseif(!$hasMore && count($cartas) > 0)
                <p class="text-gray-500 dark:text-gray-400 text-sm">No hay más cartas para mostrar</p>
            @endif
        </div>

        <!-- Estado vacío -->
        @if(empty($cartas))
            <div
                class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay cartas documento</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando tu primera carta documento</p>
                <div class="mt-6">
                    <flux:button variant="primary" href="{{ route('cartas.create') }}" wire:navigate>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Crear Primera Carta
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
