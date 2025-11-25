<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">

        {{-- Header con Título y Fecha --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">Dashboard</h1>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                    Bienvenido de nuevo, {{ auth()->user()->name }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-500">{{ now()->format('H:i') }} hrs</p>
            </div>
        </div>

        {{-- KPIs Cards Principales - MODIFICADO: "Total Cartas" en lugar de "Total Actividades" --}}
        <div class="grid gap-4 md:grid-cols-4">
            {{-- Presupuesto Total --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-1 rounded-full">
                        +{{ number_format($stats['ejecucion_presupuestaria'], 1) }}%
                    </span>
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Presupuesto Total</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">${{ number_format($stats['presupuesto_total'], 0) }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">{{ $stats['cartas_activas'] }} cartas activas</p>
            </div>

            {{-- Total Cartas - MODIFICADO --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-full">
                        {{ $stats['cartas_activas'] }} activas
                    </span>
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Total Cartas</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['cartas_total'] }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">{{ $stats['cartas_finalizadas'] }} finalizadas</p>
            </div>

            {{-- Progreso General --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Progreso General</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['progreso_general'] }}%</p>
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 mt-3">
                    <div class="bg-purple-600 h-2 rounded-full transition-all duration-500" style="width: {{ $stats['progreso_general'] }}%"></div>
                </div>
            </div>

            {{-- Actividades Atrasadas --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    @if($stats['actividades_atrasadas'] > 0)
                        <span class="text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2 py-1 rounded-full animate-pulse">
                            ¡Atención!
                        </span>
                    @endif
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Actividades Atrasadas</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['actividades_atrasadas'] }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">
                    @if($stats['actividades_atrasadas'] > 0)
                        Requiere acción inmediata
                    @else
                        Todo bajo control
                    @endif
                </p>
            </div>
        </div>

        {{-- Gráficos Principales --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Gráfico de Progreso por Carta --}}
            <div class="lg:col-span-2 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-neutral-900 dark:text-white">Progreso por Carta</h3>
                    <select class="text-xs border border-neutral-300 dark:border-neutral-600 rounded-lg px-3 py-1.5 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                        <option>Últimos 30 días</option>
                        <option>Últimos 60 días</option>
                        <option>Último año</option>
                    </select>
                </div>
                <div style="height: 300px;">
                    <canvas id="chartProgresoCarta"></canvas>
                </div>
            </div>

            {{-- Gráfico de Actividades por Estado --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-white mb-4">Actividades por Estado</h3>
                <div style="height: 300px;">
                    <canvas id="chartActividadesEstado"></canvas>
                </div>

                {{-- Leyenda personalizada --}}
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-neutral-700 dark:text-neutral-300">Completadas</span>
                        </div>
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ $stats['actividades_completadas'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-neutral-700 dark:text-neutral-300">En Curso</span>
                        </div>
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ $stats['actividades_en_curso'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                            <span class="text-neutral-700 dark:text-neutral-300">Pendientes</span>
                        </div>
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ $stats['actividades_pendientes'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                            <span class="text-neutral-700 dark:text-neutral-300">Atrasadas</span>
                        </div>
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ $stats['actividades_atrasadas'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ejecución Presupuestaria y Notificaciones --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Ejecución Presupuestaria --}}
            <div class="lg:col-span-2 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-white mb-6">Ejecución Presupuestaria</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div style="height: 200px;">
                        <canvas id="chartPresupuesto"></canvas>
                    </div>
                    <div class="flex flex-col justify-center space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-neutral-600 dark:text-neutral-400">Ejecutado</span>
                                <span class="font-semibold text-green-600 dark:text-green-400">${{ number_format($stats['gasto_total'], 0) }}</span>
                            </div>
                            <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                                <div class="bg-green-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $stats['ejecucion_presupuestaria'] }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-neutral-600 dark:text-neutral-400">Disponible</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400">${{ number_format($stats['saldo_disponible'], 0) }}</span>
                            </div>
                            <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ 100 - $stats['ejecucion_presupuestaria'] }}%"></div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-1">Porcentaje de Ejecución</p>
                            <p class="text-4xl font-bold text-neutral-900 dark:text-white">{{ $stats['ejecucion_presupuestaria'] }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Notificaciones --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-white mb-4">Notificaciones</h3>
                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                    @forelse($notificaciones as $notificacion)
                        <div class="p-3 rounded-lg border-l-4
                            @if($notificacion['tipo'] === 'error') bg-red-50 dark:bg-red-900/20 border-red-500
                            @elseif($notificacion['tipo'] === 'warning') bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500
                            @else bg-green-50 dark:bg-green-900/20 border-green-500
                            @endif
                        ">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    @if($notificacion['tipo'] === 'error')
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @elseif($notificacion['tipo'] === 'warning')
                                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium
                                        @if($notificacion['tipo'] === 'error') text-red-800 dark:text-red-300
                                        @elseif($notificacion['tipo'] === 'warning') text-yellow-800 dark:text-yellow-300
                                        @else text-green-800 dark:text-green-300
                                        @endif
                                    ">
                                        {{ $notificacion['titulo'] }}
                                    </p>
                                    <p class="text-xs
                                        @if($notificacion['tipo'] === 'error') text-red-600 dark:text-red-400
                                        @elseif($notificacion['tipo'] === 'warning') text-yellow-600 dark:text-yellow-400
                                        @else text-green-600 dark:text-green-400
                                        @endif
                                    ">
                                        {{ $notificacion['mensaje'] }}
                                    </p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                                        {{ \Carbon\Carbon::parse($notificacion['fecha'])->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-neutral-300 dark:text-neutral-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p class="text-neutral-500 dark:text-neutral-400">No hay notificaciones</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Cartas Recientes - CON BÚSQUEDA Y PAGINACIÓN --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-neutral-900 dark:text-white">Cartas Recientes</h3>
                    <a href="{{ route('cartas.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold flex items-center gap-1">
                        Ver todas
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>

                {{-- Buscador --}}
                <form method="GET" action="{{ route('dashboard') }}" class="relative">
                    <input
                        type="text"
                        name="busqueda"
                        value="{{ $busqueda }}"
                        placeholder="Buscar por código, proyecto o proveedor..."
                        class="w-full pl-10 pr-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded-lg bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white placeholder-neutral-500 dark:placeholder-neutral-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <svg class="w-5 h-5 absolute left-3 top-2.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    @if($busqueda)
                        <a href="{{ route('dashboard') }}" class="absolute right-3 top-2.5 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </a>
                    @endif
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Proyecto</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Proveedor</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Progreso</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Presupuesto</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider">Fecha Fin</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($cartasRecientes as $carta)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors cursor-pointer" onclick="window.location='{{ route('cartas.show', $carta['id']) }}'">
                            <td class="px-6 py-4">
                                <div class="font-medium text-neutral-900 dark:text-white">{{ $carta['codigo'] }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-neutral-900 dark:text-white">{{ Str::limit($carta['nombre_proyecto'], 40) }}</div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ $carta['actividades_completadas'] }}/{{ $carta['actividades_total'] }} actividades</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-neutral-900 dark:text-white">{{ $carta['proveedor'] }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($carta['estado'] === 'finalizada') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                    @elseif($carta['estado'] === 'en_ejecucion') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400
                                    @elseif($carta['estado'] === 'aceptada') bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-400
                                    @elseif($carta['estado'] === 'borrador') bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                    @else bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $carta['estado'])) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 max-w-[80px]">
                                        <div class="
                                            @if($carta['progreso'] >= 75) bg-green-600
                                            @elseif($carta['progreso'] >= 50) bg-blue-600
                                            @elseif($carta['progreso'] >= 25) bg-yellow-600
                                            @else bg-red-600
                                            @endif
                                            h-2 rounded-full transition-all duration-500
                                        " style="width: {{ $carta['progreso'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">{{ $carta['progreso'] }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-neutral-900 dark:text-white">${{ number_format($carta['presupuesto'], 0) }}</div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">Gastado: ${{ number_format($carta['gasto'], 0) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-neutral-900 dark:text-white">{{ \Carbon\Carbon::parse($carta['fecha_fin'])->format('d/m/Y') }}</div>
                                @if($carta['dias_restantes'] !== null)
                                    <div class="text-xs
                                        @if($carta['dias_restantes'] < 0) text-red-600 dark:text-red-400
                                        @elseif($carta['dias_restantes'] <= 7) text-yellow-600 dark:text-yellow-400
                                        @else text-neutral-500 dark:text-neutral-400
                                        @endif
                                    ">
                                        @if($carta['dias_restantes'] < 0)
                                            Vencida hace {{ abs($carta['dias_restantes']) }} días
                                        @elseif($carta['dias_restantes'] == 0)
                                            Vence hoy
                                        @else
                                            {{ $carta['dias_restantes'] }} días restantes
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto text-neutral-300 dark:text-neutral-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-neutral-500 dark:text-neutral-400">
                                    @if($busqueda)
                                        No se encontraron cartas con "{{ $busqueda }}"
                                    @else
                                        No hay cartas recientes
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($cartasRecientes->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                    {{ $cartasRecientes->appends(['busqueda' => $busqueda])->links() }}
                </div>
            @endif
        </div>

    </div>

    <script>
        window.addEventListener('load', function() {
            const chartData = @json($chartData);
            const stats = @json($stats);

            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#d4d4d8' : '#3f3f46';
            const gridColor = isDarkMode ? '#3f3f46' : '#e5e7eb';

            // Progreso por Carta (Barras Horizontales)
            const ctxCarta = document.getElementById('chartProgresoCarta');
            if (ctxCarta && typeof Chart !== 'undefined') {
                new Chart(ctxCarta, {
                    type: 'bar',
                    data: {
                        labels: chartData.cartas_progreso.map(c => c.codigo),
                        datasets: [{
                            label: 'Progreso (%)',
                            data: chartData.cartas_progreso.map(c => c.progreso),
                            backgroundColor: 'rgba(59, 130, 246, 0.8)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.x + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100,
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { color: textColor }
                            }
                        }
                    }
                });
            }

            // Actividades por Estado (Dona)
            const ctxEstado = document.getElementById('chartActividadesEstado');
            if (ctxEstado && typeof Chart !== 'undefined') {
                new Chart(ctxEstado, {
                    type: 'doughnut',
                    data: {
                        labels: ['Completadas', 'En Curso', 'Pendientes', 'Atrasadas'],
                        datasets: [{
                            data: [
                                chartData.actividades_por_estado.completadas,
                                chartData.actividades_por_estado.en_curso,
                                chartData.actividades_por_estado.pendientes,
                                chartData.actividades_por_estado.atrasadas
                            ],
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(156, 163, 175, 0.8)',
                                'rgba(251, 146, 60, 0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: isDarkMode ? '#18181b' : '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Presupuesto (Pastel)
            const ctxPresupuesto = document.getElementById('chartPresupuesto');
            if (ctxPresupuesto && typeof Chart !== 'undefined') {
                new Chart(ctxPresupuesto, {
                    type: 'pie',
                    data: {
                        labels: ['Ejecutado', 'Disponible'],
                        datasets: [{
                            data: [
                                stats.gasto_total,
                                stats.saldo_disponible
                            ],
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(59, 130, 246, 0.8)'
                            ],
                            borderWidth: 2,
                            borderColor: isDarkMode ? '#18181b' : '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: { color: textColor }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = stats.presupuesto_total;
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return context.label + ': $' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.app>
