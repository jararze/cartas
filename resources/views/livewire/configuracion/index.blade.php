<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $totalRoles = Role::count();
        $totalPermisos = Permission::count();
        $totalUsuarios = User::count();

        $roles = Role::withCount(['users', 'permissions'])->get();

        return [
            'stats' => [
                'total_roles' => $totalRoles,
                'total_permisos' => $totalPermisos,
                'total_usuarios' => $totalUsuarios,
            ],
            'roles' => $roles,
        ];
    }
}; ?>

<div title="Configuración">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Configuración del Sistema</h1>
            <p class="text-gray-600 dark:text-gray-400">Administración de roles, permisos y configuraciones generales</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Roles del Sistema</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $stats['total_roles'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Permisos Totales</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $stats['total_permisos'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Usuarios Activos</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $stats['total_usuarios'] }}
                </div>
            </div>
        </div>

        <!-- Menú de Configuración -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Gestión de Roles -->
            <a href="{{ route('configuracion.roles.index') }}" class="block">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                Gestión de Roles
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                Crear, editar y administrar los roles del sistema
                            </p>
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $stats['total_roles'] }} roles configurados</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- Gestión de Permisos -->
            <a href="{{ route('configuracion.permisos.index') }}" class="block">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                Gestión de Permisos
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                Administrar los permisos disponibles en el sistema
                            </p>
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $stats['total_permisos'] }} permisos disponibles</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </a>
        </div>

        <!-- Resumen de Roles -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Roles del Sistema</h2>
            <div class="space-y-4">
                @foreach($roles as $role)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $role->name }}</h3>
                                <div class="flex gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span>{{ $role->users_count }} usuarios</span>
                                    <span>{{ $role->permissions_count }} permisos</span>
                                </div>
                            </div>
                            <a href="{{ route('configuracion.roles.edit', $role) }}"
                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        </div>

                        @if($role->permissions->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach($role->permissions->take(10) as $permission)
                                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                @endforeach
                                @if($role->permissions->count() > 10)
                                    <span class="inline-block px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                        +{{ $role->permissions->count() - 10 }} más
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
