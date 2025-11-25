<?php

use App\Models\Carta;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function with()
    {
        $user = auth()->user();

        if (!$user->proveedor) {
            abort(403, 'No tienes perfil de proveedor');
        }

        $invitaciones = Carta::where('proveedor_id', $user->proveedor->id)
            ->whereIn('estado', ['enviada', 'vista', 'aceptada', 'rechazada'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return [
            'invitaciones' => $invitaciones,
        ];
    }
}; ?>

<div title="Mis Invitaciones">
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">Mis Invitaciones</h1>
        </div>

        <div class="grid gap-4">
            @forelse($invitaciones as $invitacion)
                <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-bold text-neutral-900 dark:text-white">{{ $invitacion->codigo }}</h3>
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    @if($invitacion->estado === 'enviada' || $invitacion->estado === 'vista') bg-orange-100 text-orange-800
                                    @elseif($invitacion->estado === 'aceptada') bg-green-100 text-green-800
                                    @elseif($invitacion->estado === 'rechazada') bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($invitacion->estado) }}
                                </span>
                            </div>
                            <p class="text-neutral-700 dark:text-neutral-300 mb-1">{{ $invitacion->nombre_proyecto }}</p>
                            <p class="text-sm text-neutral-500">Enviada: {{ $invitacion->fecha_envio?->format('d/m/Y H:i') ?? 'No especificada' }}</p>
                        </div>

                        <div class="flex flex-col gap-2">
                            @if($invitacion->estado === 'enviada' || $invitacion->estado === 'vista')
                                <a href="{{ route('cartas.view', $invitacion->codigo) }}"
                                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium text-center">
                                    Ver y Responder
                                </a>
                            @else
                                <a href="{{ route('cartas.view', $invitacion->codigo) }}"
                                   class="px-4 py-2 bg-neutral-600 text-white rounded-lg hover:bg-neutral-700 text-sm font-medium text-center">
                                    Ver Detalles
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <p class="text-neutral-500">No tienes invitaciones</p>
                </div>
            @endforelse
        </div>

        {{ $invitaciones->links() }}
    </div>
</div>
