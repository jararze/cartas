<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">Mi Dashboard</h1>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mt-1">
                    Bienvenido, {{ auth()->user()->name }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
            </div>
        </div>

        {{-- KPIs del Proveedor --}}
        <div class="grid gap-4 md:grid-cols-4">
            {{-- Invitaciones Pendientes --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @if($stats['invitaciones_pendientes'] > 0)
                        <span class="text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2 py-1 rounded-full animate-pulse">
                            ¡Revisar!
                        </span>
                    @endif
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Invitaciones Pendientes</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['invitaciones_pendientes'] }}</p>
                <a href="{{ route('proveedores.invitaciones') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                    Ver todas →
                </a>
            </div>

            {{-- Cartas Activas --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Cartas Activas</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['cartas_activas'] }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">{{ $stats['cartas_finalizadas'] }} finalizadas</p>
            </div>

            {{-- Progreso General --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Progreso General</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['progreso_general'] }}%</p>
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 mt-3">
                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $stats['progreso_general'] }}%"></div>
                </div>
            </div>

            {{-- Actividades --}}
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-1">Actividades</h3>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['actividades_completadas'] }}/{{ $stats['actividades_total'] }}</p>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2">{{ $stats['actividades_en_curso'] }} en curso</p>
            </div>
        </div>

        {{-- Invitaciones Pendientes (Destacado) --}}
        @if($invitacionesPendientes->count() > 0)
            <div class="rounded-xl border-2 border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/10 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="text-lg font-bold text-orange-900 dark:text-orange-300">
                        Tienes {{ $invitacionesPendientes->count() }} invitación(es) pendiente(s) de respuesta
                    </h3>
                </div>

                <div class="space-y-3">
                    @foreach($invitacionesPendientes->take(3) as $invitacion)
                        <div class="bg-white dark:bg-neutral-800 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="font-semibold text-neutral-900 dark:text-white">{{ $invitacion['codigo'] }}</h4>
                                <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $invitacion['nombre_proyecto'] }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-500 mt-1">
                                    Enviada hace {{ $invitacion['dias_sin_responder'] }} días
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('cartas.view', $invitacion['codigo']) }}"
                                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                                    Ver Detalles
                                </a>
                            </div>
                        </div>
                    @endforeach

                    @if($invitacionesPendientes->count() > 3)
                        <a href="{{ route('proveedores.invitaciones') }}"
                           class="block text-center text-orange-600 dark:text-orange-400 hover:underline font-medium">
                            Ver todas las invitaciones ({{ $invitacionesPendientes->count() }}) →
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Mis Cartas --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 overflow-hidden">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
                <h3 class="text-lg font-bold text-neutral-900 dark:text-white mb-4">Mis Cartas</h3>

                {{-- Buscador --}}
                <form method="GET" action="{{ route('dashboard') }}" class="relative">
                    <input
                        type="text"
                        name="busqueda"
                        value="{{ $busqueda }}"
                        placeholder="Buscar por código o proyecto..."
                        class="w-full pl-10 pr-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded-lg bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white"
                    >
                    <svg class="w-5 h-5 absolute left-3 top-2.5 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase">Proyecto</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase">Progreso</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-neutral-600 dark:text-neutral-400 uppercase">Fecha Fin</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($cartasRecientes as $carta)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer" onclick="window.location='{{ route('cartas.view', $carta['codigo']) }}'">
                            <td class="px-6 py-4 font-medium text-neutral-900 dark:text-white">{{ $carta['codigo'] }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-neutral-900 dark:text-white">{{ Str::limit($carta['nombre_proyecto'], 40) }}</div>
                                <div class="text-sm text-neutral-500">{{ $carta['actividades_completadas'] }}/{{ $carta['actividades_total'] }} actividades</div>
                            </td>
                            <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                                        @if($carta['estado'] === 'aceptada') bg-green-100 text-green-800
                                        @elseif($carta['estado'] === 'en_ejecucion') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $carta['estado'])) }}
                                    </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-neutral-200 dark:bg-neutral-700 rounded-full h-2 max-w-[80px]">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $carta['progreso'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-semibold">{{ $carta['progreso'] }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ \Carbon\Carbon::parse($carta['fecha_fin'])->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-neutral-500">No tienes cartas asignadas</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($cartasRecientes->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                    {{ $cartasRecientes->appends(['busqueda' => $busqueda])->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.app>
