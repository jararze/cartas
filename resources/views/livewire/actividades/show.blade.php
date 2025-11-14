<?php

use App\Models\Actividad;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public Actividad $actividad;

    public function mount(Actividad $actividad)
    {
        $this->actividad = $actividad->load([
            'producto.carta.proveedor',
            'responsable',
            'seguimientos.registradoPor',
            'seguimientos.revisadoPor'
        ]);
    }

    public function with(): array
    {
        $seguimientos = $this->actividad->seguimientos()
            ->orderBy('created_at', 'desc')
            ->get();

        // Estadísticas
        $stats = [
            'progreso_actual' => $this->actividad->progreso,
            'presupuesto' => $this->actividad->monto,
            'gasto_acumulado' => $this->actividad->gasto_acumulado,
            'saldo_disponible' => $this->actividad->monto - $this->actividad->gasto_acumulado,
            'porcentaje_ejecucion' => $this->actividad->monto > 0
                ? ($this->actividad->gasto_acumulado / $this->actividad->monto) * 100
                : 0,
            'total_seguimientos' => $seguimientos->count(),
            'dias_transcurridos' => Carbon::parse($this->actividad->fecha_inicio)->diffInDays(Carbon::now()),
            'dias_restantes' => Carbon::parse($this->actividad->fecha_fin)->diffInDays(Carbon::now(), false),
            'dias_totales' => Carbon::parse($this->actividad->fecha_inicio)->diffInDays(Carbon::parse($this->actividad->fecha_fin)),
        ];

        $stats['porcentaje_tiempo_transcurrido'] = $stats['dias_totales'] > 0
            ? ($stats['dias_transcurridos'] / $stats['dias_totales']) * 100
            : 0;

        // Alertas
        $alertas = [];

        if ($stats['porcentaje_ejecucion'] > 100) {
            $alertas[] = [
                'tipo' => 'error',
                'mensaje' => 'Presupuesto excedido en ' . number_format($stats['porcentaje_ejecucion'] - 100, 1) . '%',
            ];
        } elseif ($stats['porcentaje_ejecucion'] > 90) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => 'Presupuesto casi agotado (' . number_format($stats['porcentaje_ejecucion'], 1) . '%)',
            ];
        }

        if ($this->actividad->estado === 'atrasado') {
            $alertas[] = [
                'tipo' => 'error',
                'mensaje' => 'Actividad atrasada',
            ];
        } elseif ($stats['dias_restantes'] >= 0 && $stats['dias_restantes'] <= 7 && $this->actividad->estado !== 'finalizado') {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => 'Vence en ' . $stats['dias_restantes'] . ' días',
            ];
        }

        if ($stats['porcentaje_tiempo_transcurrido'] > $this->actividad->progreso) {
            $diferencia = $stats['porcentaje_tiempo_transcurrido'] - $this->actividad->progreso;
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => 'El progreso está ' . number_format($diferencia, 1) . '% por debajo del tiempo transcurrido',
            ];
        }

        // Línea de tiempo de seguimientos
        $lineaTiempo = $seguimientos->map(function($seg) {
            return [
                'fecha' => $seg->created_at,
                'progreso_anterior' => $seg->progreso_anterior,
                'progreso_nuevo' => $seg->progreso_nuevo,
                'monto_gastado' => $seg->monto_gastado,
                'registrado_por' => $seg->registradoPor->name,
                'descripcion' => $seg->descripcion_avance,
            ];
        });

        return [
            'stats' => $stats,
            'alertas' => $alertas,
            'seguimientos' => $seguimientos,
            'lineaTiempo' => $lineaTiempo,
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

<div title="Detalle de Actividad">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('actividades.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Actividades</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">{{ $actividad->nombre }}</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $actividad->nombre }}</h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $this->getEstadoColor($actividad->estado) }}">
                            {{ ucfirst($actividad->estado) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $this->getPrioridadColor($actividad->prioridad) }}">
                            {{ ucfirst($actividad->prioridad) }}
                        </span>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $actividad->descripcion }}</p>
                </div>

                <a href="{{ route('actividades.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Producto:</span>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $actividad->producto->nombre }}</div>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Carta:</span>
                    <a href="{{ route('cartas.show', $actividad->producto->carta) }}" class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        {{ $actividad->producto->carta->codigo }}
                    </a>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Línea Presupuestaria:</span>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $actividad->linea_presupuestaria }}</div>
                </div>
                @if($actividad->responsable)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Responsable:</span>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $actividad->responsable->name }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Alertas -->
        @if(count($alertas) > 0)
            <div class="space-y-2 mb-6">
                @foreach($alertas as $alerta)
                    <div class="flex items-center p-4 rounded-lg {{ $alerta['tipo'] === 'error' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' }}">
                        <svg class="w-5 h-5 {{ $alerta['tipo'] === 'error' ? 'text-red-600' : 'text-yellow-600' }} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="{{ $alerta['tipo'] === 'error' ? 'text-red-800 dark:text-red-300' : 'text-yellow-800 dark:text-yellow-300' }} font-medium">
                        {{ $alerta['mensaje'] }}
                    </span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Progreso Actual</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                    {{ number_format($stats['progreso_actual'], 1) }}%
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $stats['progreso_actual'] }}%"></div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Presupuesto</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    ${{ number_format($stats['presupuesto'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Línea: {{ $actividad->linea_presupuestaria }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Gastado</div>
                <div class="text-2xl font-bold {{ $stats['porcentaje_ejecucion'] > 100 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} mt-2">
                    ${{ number_format($stats['gasto_acumulado'], 2) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ number_format($stats['porcentaje_ejecucion'], 1) }}% ejecutado
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Saldo Disponible</div>
                <div class="text-2xl font-bold {{ $stats['saldo_disponible'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-2">
                    ${{ number_format($stats['saldo_disponible'], 2) }}
                </div>
            </div>
        </div>

        <!-- Fechas y Tiempo -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Cronograma</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($actividad->fecha_inicio)->format('d/m/Y') }}
                    </div>
                    @if($actividad->fecha_inicio_real)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Real: {{ \Carbon\Carbon::parse($actividad->fecha_inicio_real)->format('d/m/Y') }}
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Fecha Fin</div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($actividad->fecha_fin)->format('d/m/Y') }}
                    </div>
                    @if($actividad->fecha_fin_real)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Real: {{ \Carbon\Carbon::parse($actividad->fecha_fin_real)->format('d/m/Y') }}
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Días Restantes</div>
                    <div class="text-lg font-semibold {{ $stats['dias_restantes'] < 0 ? 'text-red-600' : ($stats['dias_restantes'] <= 7 ? 'text-orange-600' : 'text-gray-900 dark:text-white') }}">
                        {{ $stats['dias_restantes'] < 0 ? 'Vencida' : $stats['dias_restantes'] . ' días' }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $stats['dias_transcurridos'] }} de {{ $stats['dias_totales'] }} días transcurridos
                    </div>
                </div>
            </div>

            <!-- Barra de progreso de tiempo -->
            <div class="mt-4">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <span>Tiempo transcurrido</span>
                    <span>{{ number_format($stats['porcentaje_tiempo_transcurrido'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full" style="width: {{ min($stats['porcentaje_tiempo_transcurrido'], 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @if($actividad->observaciones)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Observaciones</h2>
                    <p class="text-gray-700 dark:text-gray-300">{{ $actividad->observaciones }}</p>
                </div>
            @endif

            @if($actividad->dificultades)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Dificultades</h2>
                    <p class="text-gray-700 dark:text-gray-300">{{ $actividad->dificultades }}</p>
                </div>
            @endif

            @if($actividad->proximos_pasos)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Próximos Pasos</h2>
                    <p class="text-gray-700 dark:text-gray-300">{{ $actividad->proximos_pasos }}</p>
                </div>
            @endif
        </div>

        <!-- Historial de Seguimientos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Historial de Seguimientos ({{ $stats['total_seguimientos'] }})
                </h2>
                <a href="{{ route('actividades.historial', $actividad) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Registrar Seguimiento
                </a>
            </div>

            @if($seguimientos->isEmpty())
                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p>No hay seguimientos registrados</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($seguimientos as $seguimiento)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $seguimiento->registradoPor->name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $seguimiento->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Progreso:
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ number_format($seguimiento->progreso_anterior, 1) }}%
                                            →
                                            {{ number_format($seguimiento->progreso_nuevo, 1) }}%
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Gasto:
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            ${{ number_format($seguimiento->monto_gastado, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded p-3 mb-2">
                                <p class="text-gray-700 dark:text-gray-300">{{ $seguimiento->descripcion_avance }}</p>
                            </div>

                            @if($seguimiento->logros)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400">Logros:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $seguimiento->logros }}</p>
                                </div>
                            @endif

                            @if($seguimiento->dificultades)
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-red-600 dark:text-red-400">Dificultades:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $seguimiento->dificultades }}</p>
                                </div>
                            @endif

                            @if($seguimiento->proximos_pasos)
                                <div>
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">Próximos Pasos:</span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $seguimiento->proximos_pasos }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
