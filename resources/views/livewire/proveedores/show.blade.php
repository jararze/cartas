<?php

use App\Models\Proveedor;
use App\Models\Carta;
use Livewire\Volt\Component;

new class extends Component {
    public Proveedor $proveedor;

    public function mount(Proveedor $proveedor)
    {
        $this->proveedor = $proveedor;
    }

    public function with(): array
    {
        // Cargar cartas manualmente sin usar la relación
        $cartas = Carta::where('proveedor_id', $this->proveedor->id)
            ->with(['productos.actividades'])
            ->get();

        // Estadísticas
        $stats = [
            'total_cartas' => $cartas->count(),
            'cartas_activas' => $cartas->whereIn('estado', ['en_ejecucion', 'aceptada'])->count(),
            'cartas_finalizadas' => $cartas->where('estado', 'finalizada')->count(),
            'total_productos' => $cartas->sum(fn($c) => $c->productos->count()),
            'presupuesto_total' => $cartas->sum('monto_total'),
        ];

        // Agrupar cartas por estado
        $cartasPorEstado = [
            'activas' => $cartas->whereIn('estado', ['en_ejecucion', 'aceptada']),
            'finalizadas' => $cartas->where('estado', 'finalizada'),
            'otras' => $cartas->whereNotIn('estado', ['en_ejecucion', 'aceptada', 'finalizada']),
        ];

        return [
            'stats' => $stats,
            'cartas' => $cartas,
            'cartasPorEstado' => $cartasPorEstado,
        ];
    }

    public function getEstadoColor($estado)
    {
        return match($estado) {
            'borrador' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'enviada' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'vista' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
            'aceptada' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'rechazada' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'en_ejecucion' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'finalizada' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'cancelada' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}; ?>

<div title="Detalle del Proveedor">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('proveedores.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Proveedores</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">{{ $proveedor->nombre }}</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $proveedor->nombre }}</h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $proveedor->activo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $proveedor->activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    @if($proveedor->empresa)
                        <p class="text-lg text-gray-600 dark:text-gray-400">{{ $proveedor->empresa }}</p>
                    @endif
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('proveedores.edit', $proveedor) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                    <a href="{{ route('proveedores.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            <!-- Información de Contacto -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Email</h3>
                    <a href="mailto:{{ $proveedor->email }}" class="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ $proveedor->email }}
                    </a>
                </div>

                @if($proveedor->telefono)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Teléfono</h3>
                        <a href="tel:{{ $proveedor->telefono }}" class="flex items-center text-gray-900 dark:text-white hover:text-blue-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $proveedor->telefono }}
                        </a>
                    </div>
                @endif

                @if($proveedor->whatsapp)
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">WhatsApp</h3>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $proveedor->whatsapp) }}"
                           target="_blank"
                           class="flex items-center text-green-600 hover:text-green-800 dark:text-green-400">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            {{ $proveedor->whatsapp }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Contacto Principal -->
        @if($proveedor->contacto_principal || $proveedor->cargo)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Persona de Contacto</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($proveedor->contacto_principal)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</h3>
                            <p class="text-lg text-gray-900 dark:text-white">{{ $proveedor->contacto_principal }}</p>
                        </div>
                    @endif
                    @if($proveedor->cargo)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cargo</h3>
                            <p class="text-lg text-gray-900 dark:text-white">{{ $proveedor->cargo }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Especialidades -->
        @if($proveedor->especialidades)
            @php
                $especialidades = is_string($proveedor->especialidades)
                    ? json_decode($proveedor->especialidades, true)
                    : $proveedor->especialidades;
            @endphp
            @if(is_array($especialidades) && count($especialidades) > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Especialidades</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($especialidades as $especialidad)
                            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            {{ $especialidad }}
                        </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <!-- Notas -->
        @if($proveedor->notas)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Notas</h2>
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $proveedor->notas }}</p>
            </div>
        @endif

        <!-- KPIs de Cartas -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Cartas</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $stats['total_cartas'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Activas</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                    {{ $stats['cartas_activas'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Finalizadas</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                    {{ $stats['cartas_finalizadas'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Productos</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                    {{ $stats['total_productos'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Presupuesto Total</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    ${{ number_format($stats['presupuesto_total'], 2) }}
                </div>
            </div>
        </div>

        <!-- Cartas Asociadas -->
        @if($cartas->isNotEmpty())
            <!-- Cartas Activas -->
            @if($cartasPorEstado['activas']->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Cartas Activas ({{ $cartasPorEstado['activas']->count() }})
                    </h2>
                    <div class="space-y-3">
                        @foreach($cartasPorEstado['activas'] as $carta)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <a href="{{ route('cartas.show', $carta) }}" class="text-lg font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            {{ $carta->codigo }}
                                        </a>
                                        <h3 class="text-gray-900 dark:text-white mt-1">{{ $carta->nombre_proyecto }}</h3>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getEstadoColor($carta->estado) }}">
                                    {{ ucfirst(str_replace('_', ' ', $carta->estado)) }}
                                </span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Productos:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $carta->productos->count() }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Monto:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ number_format($carta->monto_total, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Inicio:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($carta->fecha_inicio)->format('d/m/Y') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Fin:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($carta->fecha_fin)->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Cartas Finalizadas -->
            @if($cartasPorEstado['finalizadas']->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Cartas Finalizadas ({{ $cartasPorEstado['finalizadas']->count() }})
                    </h2>
                    <div class="space-y-3">
                        @foreach($cartasPorEstado['finalizadas'] as $carta)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <a href="{{ route('cartas.show', $carta) }}" class="text-lg font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            {{ $carta->codigo }}
                                        </a>
                                        <h3 class="text-gray-900 dark:text-white mt-1">{{ $carta->nombre_proyecto }}</h3>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getEstadoColor($carta->estado) }}">
                                    Finalizada
                                </span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Productos:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ $carta->productos->count() }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Monto:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">${{ number_format($carta->monto_total, 2) }}</span>
                                    </div>
                                    <div class="md:col-span-2">
                                        <span class="text-gray-500 dark:text-gray-400">Período:</span>
                                        <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($carta->fecha_inicio)->format('d/m/Y') }} -
                                        {{ \Carbon\Carbon::parse($carta->fecha_fin)->format('d/m/Y') }}
                                    </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Otras Cartas -->
            @if($cartasPorEstado['otras']->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                        Otras Cartas ({{ $cartasPorEstado['otras']->count() }})
                    </h2>
                    <div class="space-y-3">
                        @foreach($cartasPorEstado['otras'] as $carta)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <a href="{{ route('cartas.show', $carta) }}" class="text-lg font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            {{ $carta->codigo }}
                                        </a>
                                        <h3 class="text-gray-900 dark:text-white mt-1">{{ $carta->nombre_proyecto }}</h3>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getEstadoColor($carta->estado) }}">
                                    {{ ucfirst(str_replace('_', ' ', $carta->estado)) }}
                                </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Este proveedor no tiene cartas asociadas</p>
            </div>
        @endif
    </div>
</div>
