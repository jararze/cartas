<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user)
    {
        $this->user = $user->load('roles.permissions');
    }

    public function with(): array
    {
        // Actividad del usuario
        $cartasCreadas = \App\Models\Carta::where('creado_por', $this->user->id)->count();
        $actividadesResponsable = \App\Models\Actividad::where('responsable_id', $this->user->id)->count();
        $seguimientosRegistrados = \App\Models\SeguimientoActividad::where('registrado_por', $this->user->id)->count();

        // Últimas cartas creadas
        $ultimasCartas = \App\Models\Carta::where('creado_por', $this->user->id)
            ->with('proveedor')
            ->latest()
            ->limit(5)
            ->get();

        // Últimas actividades como responsable
        $ultimasActividades = \App\Models\Actividad::where('responsable_id', $this->user->id)
            ->with('producto.carta')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'stats' => [
                'cartas_creadas' => $cartasCreadas,
                'actividades_responsable' => $actividadesResponsable,
                'seguimientos_registrados' => $seguimientosRegistrados,
            ],
            'ultimasCartas' => $ultimasCartas,
            'ultimasActividades' => $ultimasActividades,
        ];
    }

    public function getRoleColor($roleName)
    {
        return match($roleName) {
            'Administrador' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'Coordinador' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
            'Técnico' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'Proveedor' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'Contraparte' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'Invitado' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getEstadoColor($estado)
    {
        return match($estado) {
            'pendiente' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'en_curso' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'finalizado' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'atrasado' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}; ?>

<div title="Detalle del Usuario">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('usuarios.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Usuarios</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">{{ $user->name }}</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center flex-1">
                    <!-- Avatar -->
                    <div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>

                    <div class="ml-6 flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                            @if($user->email_verified_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Verificado
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center text-gray-600 dark:text-gray-400 mb-3">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $user->email }}
                        </div>

                        <!-- Roles -->
                        <div class="flex flex-wrap gap-2">
                            @forelse($user->roles as $role)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $this->getRoleColor($role->name) }}">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500 dark:text-gray-400">Sin roles asignados</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('usuarios.edit', $user) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                    <a href="{{ route('usuarios.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>

            <!-- Info adicional -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Miembro desde</h3>
                    <p class="text-lg text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Última actualización</h3>
                    <p class="text-lg text-gray-900 dark:text-white">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">ID de Usuario</h3>
                    <p class="text-lg text-gray-900 dark:text-white">#{{ $user->id }}</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Actividad -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Cartas Creadas</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $stats['cartas_creadas'] }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Actividades Responsable</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $stats['actividades_responsable'] }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Seguimientos Registrados</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $stats['seguimientos_registrados'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permisos del Usuario -->
        @if($user->roles->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Permisos</h2>

                @foreach($user->roles as $role)
                    <div class="mb-6 last:mb-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            Rol: {{ $role->name }}
                        </h3>

                        @if($role->permissions->isNotEmpty())
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                @foreach($role->permissions as $permission)
                                    <div class="flex items-center px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Este rol no tiene permisos asignados</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Últimas Cartas Creadas -->
        @if($ultimasCartas->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Últimas Cartas Creadas</h2>
                <div class="space-y-3">
                    @foreach($ultimasCartas as $carta)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <a href="{{ route('cartas.show', $carta) }}" class="text-lg font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        {{ $carta->codigo }}
                                    </a>
                                    <h3 class="text-gray-900 dark:text-white mt-1">{{ $carta->nombre_proyecto }}</h3>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                        {{ \Carbon\Carbon::parse($carta->created_at)->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                {{ ucfirst(str_replace('_', ' ', $carta->estado)) }}
                            </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Últimas Actividades como Responsable -->
        @if($ultimasActividades->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Actividades como Responsable</h2>
                <div class="space-y-3">
                    @foreach($ultimasActividades as $actividad)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $actividad->nombre }}</h3>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $actividad->producto->nombre }} - {{ $actividad->producto->carta->codigo }}
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getEstadoColor($actividad->estado) }}">
                                {{ ucfirst(str_replace('_', ' ', $actividad->estado)) }}
                            </span>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Progreso: <span class="font-semibold text-gray-900 dark:text-white">{{ $actividad->progreso }}%</span>
                                </div>
                                <a href="{{ route('actividades.show', $actividad) }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                    Ver detalles →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
