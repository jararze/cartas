<?php
use App\Models\Actividad;
use Livewire\Volt\Component;

new class extends Component {
    public Actividad $actividad;
    public $totalSeguimientos;
    public $progresoInicial;
    public $progresoActual;
    public $incrementoTotal;

    public function mount(Actividad $actividad): void
    {
        $this->actividad = $actividad->load([
            'producto.carta',
            'seguimientos' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        $this->totalSeguimientos = $this->actividad->seguimientos->count();
        $this->progresoInicial = $this->actividad->seguimientos->last()?->progreso_anterior ?? 0;
        $this->progresoActual = $this->actividad->progreso;
        $this->incrementoTotal = $this->progresoActual - $this->progresoInicial;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb y Header --}}
        <div class="mb-8">
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('cartas.show', $actividad->producto->carta->id) }}"
                           class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            {{ $actividad->producto->carta->codigo }}
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400">{{ $actividad->producto->nombre }}</span>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Historial</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Historial de Seguimientos
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $actividad->nombre }}</p>
                </div>
                <a href="{{ route('actividades.seguimiento', $actividad->id) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all border-2 border-blue-500 dark:border-blue-600 shadow-sm font-semibold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo Seguimiento
                </a>
            </div>
        </div>

        {{-- Información de Contexto --}}
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Carta</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $actividad->producto->carta->codigo }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $actividad->producto->carta->nombre_proyecto }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Producto</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $actividad->producto->nombre }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Línea Presupuestaria</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $actividad->linea_presupuestaria }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Tarjetas de Resumen --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            {{-- Progreso Actual --}}
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Progreso Actual</h3>
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <p class="text-4xl font-bold text-purple-600 dark:text-purple-400">
                    {{ number_format($progresoActual, 1) }}%
                </p>
                @if($incrementoTotal > 0)
                    <p class="text-sm text-green-600 dark:text-green-400 mt-2 font-medium">
                        +{{ number_format($incrementoTotal, 1) }}% desde inicio
                    </p>
                @endif
            </div>

            {{-- Presupuesto --}}
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Presupuesto</h3>
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($actividad->monto, 2) }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Total asignado</p>
            </div>

            {{-- Ejecutado --}}
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Ejecutado</h3>
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    ${{ number_format($actividad->gasto_acumulado, 2) }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    {{ $actividad->monto > 0 ? number_format(($actividad->gasto_acumulado / $actividad->monto) * 100, 1) : 0 }}% del presupuesto
                </p>
            </div>

            {{-- Saldo --}}
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">Saldo</h3>
                    <svg class="w-10 h-10 {{ ($actividad->monto - $actividad->gasto_acumulado) < 0 ? 'text-red-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold {{ ($actividad->monto - $actividad->gasto_acumulado) < 0 ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                    ${{ number_format(abs($actividad->monto - $actividad->gasto_acumulado), 2) }}
                </p>
                @if(($actividad->monto - $actividad->gasto_acumulado) < 0)
                    <p class="text-sm text-red-600 dark:text-red-400 mt-2 font-medium">⚠️ Sobregiro</p>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Disponible</p>
                @endif
            </div>
        </div>

        {{-- Timeline de Seguimientos --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 px-8 py-6 border-b-2 border-slate-200 dark:border-slate-700">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Línea de Tiempo
                    <span class="text-lg text-gray-500 dark:text-gray-400">({{ $totalSeguimientos }} registros)</span>
                </h3>
            </div>

            @if($actividad->seguimientos->count() > 0)
                <div class="p-8">
                    <div class="space-y-8">
                        @foreach($actividad->seguimientos as $index => $seguimiento)
                            <div class="relative border-l-4
                                @if($seguimiento->progreso_nuevo >= 100) border-green-500
                                @elseif($seguimiento->progreso_nuevo >= 50) border-blue-500
                                @else border-yellow-500
                                @endif
                                pl-10">

                                {{-- Indicador circular con número --}}
                                <div class="absolute -left-6 top-0 w-12 h-12 rounded-full border-4 border-white dark:border-slate-800 flex items-center justify-center
                                    @if($seguimiento->progreso_nuevo >= 100) bg-green-500
                                    @elseif($seguimiento->progreso_nuevo >= 50) bg-blue-500
                                    @else bg-yellow-500
                                    @endif">
                                    <span class="text-white text-sm font-bold">{{ $actividad->seguimientos->count() - $index }}</span>
                                </div>

                                <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl border-2 border-slate-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-md transition-all">
                                    {{-- Header --}}
                                    <div class="flex items-start justify-between mb-5">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <p class="text-xl font-bold text-gray-900 dark:text-white">
                                                    {{ $seguimiento->responsable_nombre ?? 'Sin responsable' }}
                                                </p>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $seguimiento->created_at->format('d/m/Y H:i') }}
                                                </span>
                                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                                    ({{ $seguimiento->created_at->diffForHumans() }})
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex gap-3">
                                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold
                                                @if($seguimiento->progreso_nuevo >= 100) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($seguimiento->progreso_nuevo >= 50) bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @endif">
                                                {{ number_format($seguimiento->progreso_anterior, 1) }}% → {{ number_format($seguimiento->progreso_nuevo, 1) }}%
                                            </span>
                                            <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-sm font-bold">
                                                ${{ number_format($seguimiento->monto_gastado, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Métricas --}}
                                    <div class="grid grid-cols-4 gap-4 mb-5 p-4 bg-white dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Incremento</p>
                                            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                                +{{ number_format($seguimiento->progreso_nuevo - $seguimiento->progreso_anterior, 1) }}%
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Gasto Acumulado</p>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white">
                                                ${{ number_format($seguimiento->gasto_acumulado_nuevo, 2) }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Saldo Después</p>
                                            <p class="text-lg font-bold {{ ($actividad->monto - $seguimiento->gasto_acumulado_nuevo) < 0 ? 'text-red-600' : 'text-blue-600' }}">
                                                ${{ number_format(abs($actividad->monto - $seguimiento->gasto_acumulado_nuevo), 2) }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Eficiencia</p>
                                            <p class="text-lg font-bold {{ $seguimiento->indice_eficiencia >= 1 ? 'text-green-600' : 'text-yellow-600' }}">
                                                {{ number_format($seguimiento->indice_eficiencia ?? 0, 2) }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- Descripción --}}
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                                <span class="text-lg">📝</span> Descripción del Avance
                                            </p>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 p-4 rounded-lg border border-slate-200 dark:border-slate-600 leading-relaxed">
                                                {{ $seguimiento->descripcion_avance }}
                                            </p>
                                        </div>

                                        @if($seguimiento->logros)
                                            <div>
                                                <p class="text-sm font-bold text-green-700 dark:text-green-300 mb-2 flex items-center gap-2">
                                                    <span class="text-lg">✅</span> Logros Alcanzados
                                                </p>
                                                <p class="text-sm text-gray-700 dark:text-gray-300 bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800 leading-relaxed">
                                                    {{ $seguimiento->logros }}
                                                </p>
                                            </div>
                                        @endif

                                        @if($seguimiento->dificultades)
                                            <div>
                                                <p class="text-sm font-bold text-orange-700 dark:text-orange-300 mb-2 flex items-center gap-2">
                                                    <span class="text-lg">⚠️</span> Dificultades Encontradas
                                                </p>
                                                <p class="text-sm text-gray-700 dark:text-gray-300 bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-200 dark:border-orange-800 leading-relaxed">
                                                    {{ $seguimiento->dificultades }}
                                                </p>
                                            </div>
                                        @endif

                                        @if($seguimiento->proximos_pasos)
                                            <div>
                                                <p class="text-sm font-bold text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                                                    <span class="text-lg">🎯</span> Próximos Pasos
                                                </p>
                                                <p class="text-sm text-gray-700 dark:text-gray-300 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800 leading-relaxed">
                                                    {{ $seguimiento->proximos_pasos }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Alertas y Tags --}}
                                    <div class="mt-5 flex flex-wrap gap-2">
                                        @if($seguimiento->nivel_riesgo && $seguimiento->nivel_riesgo !== 'bajo')
                                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                                                @if($seguimiento->nivel_riesgo === 'critico') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @elseif($seguimiento->nivel_riesgo === 'alto') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @endif">
                                                ⚠️ Riesgo: {{ ucfirst($seguimiento->nivel_riesgo) }}
                                            </span>
                                        @endif

                                        @if(($actividad->monto - $seguimiento->gasto_acumulado_nuevo) < 0)
                                            <span class="inline-flex items-center px-4 py-2 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-sm font-semibold">
                                                🚨 Sobrepresupuesto
                                            </span>
                                        @endif

                                        @if($seguimiento->etiquetas && is_array($seguimiento->etiquetas))
                                            @foreach($seguimiento->etiquetas as $etiqueta)
                                                <span class="inline-flex items-center px-3 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium">
                                                    #{{ $etiqueta }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Estado vacío --}}
                <div class="p-16 text-center">
                    <svg class="w-24 h-24 mx-auto text-gray-300 dark:text-gray-600 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        No hay seguimientos registrados
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg">
                        Esta actividad aún no tiene registros de seguimiento.
                    </p>
                    <a href="{{ route('actividades.seguimiento', $actividad->id) }}"
                       class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all border-2 border-blue-500 dark:border-blue-600 shadow-sm font-bold text-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Registrar Primer Seguimiento
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
