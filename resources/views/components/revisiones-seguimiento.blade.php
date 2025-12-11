@props(['seguimiento', 'puedeRevisar' => false, 'puedeResponder' => false])

<div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-600">
    {{-- Header de Revisiones --}}
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Revisiones FAO
            @if($seguimiento->revisiones->where('estado', 'pendiente')->count() > 0)
                <span class="px-2 py-0.5 text-xs font-bold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                    {{ $seguimiento->revisiones->where('estado', 'pendiente')->count() }} pendientes
                </span>
            @endif
            @if($seguimiento->estaAprobado())
                <span class="px-2 py-0.5 text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                    ✓ Aprobado
                </span>
            @endif
        </h4>

        @if($puedeRevisar)
            <button
                wire:click="abrirModalRevision({{ $seguimiento->id }})"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Agregar Revisión
            </button>
        @endif
    </div>

    {{-- Lista de Revisiones --}}
    @if($seguimiento->revisiones->isNotEmpty())
        <div class="space-y-3">
            @foreach($seguimiento->revisiones as $revision)
                <div class="bg-{{ $revision->tipo_color }}-50 dark:bg-{{ $revision->tipo_color }}-900/20 border border-{{ $revision->tipo_color }}-200 dark:border-{{ $revision->tipo_color }}-800 rounded-lg p-4">
                    {{-- Header de la revisión --}}
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $revision->tipo_icono }}</span>
                            <span class="font-semibold text-{{ $revision->tipo_color }}-800 dark:text-{{ $revision->tipo_color }}-200">
                                {{ $revision->tipo_texto }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                por {{ $revision->revisor->name ?? 'Usuario' }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $revision->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $revision->estado_badge }}">
                            {{ ucfirst($revision->estado) }}
                        </span>
                    </div>

                    {{-- Comentario --}}
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        {{ $revision->comentario }}
                    </p>

                    {{-- Respuesta del proveedor (si existe) --}}
                    @if($revision->respuesta_proveedor)
                        <div class="mt-3 pl-4 border-l-2 border-green-400 dark:border-green-600">
                            <p class="text-xs font-semibold text-green-700 dark:text-green-300 mb-1">
                                Respuesta ({{ $revision->respondidoPor->name ?? 'Proveedor' }} - {{ $revision->fecha_respuesta?->format('d/m/Y H:i') }}):
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $revision->respuesta_proveedor }}
                            </p>
                        </div>
                    @endif

                    {{-- Botón para responder (solo si está pendiente y puede responder) --}}
                    @if($puedeResponder && $revision->estado === 'pendiente' && !in_array($revision->tipo, ['aprobacion', 'rechazo']))
                        <div class="mt-3 pt-3 border-t border-{{ $revision->tipo_color }}-200 dark:border-{{ $revision->tipo_color }}-700">
                            <button
                                wire:click="abrirModalRespuesta({{ $revision->id }})"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                                Responder
                            </button>
                        </div>
                    @endif

                    {{-- Botón para cerrar (solo FAO) --}}
                    @if($puedeRevisar && $revision->estado === 'atendido')
                        <div class="mt-3 pt-3 border-t border-{{ $revision->tipo_color }}-200 dark:border-{{ $revision->tipo_color }}-700">
                            <button
                                wire:click="cerrarRevision({{ $revision->id }})"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Cerrar Revisión
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
            No hay revisiones para este seguimiento
        </p>
    @endif
</div>
