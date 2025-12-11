<?php

use Livewire\Volt\Component;
use App\Models\Actividad;
use App\Models\Carta;

new class extends Component {
    public ?int $cartaId = null;
    public bool $showModal = false;
    public ?Actividad $actividadSeleccionada = null;
    public string $respuesta = '';
    public string $accion = '';

    public function mount(?int $cartaId = null): void
    {
        $this->cartaId = $cartaId;
    }

    public function getActividadesPendientesProperty()
    {
        $query = Actividad::with(['producto.carta.creador', 'solicitante'])
            ->where('estado', 'pendiente_cancelacion')
            ->where('estado_cancelacion', 'pendiente');

        if ($this->cartaId) {
            $query->whereHas('producto', function ($q) {
                $q->where('carta_id', $this->cartaId);
            });
        }

        // Si NO es administrador, filtrar solo sus cartas
        if (!auth()->user()->hasRole('Administrador')) {
            $query->whereHas('producto.carta', function ($q) {
                $q->where('creado_por', auth()->id());
            });
        }

        return $query->orderBy('fecha_solicitud_cancelacion', 'asc')->get();
    }

    public function abrirModal(int $actividadId, string $accion): void
    {
        $this->actividadSeleccionada = Actividad::with(['producto.carta', 'solicitante'])->find($actividadId);
        $this->accion = $accion;
        $this->respuesta = '';
        $this->showModal = true;
    }

    public function procesarSolicitud(): void
    {
        $rules = ['respuesta' => 'nullable|string'];

        if ($this->accion === 'rechazar') {
            $rules['respuesta'] = 'required|min:10';
        }

        $this->validate($rules, [
            'respuesta.required' => 'Debe indicar el motivo del rechazo',
            'respuesta.min' => 'El motivo debe tener al menos 10 caracteres',
        ]);

        if ($this->accion === 'aprobar') {
            $this->actividadSeleccionada->aprobarCancelacion(auth()->id(), $this->respuesta);
            session()->flash('success', 'Cancelación aprobada correctamente.');
        } else {
            $this->actividadSeleccionada->rechazarCancelacion(auth()->id(), $this->respuesta);
            session()->flash('success', 'Cancelación rechazada. La actividad vuelve a su estado anterior.');
        }

        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->showModal = false;
        $this->actividadSeleccionada = null;
        $this->respuesta = '';
        $this->accion = '';
    }

}; ?>

<div>
    @if($this->actividadesPendientes->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>No hay solicitudes de cancelación pendientes</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->actividadesPendientes as $actividad)
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-yellow-200 dark:border-yellow-800 p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded">
                                    Pendiente
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $actividad->producto->carta->codigo ?? 'Sin código' }}
                                </span>
                            </div>

                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $actividad->nombre }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Producto: {{ $actividad->producto->nombre }}
                            </p>

                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <strong>Motivo:</strong> {{ $actividad->motivo_cancelacion }}
                                </p>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Solicitado por <strong>{{ $actividad->solicitante->name ?? 'Usuario' }}</strong>
                                el {{ $actividad->fecha_solicitud_cancelacion->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 ml-4">
                            <button wire:click="abrirModal({{ $actividad->id }}, 'aprobar')"
                                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Aprobar
                            </button>
                            <button wire:click="abrirModal({{ $actividad->id }}, 'rechazar')"
                                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Rechazar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal Aprobar/Rechazar --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        @if($actividadSeleccionada)
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 {{ $accion === 'aprobar' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-full flex items-center justify-center">
                        @if($accion === 'aprobar')
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $accion === 'aprobar' ? 'Aprobar' : 'Rechazar' }} Cancelación
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $actividadSeleccionada->nombre }}</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <strong>Motivo del proveedor:</strong><br>
                        {{ $actividadSeleccionada->motivo_cancelacion }}
                    </p>
                </div>

                @if($accion === 'aprobar')
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                        <p class="text-sm text-green-800 dark:text-green-200">
                            <strong>✓</strong> Al aprobar, la actividad será marcada como cancelada y no se considerará
                            en el progreso del producto.
                        </p>
                    </div>
                @else
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                        <p class="text-sm text-red-800 dark:text-red-200">
                            <strong>✗</strong> Al rechazar, la actividad volverá a su estado anterior
                            ({{ $actividadSeleccionada->estado_anterior_cancelacion }}) y el proveedor será notificado.
                        </p>
                    </div>
                @endif

                <form wire:submit="procesarSolicitud">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $accion === 'aprobar' ? 'Comentario (opcional)' : 'Motivo del rechazo' }}
                            @if($accion === 'rechazar')<span class="text-red-500">*</span>@endif
                        </label>
                        <textarea wire:model="respuesta"
                                  rows="4"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  placeholder="{{ $accion === 'aprobar' ? 'Comentario opcional...' : 'Explique por qué no se puede cancelar esta actividad...' }}"></textarea>
                        @error('respuesta')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                                wire:click="cerrarModal"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 {{ $accion === 'aprobar' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white font-medium rounded-lg transition-colors">
                            {{ $accion === 'aprobar' ? 'Confirmar Aprobación' : 'Confirmar Rechazo' }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>
</div>
