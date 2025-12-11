<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Producto;
use App\Models\Carta;
use App\Models\Desembolso;

new class extends Component {
    use WithPagination;

    // Filtros
    public string $filtroCarta = '';
    public string $busqueda = '';

    // Modal Aprobar
    public bool $mostrarModalAprobar = false;
    public ?Producto $productoSeleccionado = null;
    public string $observacionesAprobacion = '';

    // Modal Rechazar
    public bool $mostrarModalRechazar = false;
    public string $motivoRechazo = '';

    // Modal Detalle
    public bool $mostrarModalDetalle = false;

    protected $queryString = ['filtroCarta', 'busqueda'];

    public function mount(): void
    {
        if (!auth()->user()->hasAnyRole(['Administrador', 'Coordinador'])) {
            abort(403, 'No tienes permiso para acceder a este módulo');
        }
    }

    public function with(): array
    {
        $query = Producto::with(['carta', 'actividades', 'aprobador'])
            ->where('estado', 'completado')
            ->orderBy('updated_at', 'desc');

        if ($this->filtroCarta) {
            $query->where('carta_id', $this->filtroCarta);
        }

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->busqueda}%")
                    ->orWhereHas('carta', function ($q2) {
                        $q2->where('codigo', 'like', "%{$this->busqueda}%")
                            ->orWhere('nombre_proyecto', 'like', "%{$this->busqueda}%");
                    });
            });
        }

        // Estadísticas
        $stats = [
            'pendientes' => Producto::where('estado', 'completado')->count(),
            'aprobados_mes' => Producto::where('estado', 'aprobado')
                ->whereMonth('fecha_aprobacion', now()->month)
                ->whereYear('fecha_aprobacion', now()->year)
                ->count(),
            'rechazados_mes' => Producto::where('estado', 'rechazado')
                ->whereMonth('fecha_aprobacion', now()->month)
                ->whereYear('fecha_aprobacion', now()->year)
                ->count(),
            'monto_pendiente' => Producto::where('estado', 'completado')
                ->with('actividades')
                ->get()
                ->sum(fn($p) => $p->actividades->sum('monto')),
        ];

        return [
            'productos' => $query->paginate(10),
            'cartas' => Carta::has('productos')->orderBy('codigo')->get(['id', 'codigo', 'nombre_proyecto']),
            'stats' => $stats,
        ];
    }

    public function verDetalle(int $id): void
    {
        $this->productoSeleccionado = Producto::with(['carta', 'actividades', 'aprobador'])->find($id);
        $this->mostrarModalDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->mostrarModalDetalle = false;
        $this->productoSeleccionado = null;
    }

    public function abrirModalAprobar(int $id): void
    {
        $this->productoSeleccionado = Producto::with(['carta', 'actividades'])->find($id);
        $this->observacionesAprobacion = '';
        $this->mostrarModalAprobar = true;
    }

    public function cerrarModalAprobar(): void
    {
        $this->mostrarModalAprobar = false;
        $this->productoSeleccionado = null;
        $this->observacionesAprobacion = '';
    }

    public function aprobarProducto(): void
    {
        $producto = $this->productoSeleccionado;

        // Aprobar producto
        $producto->aprobar(auth()->id(), $this->observacionesAprobacion);

        // Crear desembolso automáticamente
        Desembolso::create([
            'producto_id' => $producto->id,
            'carta_id' => $producto->carta_id,
            'monto_total' => $producto->actividades->sum('monto'),
            'estado' => 'pendiente',
            'desglose_lineas' => $producto->desglose_por_linea,
            'solicitado_por' => auth()->id(),
            'fecha_solicitud' => now(),
        ]);

        $this->cerrarModalAprobar();
        $this->dispatch('notify', type: 'success', message: 'Producto aprobado. Desembolso creado y enviado a Finanzas.');
    }

    public function abrirModalRechazar(int $id): void
    {
        $this->productoSeleccionado = Producto::find($id);
        $this->motivoRechazo = '';
        $this->mostrarModalRechazar = true;
    }

    public function cerrarModalRechazar(): void
    {
        $this->mostrarModalRechazar = false;
        $this->productoSeleccionado = null;
        $this->motivoRechazo = '';
    }

    public function rechazarProducto(): void
    {
        $this->validate([
            'motivoRechazo' => 'required|string|min:10|max:500',
        ], [
            'motivoRechazo.required' => 'Debe indicar el motivo del rechazo',
            'motivoRechazo.min' => 'El motivo debe tener al menos 10 caracteres',
        ]);

        $this->productoSeleccionado->rechazar(auth()->id(), $this->motivoRechazo);

        $this->cerrarModalRechazar();
        $this->dispatch('notify', type: 'warning', message: 'Producto rechazado. El proveedor será notificado.');
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['filtroCarta', 'busqueda']);
    }
}; ?>

<div>
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Aprobación de Productos</h1>
        <p class="text-gray-600 dark:text-gray-400">Revisa y aprueba productos completados al 100%</p>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pendientes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pendientes'] }}</p>
                    <p class="text-xs text-gray-500">Esperando revisión</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="clock" class="w-6 h-6 text-orange-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Aprobados (Mes)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['aprobados_mes'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="check-circle" class="w-6 h-6 text-green-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Rechazados (Mes)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['rechazados_mes'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="x-circle" class="w-6 h-6 text-red-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Monto Pendiente</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['monto_pendiente'], 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="banknotes" class="w-6 h-6 text-purple-600" />
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="busqueda"
                    placeholder="Buscar por producto o carta..."
                    icon="magnifying-glass"
                />
            </div>
            <div class="w-full md:w-64">
                <flux:select wire:model.live="filtroCarta">
                    <option value="">Todas las cartas</option>
                    @foreach($cartas as $carta)
                        <option value="{{ $carta->id }}">{{ $carta->codigo }} - {{ Str::limit($carta->nombre_proyecto, 20) }}</option>
                    @endforeach
                </flux:select>
            </div>
            @if($filtroCarta || $busqueda)
                <flux:button variant="ghost" wire:click="limpiarFiltros" icon="x-mark">
                    Limpiar
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Lista de Productos -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Producto</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Carta</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Presupuesto</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actividades</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Progreso</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Completado</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($productos as $producto)
                    @php
                        $tieneSobrecosto = $producto->actividades->sum('gasto_acumulado') > $producto->actividades->sum('monto');
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 {{ $tieneSobrecosto ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}">
                        <td class="px-4 py-4">
                            <div class="flex items-start gap-2">
                                @if($tieneSobrecosto)
                                    <x-icon name="exclamation-triangle" class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ Str::limit($producto->nombre, 35) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($producto->descripcion, 50) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">{{ $producto->carta->codigo }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($producto->carta->nombre_proyecto, 25) }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-bold text-gray-900 dark:text-white">${{ number_format($producto->actividades->sum('monto'), 2) }}</p>
                            <p class="text-xs {{ $tieneSobrecosto ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                Ejecutado: ${{ number_format($producto->actividades->sum('gasto_acumulado'), 2) }}
                                @if($tieneSobrecosto)
                                    <span class="text-red-600">(+${{ number_format($producto->actividades->sum('gasto_acumulado') - $producto->actividades->sum('monto'), 0) }})</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $producto->actividades->count() }}</span>
                                <span class="text-xs text-green-600">({{ $producto->actividades->where('estado', 'finalizado')->count() }} finalizadas)</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $producto->progreso_promedio }}%"></div>
                                </div>
                                <span class="text-sm font-bold text-green-600">{{ $producto->progreso_promedio }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $producto->updated_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button size="xs" variant="ghost" wire:click="verDetalle({{ $producto->id }})" icon="eye" title="Ver detalle" />
                                <flux:button size="xs" variant="ghost" wire:click="abrirModalAprobar({{ $producto->id }})" icon="check-circle" title="Aprobar" class="text-green-600 hover:bg-green-50" />
                                <flux:button size="xs" variant="ghost" wire:click="abrirModalRechazar({{ $producto->id }})" icon="x-circle" title="Rechazar" class="text-red-600 hover:bg-red-50" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                                    <x-icon name="check-badge" class="w-8 h-8 text-green-500" />
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">¡Todo al día!</p>
                                <p class="text-sm text-gray-400">No hay productos pendientes de aprobación</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($productos->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                {{ $productos->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Detalle -->
    <flux:modal wire:model="mostrarModalDetalle" maxWidth="7xl" class="!max-w-[95vw]">
        @if($productoSeleccionado)
            @php
                $totalPresupuesto = $productoSeleccionado->actividades->sum('monto');
                $totalEjecutado = $productoSeleccionado->actividades->sum('gasto_acumulado');
                $tieneSobrecostoGlobal = $totalEjecutado > $totalPresupuesto;
                $montoSobrecosto = $totalEjecutado - $totalPresupuesto;
            @endphp
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-6 pb-4 border-b border-gray-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            @if($tieneSobrecostoGlobal)
                                <span class="px-3 py-1 text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-lg flex items-center gap-1">
                                    <x-icon name="exclamation-triangle" class="w-3 h-3" />
                                    Sobrecosto Detectado
                                </span>
                            @endif
                            <span class="px-3 py-1 text-xs font-bold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-lg">
                                ⏳ Pendiente de Aprobación
                            </span>
                            <span class="text-xs text-gray-500">Completado {{ $productoSeleccionado->updated_at->diffForHumans() }}</span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $productoSeleccionado->nombre }}</h2>
                        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mt-1">
                            {{ $productoSeleccionado->carta->codigo }} - {{ Str::limit($productoSeleccionado->carta->nombre_proyecto, 60) }}
                        </p>
                    </div>
                    <flux:button variant="ghost" wire:click="cerrarDetalle" icon="x-mark" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Columna Izquierda (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Descripción -->
                        <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                <x-icon name="document-text" class="w-4 h-4" />
                                Descripción del Producto
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $productoSeleccionado->descripcion ?: 'Sin descripción' }}
                            </p>
                        </div>

                        <!-- Desglose por Línea Presupuestaria -->
                        <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                                <x-icon name="chart-pie" class="w-4 h-4" />
                                Desglose por Línea Presupuestaria
                            </h3>
                            <div class="space-y-3">
                                @php
                                    $totalPlanificado = collect($productoSeleccionado->desglose_por_linea)->sum('planificado');
                                @endphp
                                @foreach($productoSeleccionado->desglose_por_linea as $linea)
                                    @php
                                        $porcentajeLinea = $totalPlanificado > 0 ? ($linea['planificado'] / $totalPlanificado) * 100 : 0;
                                        $porcentajeEjecucion = $linea['planificado'] > 0 ? ($linea['ejecutado'] / $linea['planificado']) * 100 : 0;
                                        $lineaSobrecosto = $linea['ejecutado'] > $linea['planificado'];
                                        $exceso = $linea['ejecutado'] - $linea['planificado'];
                                    @endphp
                                    <div class="rounded-lg p-3 {{ $lineaSobrecosto ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-zinc-700/50' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                @if($lineaSobrecosto)
                                                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                                @else
                                                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                                @endif
                                                <span class="text-sm font-medium {{ $lineaSobrecosto ? 'text-red-800 dark:text-red-300' : 'text-gray-900 dark:text-white' }}">
                                                    {{ $linea['linea'] }}
                                                </span>
                                                <span class="text-xs text-gray-500">({{ number_format($porcentajeLinea, 0) }}% del total)</span>
                                                @if($lineaSobrecosto)
                                                    <span class="px-2 py-0.5 text-xs font-semibold bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200 rounded">
                                                        +${{ number_format($exceso, 0) }} excedido
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-sm font-bold {{ $lineaSobrecosto ? 'text-red-800 dark:text-red-300' : 'text-gray-900 dark:text-white' }}">
                                                ${{ number_format($linea['planificado'], 2) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 bg-gray-200 dark:bg-zinc-600 rounded-full h-2 overflow-hidden">
                                                <div class="{{ $lineaSobrecosto ? 'bg-red-500' : 'bg-green-500' }} h-2 rounded-full transition-all"
                                                     style="width: {{ min($porcentajeEjecucion, 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-medium {{ $lineaSobrecosto ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                                {{ number_format($porcentajeEjecucion, 0) }}% ejecutado
                                            </span>
                                        </div>
                                        <div class="flex justify-between mt-1">
                                            <p class="text-xs {{ $lineaSobrecosto ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500' }}">
                                                Ejecutado: ${{ number_format($linea['ejecutado'], 2) }}
                                            </p>
                                            @if($lineaSobrecosto)
                                                <p class="text-xs text-red-600 dark:text-red-400 font-semibold">
                                                    ⚠️ Sobrepasado
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Lista de Actividades -->
                        <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                                <x-icon name="clipboard-document-list" class="w-4 h-4" />
                                Actividades del Producto ({{ $productoSeleccionado->actividades->count() }})
                                @if($productoSeleccionado->actividades->where('gasto_acumulado', '>', 0)->filter(fn($a) => $a->gasto_acumulado > $a->monto)->count() > 0)
                                    <span class="px-2 py-0.5 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded">
                                        {{ $productoSeleccionado->actividades->filter(fn($a) => $a->gasto_acumulado > $a->monto)->count() }} con sobrecosto
                                    </span>
                                @endif
                            </h3>
                            <div class="max-h-72 overflow-y-auto space-y-2 pr-2">
                                @foreach($productoSeleccionado->actividades as $actividad)
                                    @php
                                        $actividadSobrecosto = $actividad->gasto_acumulado > $actividad->monto;
                                        $excesoActividad = $actividad->gasto_acumulado - $actividad->monto;
                                    @endphp
                                    <div class="rounded-lg p-3 transition {{ $actividadSobrecosto ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-gray-50 dark:bg-zinc-700/50 hover:bg-gray-100 dark:hover:bg-zinc-700' }}">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    @if($actividadSobrecosto)
                                                        <x-icon name="exclamation-triangle" class="w-4 h-4 text-red-500 flex-shrink-0" />
                                                    @elseif($actividad->progreso >= 100)
                                                        <x-icon name="check-circle" class="w-4 h-4 text-green-500 flex-shrink-0" />
                                                    @else
                                                        <x-icon name="clock" class="w-4 h-4 text-blue-500 flex-shrink-0" />
                                                    @endif
                                                    <p class="text-sm font-medium {{ $actividadSobrecosto ? 'text-red-800 dark:text-red-300' : 'text-gray-900 dark:text-white' }} truncate">
                                                        {{ $actividad->nombre }}
                                                    </p>
                                                    @if($actividadSobrecosto)
                                                        <span class="px-2 py-0.5 text-xs font-semibold bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200 rounded flex-shrink-0">
                                                            +${{ number_format($excesoActividad, 0) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-3 text-xs text-gray-500">
                                                    <span class="px-2 py-0.5 {{ $actividadSobrecosto ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' }} rounded">
                                                        {{ $actividad->linea_presupuestaria }}
                                                    </span>
                                                    @if($actividad->responsable)
                                                        <span>{{ $actividad->responsable->name ?? 'Sin asignar' }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-right ml-4 flex-shrink-0">
                                                <p class="text-sm font-bold {{ $actividadSobrecosto ? 'text-red-800 dark:text-red-300' : 'text-gray-900 dark:text-white' }}">
                                                    ${{ number_format($actividad->monto, 2) }}
                                                </p>
                                                <p class="text-xs {{ $actividadSobrecosto ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                                    Gasto: ${{ number_format($actividad->gasto_acumulado, 2) }}
                                                </p>
                                                <div class="flex items-center justify-end gap-2 mt-1">
                                                    <div class="w-16 bg-gray-200 dark:bg-zinc-600 rounded-full h-1.5">
                                                        <div class="{{ $actividadSobrecosto ? 'bg-red-500' : 'bg-green-500' }} h-1.5 rounded-full" style="width: {{ $actividad->progreso }}%"></div>
                                                    </div>
                                                    <span class="text-xs font-semibold {{ $actividad->progreso >= 100 ? ($actividadSobrecosto ? 'text-red-600' : 'text-green-600') : 'text-blue-600' }}">
                                                        {{ $actividad->progreso }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha (1/3) -->
                    <div class="space-y-4">
                        <!-- Card Monto Principal -->
                        <div class="rounded-xl p-5 text-white {{ $tieneSobrecostoGlobal ? 'bg-gradient-to-br from-red-500 to-red-600' : 'bg-gradient-to-br from-green-500 to-green-600' }}">
                            <p class="{{ $tieneSobrecostoGlobal ? 'text-red-100' : 'text-green-100' }} text-sm mb-1">
                                {{ $tieneSobrecostoGlobal ? 'Monto con Sobrecosto' : 'Monto a Desembolsar' }}
                            </p>
                            <p class="text-3xl font-bold">${{ number_format($totalPresupuesto, 2) }}</p>
                            <div class="mt-3 pt-3 border-t {{ $tieneSobrecostoGlobal ? 'border-red-400/30' : 'border-green-400/30' }}">
                                <div class="flex justify-between text-sm">
                                    <span class="{{ $tieneSobrecostoGlobal ? 'text-red-100' : 'text-green-100' }}">Ejecutado:</span>
                                    <span class="font-semibold">${{ number_format($totalEjecutado, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <span class="{{ $tieneSobrecostoGlobal ? 'text-red-100' : 'text-green-100' }}">
                                        {{ $tieneSobrecostoGlobal ? 'Exceso:' : 'Diferencia:' }}
                                    </span>
                                    <span class="font-semibold {{ $tieneSobrecostoGlobal ? 'text-yellow-300' : '' }}">
                                        {{ $tieneSobrecostoGlobal ? '+' : '' }}${{ number_format(abs($montoSobrecosto), 2) }}
                                    </span>
                                </div>
                            </div>
                            @if($tieneSobrecostoGlobal)
                                <div class="mt-3 pt-3 border-t border-red-400/30">
                                    <p class="text-xs text-red-100">
                                        ⚠️ El gasto excede el presupuesto en un {{ number_format(($montoSobrecosto / $totalPresupuesto) * 100, 1) }}%
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Progreso General -->
                        <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Progreso General</h4>
                            <div class="flex items-center justify-center">
                                <div class="relative w-28 h-28">
                                    <svg class="w-full h-full transform -rotate-90">
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-200 dark:text-zinc-700"/>
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="8" fill="none"
                                                class="{{ $tieneSobrecostoGlobal ? 'text-red-500' : 'text-green-500' }}"
                                                stroke-dasharray="{{ 2 * 3.14159 * 48 }}"
                                                stroke-dashoffset="{{ 2 * 3.14159 * 48 * (1 - $productoSeleccionado->progreso_promedio / 100) }}"
                                                stroke-linecap="round"/>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-2xl font-bold {{ $tieneSobrecostoGlobal ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                            {{ $productoSeleccionado->progreso_promedio }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-center text-xs text-gray-500 mt-2">
                                {{ $productoSeleccionado->actividades->where('progreso', '>=', 100)->count() }} de {{ $productoSeleccionado->actividades->count() }} actividades completas
                            </p>
                        </div>

                        <!-- Info de la Carta -->
                        <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <x-icon name="document" class="w-4 h-4" />
                                Carta Asociada
                            </h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Código:</span>
                                    <span class="font-semibold text-blue-600">{{ $productoSeleccionado->carta->codigo }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Proyecto:</span>
                                    <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $productoSeleccionado->carta->nombre_proyecto }}</p>
                                </div>
                                @if($productoSeleccionado->carta->proveedor)
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Proveedor:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $productoSeleccionado->carta->proveedor->nombre ?? 'N/A' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Advertencia si hay sobrecosto -->
                        @if($tieneSobrecostoGlobal)
                            <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-700 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-red-100 dark:bg-red-800 rounded-full flex items-center justify-center flex-shrink-0">
                                        <x-icon name="exclamation-triangle" class="w-5 h-5 text-red-600 dark:text-red-300" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-red-800 dark:text-red-300">⚠️ Alerta de Sobrecosto</p>
                                        <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                                            El gasto ejecutado excede el presupuesto planificado por
                                            <strong class="text-red-800 dark:text-red-300">${{ number_format($montoSobrecosto, 2) }}</strong>
                                        </p>
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-2">
                                            Se recomienda revisar las actividades antes de aprobar.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer con Acciones -->
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-zinc-700">
                    <p class="text-xs text-gray-500">
                        Última actualización: {{ $productoSeleccionado->updated_at->format('d/m/Y H:i') }}
                    </p>
                    <div class="flex gap-3">
                        <flux:button variant="ghost" wire:click="cerrarDetalle">Cerrar</flux:button>
                        <flux:button variant="danger" wire:click="abrirModalRechazar({{ $productoSeleccionado->id }})" icon="x-circle">
                            Rechazar
                        </flux:button>
                        <flux:button variant="primary" wire:click="abrirModalAprobar({{ $productoSeleccionado->id }})" icon="check-circle">
                            Aprobar Producto
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Modal Aprobar -->
    <flux:modal wire:model="mostrarModalAprobar" maxWidth="md">
        @if($productoSeleccionado)
            @php
                $sobrecostoAprobacion = $productoSeleccionado->actividades->sum('gasto_acumulado') > $productoSeleccionado->actividades->sum('monto');
            @endphp
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 {{ $sobrecostoAprobacion ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-green-100 dark:bg-green-900/30' }} rounded-full flex items-center justify-center mx-auto mb-4">
                        @if($sobrecostoAprobacion)
                            <x-icon name="exclamation-triangle" class="w-8 h-8 text-orange-600" />
                        @else
                            <x-icon name="check-circle" class="w-8 h-8 text-green-600" />
                        @endif
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Aprobar Producto</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $productoSeleccionado->nombre }}</p>
                </div>

                @if($sobrecostoAprobacion)
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-4 mb-4">
                        <p class="text-sm text-orange-800 dark:text-orange-300 font-semibold">
                            ⚠️ Este producto tiene sobrecosto
                        </p>
                        <p class="text-xs text-orange-700 dark:text-orange-400 mt-1">
                            Exceso de: <strong>${{ number_format($productoSeleccionado->actividades->sum('gasto_acumulado') - $productoSeleccionado->actividades->sum('monto'), 2) }}</strong>
                        </p>
                    </div>
                @endif

                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 mb-6">
                    <p class="text-sm text-green-800 dark:text-green-300">
                        <strong>Al aprobar este producto:</strong>
                    </p>
                    <ul class="text-sm text-green-700 dark:text-green-400 mt-2 space-y-1">
                        <li>• Se creará automáticamente una solicitud de desembolso</li>
                        <li>• El área de Finanzas recibirá la notificación</li>
                        <li>• Monto a desembolsar: <strong>${{ number_format($productoSeleccionado->actividades->sum('monto'), 2) }}</strong></li>
                    </ul>
                </div>

                <form wire:submit="aprobarProducto" class="space-y-4">
                    <flux:textarea
                        wire:model="observacionesAprobacion"
                        label="Observaciones {{ $sobrecostoAprobacion ? '(Recomendado justificar sobrecosto)' : '(Opcional)' }}"
                        rows="3"
                        placeholder="{{ $sobrecostoAprobacion ? 'Justifique la aprobación del producto con sobrecosto...' : 'Agregue comentarios sobre la aprobación...' }}"
                    />

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                        <flux:button variant="ghost" wire:click="cerrarModalAprobar">Cancelar</flux:button>
                        <flux:button type="submit" variant="primary" icon="check">Confirmar Aprobación</flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>

    <!-- Modal Rechazar -->
    <flux:modal wire:model="mostrarModalRechazar" maxWidth="md">
        @if($productoSeleccionado)
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <x-icon name="x-circle" class="w-8 h-8 text-red-600" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Rechazar Producto</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $productoSeleccionado->nombre }}</p>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 mb-6">
                    <p class="text-sm text-red-800 dark:text-red-300">
                        <strong>Atención:</strong> El producto volverá a estado de revisión y el proveedor deberá realizar correcciones.
                    </p>
                </div>

                <form wire:submit="rechazarProducto" class="space-y-4">
                    <flux:textarea
                        wire:model="motivoRechazo"
                        label="Motivo del Rechazo *"
                        rows="4"
                        placeholder="Explique detalladamente el motivo del rechazo..."
                        required
                    />
                    @error('motivoRechazo')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                        <flux:button variant="ghost" wire:click="cerrarModalRechazar">Cancelar</flux:button>
                        <flux:button type="submit" variant="danger" icon="x-circle">Confirmar Rechazo</flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>
</div>
