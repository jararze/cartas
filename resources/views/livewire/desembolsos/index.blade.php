<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Desembolso;
use App\Models\Producto;
use App\Models\Carta;

new class extends Component {
    use WithPagination, WithFileUploads;

    // Filtros
    public string $filtroEstado = '';
    public string $filtroCarta = '';
    public string $busqueda = '';

    // Modal procesar pago
    public bool $mostrarModalPago = false;
    public ?int $desembolsoId = null;
    public float $montoAprobado = 0;
    public string $numeroTransferencia = '';
    public string $banco = '';
    public string $cuentaDestino = '';
    public string $observaciones = '';
    public $comprobante;

    // Modal rechazo
    public bool $mostrarModalRechazo = false;
    public string $motivoRechazo = '';

    // Modal detalle
    public bool $mostrarModalDetalle = false;
    public ?Desembolso $desembolsoDetalle = null;

    protected $queryString = ['filtroEstado', 'filtroCarta', 'busqueda'];

    public function mount(): void
    {
        // Verificar permisos
        if (!auth()->user()->hasAnyRole(['Administrador', 'Finanzas'])) {
            abort(403, 'No tienes permiso para acceder a este módulo');
        }
    }

    public function with(): array
    {
        $query = Desembolso::with(['producto.carta', 'solicitante', 'procesador'])
            ->orderByRaw("FIELD(estado, 'pendiente', 'en_proceso', 'pagado', 'rechazado')")
            ->orderBy('fecha_solicitud', 'desc');

        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        if ($this->filtroCarta) {
            $query->where('carta_id', $this->filtroCarta);
        }

        if ($this->busqueda) {
            $query->whereHas('producto', function ($q) {
                $q->where('nombre', 'like', "%{$this->busqueda}%");
            })->orWhereHas('carta', function ($q) {
                $q->where('codigo', 'like', "%{$this->busqueda}%")
                    ->orWhere('nombre', 'like', "%{$this->busqueda}%");
            });
        }

        // Estadísticas
        $stats = [
            'pendientes' => Desembolso::pendientes()->count(),
            'en_proceso' => Desembolso::enProceso()->count(),
            'pagados_mes' => Desembolso::pagados()
                ->whereMonth('fecha_pago', now()->month)
                ->whereYear('fecha_pago', now()->year)
                ->count(),
            'monto_pendiente' => Desembolso::pendientes()->sum('monto_total'),
            'monto_pagado_mes' => Desembolso::pagados()
                ->whereMonth('fecha_pago', now()->month)
                ->whereYear('fecha_pago', now()->year)
                ->sum('monto_aprobado'),
        ];

        return [
            'desembolsos' => $query->paginate(10),
            'cartas' => Carta::orderBy('codigo')->get(['id', 'codigo', 'nombre_proyecto']),
            'stats' => $stats,
        ];
    }

    public function verDetalle(int $id): void
    {
        $this->desembolsoDetalle = Desembolso::with(['producto.actividades', 'carta', 'solicitante', 'procesador'])->find($id);
        $this->mostrarModalDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->mostrarModalDetalle = false;
        $this->desembolsoDetalle = null;
    }

    public function abrirModalPago(int $id): void
    {
        $desembolso = Desembolso::find($id);
        $this->desembolsoId = $id;
        $this->montoAprobado = $desembolso->monto_total;
        $this->mostrarModalPago = true;
    }

    public function cerrarModalPago(): void
    {
        $this->reset(['mostrarModalPago', 'desembolsoId', 'montoAprobado', 'numeroTransferencia', 'banco', 'cuentaDestino', 'observaciones', 'comprobante']);
    }

    public function procesarPago(): void
    {
        $this->validate([
            'montoAprobado' => 'required|numeric|min:0.01',
            'numeroTransferencia' => 'required|string|max:100',
            'banco' => 'required|string|max:100',
            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'montoAprobado.required' => 'El monto es requerido',
            'numeroTransferencia.required' => 'El número de transferencia es requerido',
            'banco.required' => 'El banco es requerido',
        ]);

        $desembolso = Desembolso::find($this->desembolsoId);

        $comprobantePath = null;
        if ($this->comprobante) {
            $comprobantePath = $this->comprobante->store('comprobantes', 'public');
        }

        $desembolso->marcarPagado([
            'monto_aprobado' => $this->montoAprobado,
            'numero_transferencia' => $this->numeroTransferencia,
            'banco' => $this->banco,
            'cuenta_destino' => $this->cuentaDestino,
            'comprobante_path' => $comprobantePath,
            'observaciones' => $this->observaciones,
        ]);

        $this->cerrarModalPago();
        $this->dispatch('notify', type: 'success', message: 'Pago procesado correctamente');
    }

    public function marcarEnProceso(int $id): void
    {
        $desembolso = Desembolso::find($id);
        $desembolso->marcarEnProceso(auth()->id());
        $this->dispatch('notify', type: 'info', message: 'Desembolso marcado en proceso');
    }

    public function abrirModalRechazo(int $id): void
    {
        $this->desembolsoId = $id;
        $this->mostrarModalRechazo = true;
    }

    public function cerrarModalRechazo(): void
    {
        $this->reset(['mostrarModalRechazo', 'desembolsoId', 'motivoRechazo']);
    }

    public function rechazarDesembolso(): void
    {
        $this->validate([
            'motivoRechazo' => 'required|string|min:10|max:500',
        ], [
            'motivoRechazo.required' => 'Debe indicar el motivo del rechazo',
            'motivoRechazo.min' => 'El motivo debe tener al menos 10 caracteres',
        ]);

        $desembolso = Desembolso::find($this->desembolsoId);
        $desembolso->rechazar($this->motivoRechazo, auth()->id());

        $this->cerrarModalRechazo();
        $this->dispatch('notify', type: 'warning', message: 'Desembolso rechazado');
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['filtroEstado', 'filtroCarta', 'busqueda']);
    }

    public function descargarPDF(int $id)
    {
        $desembolso = Desembolso::with(['producto.actividades', 'carta', 'solicitante', 'procesador'])->find($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.pdf.desembolso-detalle', [
            'desembolso' => $desembolso,
        ]);

        $filename = 'desembolso_' . $desembolso->id . '_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}; ?>

<div>
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Desembolsos</h1>
        <p class="text-gray-600 dark:text-gray-400">Procesa pagos de productos aprobados</p>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pendientes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pendientes'] }}</p>
                    <p class="text-xs text-gray-500">${{ number_format($stats['monto_pendiente'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="clock" class="w-6 h-6 text-yellow-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">En Proceso</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['en_proceso'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="arrow-path" class="w-6 h-6 text-blue-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pagados (Mes)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pagados_mes'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <x-icon name="check-circle" class="w-6 h-6 text-green-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Monto Pagado (Mes)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['monto_pagado_mes'], 2) }}</p>
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
            <div class="w-full md:w-48">
                <flux:select wire:model.live="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="pagado">Pagado</option>
                    <option value="rechazado">Rechazado</option>
                </flux:select>
            </div>
            <div class="w-full md:w-48">
                <flux:select wire:model.live="filtroCarta">
                    <option value="">Todas las cartas</option>
                    @foreach($cartas as $carta)
                        <option value="{{ $carta->id }}">{{ $carta->codigo }} - {{ Str::limit($carta->nombre_proyecto, 30) }}</option>
                    @endforeach
                </flux:select>
            </div>
            @if($filtroEstado || $filtroCarta || $busqueda)
                <flux:button variant="ghost" wire:click="limpiarFiltros" icon="x-mark">
                    Limpiar
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Tabla de Desembolsos -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Carta / Producto</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Monto</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Solicitado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Líneas Presup.</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse($desembolsos as $desembolso)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $desembolso->carta->codigo }}</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ Str::limit($desembolso->producto->nombre, 40) }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-bold text-gray-900 dark:text-white">${{ number_format($desembolso->monto_total, 2) }}</p>
                            @if($desembolso->monto_aprobado && $desembolso->monto_aprobado != $desembolso->monto_total)
                                <p class="text-xs text-green-600">Aprobado: ${{ number_format($desembolso->monto_aprobado, 2) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            {!! $desembolso->estado_badge !!}
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $desembolso->solicitante?->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $desembolso->fecha_solicitud->format('d/m/Y H:i') }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($desembolso->desglose_lineas ?? [] as $linea)
                                    <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-zinc-600 rounded-full">
                                            {{ Str::limit($linea['linea'], 15) }}
                                        </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-1">
                                <flux:button size="xs" variant="ghost" wire:click="verDetalle({{ $desembolso->id }})" icon="eye" title="Ver detalle" />

                                @if($desembolso->estado === 'pendiente')
                                    <flux:button size="xs" variant="ghost" wire:click="marcarEnProceso({{ $desembolso->id }})" icon="arrow-path" title="Marcar en proceso" class="text-blue-600" />
                                @endif

                                @if(in_array($desembolso->estado, ['pendiente', 'en_proceso']))
                                    <flux:button size="xs" variant="ghost" wire:click="abrirModalPago({{ $desembolso->id }})" icon="banknotes" title="Procesar pago" class="text-green-600" />
                                    <flux:button size="xs" variant="ghost" wire:click="abrirModalRechazo({{ $desembolso->id }})" icon="x-circle" title="Rechazar" class="text-red-600" />
                                @endif

                                @if($desembolso->estado === 'pagado' && $desembolso->comprobante_path)
                                    <a href="{{ Storage::url($desembolso->comprobante_path) }}" target="_blank">
                                        <flux:button size="xs" variant="ghost" icon="document-arrow-down" title="Ver comprobante" />
                                    </a>
                                @endif

                                <flux:button size="xs" variant="ghost" wire:click="descargarPDF({{ $desembolso->id }})" icon="document-arrow-down" title="Descargar PDF" class="text-purple-600" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <x-icon name="inbox" class="w-12 h-12 text-gray-400 mb-3" />
                                <p class="text-gray-500 dark:text-gray-400">No hay desembolsos registrados</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($desembolsos->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-700">
                {{ $desembolsos->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Detalle -->
    <!-- Modal Detalle -->
    <flux:modal wire:model="mostrarModalDetalle" maxWidth="7xl" class="!max-w-[95vw]">
        @if($desembolsoDetalle)
            <div class="p-6 max-h-[85vh] overflow-y-auto">
                <!-- Header del Modal -->
                <div class="flex items-start justify-between mb-6 pb-4 border-b border-gray-200 dark:border-zinc-700">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg">
                            {{ $desembolsoDetalle->carta->codigo }}
                        </span>
                            {!! $desembolsoDetalle->estado_badge !!}
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white truncate" title="{{ $desembolsoDetalle->carta->nombre_proyecto }}">
                            {{ Str::limit($desembolsoDetalle->carta->nombre_proyecto, 60) }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Desembolso #{{ $desembolsoDetalle->id }} • Solicitado {{ $desembolsoDetalle->fecha_solicitud->diffForHumans() }}
                        </p>
                    </div>
                    <flux:button variant="ghost" wire:click="cerrarDetalle" icon="x-mark" class="ml-4" />
                </div>

                <!-- Grid Principal -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Columna Izquierda: Info Principal -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Producto -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border border-blue-100 dark:border-blue-800">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <x-icon name="cube" class="w-6 h-6 text-white" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Producto</p>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $desembolsoDetalle->producto->nombre }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $desembolsoDetalle->producto->descripcion }}</p>

                                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                                        <div>
                                            <p class="text-xs text-gray-500">Presupuesto</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">${{ number_format($desembolsoDetalle->producto->presupuesto, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Progreso</p>
                                            <p class="text-sm font-bold text-green-600">{{ $desembolsoDetalle->producto->progreso_promedio }}%</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Actividades</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $desembolsoDetalle->producto->actividades->count() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose por Línea Presupuestaria -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                            <div class="px-5 py-4 bg-gray-50 dark:bg-zinc-700/50 border-b border-gray-200 dark:border-zinc-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <x-icon name="chart-pie" class="w-4 h-4 text-purple-500" />
                                    Desglose por Línea Presupuestaria
                                </h3>
                            </div>
                            <div class="p-4">
                                <div class="space-y-3">
                                    @forelse($desembolsoDetalle->desglose_lineas ?? [] as $linea)
                                        @php
                                            $porcentaje = $linea['planificado'] > 0 ? round(($linea['ejecutado'] / $linea['planificado']) * 100) : 0;
                                        @endphp
                                        <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $linea['linea'] }}</span>
                                                <span class="text-xs font-bold {{ $porcentaje >= 100 ? 'text-green-600' : 'text-blue-600' }}">{{ $porcentaje }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2 mb-2">
                                                <div class="h-2 rounded-full {{ $porcentaje >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ min($porcentaje, 100) }}%"></div>
                                            </div>
                                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                                <span>Planificado: ${{ number_format($linea['planificado'], 2) }}</span>
                                                <span>Ejecutado: ${{ number_format($linea['ejecutado'], 2) }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 text-center py-4">Sin desglose disponible</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Actividades del Producto -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                            <div class="px-5 py-4 bg-gray-50 dark:bg-zinc-700/50 border-b border-gray-200 dark:border-zinc-700">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <x-icon name="clipboard-document-list" class="w-4 h-4 text-indigo-500" />
                                    Actividades del Producto ({{ $desembolsoDetalle->producto->actividades->count() }})
                                </h3>
                            </div>
                            <div class="divide-y divide-gray-100 dark:divide-zinc-700 max-h-64 overflow-y-auto">
                                @foreach($desembolsoDetalle->producto->actividades as $actividad)
                                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $actividad->nombre }}</span>
                                                    @php
                                                        $estadoClass = match($actividad->estado) {
                                                            'finalizado' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                            'en_curso' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                            'atrasado' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400',
                                                        };
                                                    @endphp
                                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $estadoClass }}">
                                                    {{ ucfirst(str_replace('_', ' ', $actividad->estado)) }}
                                                </span>
                                                </div>
                                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <x-icon name="tag" class="w-3 h-3" />
                                                    {{ $actividad->linea_presupuestaria }}
                                                </span>
                                                    <span class="flex items-center gap-1">
                                                    <x-icon name="calendar" class="w-3 h-3" />
                                                    {{ $actividad->fecha_fin?->format('d/m/Y') }}
                                                </span>
                                                </div>
                                            </div>
                                            <div class="text-right flex-shrink-0">
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">${{ number_format($actividad->monto, 2) }}</p>
                                                <div class="flex items-center gap-1 mt-1">
                                                    <div class="w-16 bg-gray-200 dark:bg-zinc-600 rounded-full h-1.5">
                                                        <div class="h-1.5 rounded-full {{ $actividad->progreso >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $actividad->progreso }}%"></div>
                                                    </div>
                                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $actividad->progreso }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Resumen y Estado -->
                    <div class="space-y-6">

                        <!-- Monto del Desembolso -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-5 border border-green-100 dark:border-green-800">
                            <p class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wider mb-2">Monto Solicitado</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">${{ number_format($desembolsoDetalle->monto_total, 2) }}</p>
                            @if($desembolsoDetalle->monto_aprobado && $desembolsoDetalle->monto_aprobado != $desembolsoDetalle->monto_total)
                                <div class="mt-3 pt-3 border-t border-green-200 dark:border-green-700">
                                    <p class="text-xs text-gray-500">Monto Aprobado</p>
                                    <p class="text-xl font-bold text-green-600">${{ number_format($desembolsoDetalle->monto_aprobado, 2) }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Información del Solicitante -->
                        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Solicitante</h4>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ $desembolsoDetalle->solicitante ? substr($desembolsoDetalle->solicitante->name, 0, 2) : 'NA' }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $desembolsoDetalle->solicitante?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $desembolsoDetalle->fecha_solicitud->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Info de Pago (si existe) -->
                        @if($desembolsoDetalle->estado === 'pagado')
                            <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-5">
                                <div class="flex items-center gap-2 mb-4">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                        <x-icon name="check" class="w-5 h-5 text-white" />
                                    </div>
                                    <h4 class="text-sm font-bold text-green-800 dark:text-green-300">Pago Realizado</h4>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">N° Transferencia</p>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ $desembolsoDetalle->numero_transferencia }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Banco</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $desembolsoDetalle->banco }}</p>
                                    </div>
                                    @if($desembolsoDetalle->cuenta_destino)
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Cuenta Destino</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $desembolsoDetalle->cuenta_destino }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Fecha de Pago</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $desembolsoDetalle->fecha_pago?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    @if($desembolsoDetalle->procesador)
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Procesado por</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $desembolsoDetalle->procesador->name }}</p>
                                        </div>
                                    @endif
                                </div>
                                @if($desembolsoDetalle->comprobante_path)
                                    <a href="{{ Storage::url($desembolsoDetalle->comprobante_path) }}" target="_blank" class="mt-4 flex items-center justify-center gap-2 w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                        <x-icon name="document-arrow-down" class="w-4 h-4" />
                                        Ver Comprobante
                                    </a>
                                @endif
                            </div>
                        @endif

                        <!-- Motivo Rechazo -->
                        @if($desembolsoDetalle->estado === 'rechazado')
                            <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl border border-red-200 dark:border-red-800 p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                        <x-icon name="x-mark" class="w-5 h-5 text-white" />
                                    </div>
                                    <h4 class="text-sm font-bold text-red-800 dark:text-red-300">Desembolso Rechazado</h4>
                                </div>
                                <p class="text-sm text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30 rounded-lg p-3">
                                    {{ $desembolsoDetalle->motivo_rechazo }}
                                </p>
                                @if($desembolsoDetalle->procesador)
                                    <p class="text-xs text-gray-500 mt-3">
                                        Rechazado por {{ $desembolsoDetalle->procesador->name }} el {{ $desembolsoDetalle->fecha_proceso?->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <!-- En Proceso -->
                        @if($desembolsoDetalle->estado === 'en_proceso')
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center animate-pulse">
                                        <x-icon name="arrow-path" class="w-5 h-5 text-white" />
                                    </div>
                                    <h4 class="text-sm font-bold text-blue-800 dark:text-blue-300">En Proceso</h4>
                                </div>
                                <p class="text-sm text-blue-700 dark:text-blue-400">
                                    Este desembolso está siendo procesado por el equipo de finanzas.
                                </p>
                                @if($desembolsoDetalle->procesador)
                                    <p class="text-xs text-gray-500 mt-3">
                                        Procesando: {{ $desembolsoDetalle->procesador->name }} desde {{ $desembolsoDetalle->fecha_proceso?->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        <!-- Observaciones -->
                        @if($desembolsoDetalle->observaciones)
                            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Observaciones</h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $desembolsoDetalle->observaciones }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer del Modal -->
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-zinc-700">
                    <p class="text-xs text-gray-500">
                        Última actualización: {{ $desembolsoDetalle->updated_at->format('d/m/Y H:i') }}
                    </p>
                    <div class="flex gap-2">
                        @if(in_array($desembolsoDetalle->estado, ['pendiente', 'en_proceso']))
                            <flux:button variant="danger" wire:click="abrirModalRechazo({{ $desembolsoDetalle->id }})" icon="x-circle">
                                Rechazar
                            </flux:button>
                            <flux:button variant="primary" wire:click="abrirModalPago({{ $desembolsoDetalle->id }})" icon="banknotes">
                                Procesar Pago
                            </flux:button>
                        @endif
                        <flux:button wire:click="cerrarDetalle">Cerrar</flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Modal Procesar Pago -->
    <flux:modal wire:model="mostrarModalPago" maxWidth="lg">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Procesar Pago</h2>

            <form wire:submit="procesarPago" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="montoAprobado"
                        label="Monto a Pagar (USD)"
                        type="number"
                        step="0.01"
                        required
                    />
                    <flux:input
                        wire:model="numeroTransferencia"
                        label="N° Transferencia/Cheque"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input
                        wire:model="banco"
                        label="Banco"
                        required
                    />
                    <flux:input
                        wire:model="cuentaDestino"
                        label="Cuenta Destino"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comprobante (PDF/Imagen)</label>
                    <input type="file" wire:model="comprobante" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept=".pdf,.jpg,.jpeg,.png">
                    @error('comprobante') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <flux:textarea
                    wire:model="observaciones"
                    label="Observaciones"
                    rows="2"
                />

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <flux:button variant="ghost" wire:click="cerrarModalPago">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">Confirmar Pago</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Modal Rechazo -->
    <flux:modal wire:model="mostrarModalRechazo" maxWidth="md">
        <div class="p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Rechazar Desembolso</h2>

            <form wire:submit="rechazarDesembolso" class="space-y-4">
                <flux:textarea
                    wire:model="motivoRechazo"
                    label="Motivo del Rechazo"
                    rows="4"
                    placeholder="Explique el motivo del rechazo..."
                    required
                />

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <flux:button variant="ghost" wire:click="cerrarModalRechazo">Cancelar</flux:button>
                    <flux:button type="submit" variant="danger" icon="x-circle">Confirmar Rechazo</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
