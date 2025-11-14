<?php

use App\Models\Proveedor;
use App\Models\Carta;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterActivo = '';

    public function with(): array
    {
        $totalProveedores = Proveedor::count();
        $proveedoresActivos = Proveedor::where('activo', true)->count();
        $proveedoresInactivos = Proveedor::where('activo', false)->count();

        // Contar proveedores con cartas
        $cartasAsociadas = Carta::whereNotNull('proveedor_id')
            ->distinct('proveedor_id')
            ->count('proveedor_id');

        $query = Proveedor::query()
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('nombre', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('empresa', 'like', '%' . $this->search . '%')
                        ->orWhere('contacto_principal', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterActivo !== '', function($q) {
                $q->where('activo', $this->filterActivo);
            })
            ->latest();

        $proveedores = $query->paginate(12);

        // Agregar conteo de cartas manualmente
        $proveedores->getCollection()->transform(function($proveedor) {
            $proveedor->cartas_count = Carta::where('proveedor_id', $proveedor->id)->count();
            return $proveedor;
        });

        return [
            'kpis' => [
                'total' => $totalProveedores,
                'activos' => $proveedoresActivos,
                'inactivos' => $proveedoresInactivos,
                'con_cartas' => $cartasAsociadas,
            ],
            'proveedores' => $proveedores,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterActivo()
    {
        $this->resetPage();
    }
}; ?>

<div title="Proveedores">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Proveedores</h1>
                <p class="text-gray-600 dark:text-gray-400">Gestión de proveedores y contactos</p>
            </div>
            <a href="{{ route('proveedores.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Proveedor
            </a>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Proveedores</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ number_format($kpis['total']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Activos</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                    {{ number_format($kpis['activos']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Inactivos</div>
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400 mt-2">
                    {{ number_format($kpis['inactivos']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Con Cartas Asociadas</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                    {{ number_format($kpis['con_cartas']) }}
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre, email, empresa o contacto..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                </div>

                <select
                    wire:model.live="filterActivo"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todos los estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
        </div>

        <!-- Grid de Proveedores -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($proveedores as $proveedor)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <!-- Header con estado -->
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                    {{ $proveedor->nombre }}
                                </h3>
                                @if($proveedor->empresa)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $proveedor->empresa }}
                                    </p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $proveedor->activo ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $proveedor->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        <!-- Contacto Principal -->
                        @if($proveedor->contacto_principal)
                            <div class="mb-3">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Contacto</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $proveedor->contacto_principal }}
                                </div>
                                @if($proveedor->cargo)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $proveedor->cargo }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Email -->
                        <div class="mb-3">
                            <a href="mailto:{{ $proveedor->email }}"
                               class="flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $proveedor->email }}
                            </a>
                        </div>

                        <!-- Teléfonos -->
                        <div class="mb-3 space-y-1">
                            @if($proveedor->telefono)
                                <a href="tel:{{ $proveedor->telefono }}"
                                   class="flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    {{ $proveedor->telefono }}
                                </a>
                            @endif

                            @if($proveedor->whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $proveedor->whatsapp) }}"
                                   target="_blank"
                                   class="flex items-center text-sm text-green-600 dark:text-green-400 hover:text-green-800">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    WhatsApp
                                </a>
                            @endif
                        </div>

                        <!-- Especialidades -->
                        @if($proveedor->especialidades)
                            @php
                                $especialidades = is_string($proveedor->especialidades)
                                    ? json_decode($proveedor->especialidades, true)
                                    : $proveedor->especialidades;
                            @endphp
                            @if(is_array($especialidades) && count($especialidades) > 0)
                                <div class="mb-4">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Especialidades</div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice($especialidades, 0, 3) as $esp)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                {{ $esp }}
                                            </span>
                                        @endforeach
                                        @if(count($especialidades) > 3)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                +{{ count($especialidades) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Cartas Asociadas -->
                        <div class="mb-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Cartas Asociadas</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $proveedor->cartas_count }}
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex gap-2">
                            <a href="{{ route('proveedores.show', $proveedor) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Ver
                            </a>
                            <a href="{{ route('proveedores.edit', $proveedor) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-sm font-medium text-white">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p>No se encontraron proveedores</p>
                    <a href="{{ route('proveedores.create') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mt-2 inline-block">
                        Crear el primero
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Paginación -->
        @if($proveedores->hasPages())
            <div class="mt-6">
                {{ $proveedores->links() }}
            </div>
        @endif
    </div>
</div>
