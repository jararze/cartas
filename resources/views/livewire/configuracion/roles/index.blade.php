<?php

use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->with('permissions')
            ->get();

        return [
            'roles' => $roles,
        ];
    }
}; ?>

<div title="Gestión de Roles">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('configuracion.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Configuración</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Roles</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Roles</h1>
                <p class="text-gray-600 dark:text-gray-400">Administrar roles y sus permisos</p>
            </div>
            <a href="{{ route('configuracion.roles.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Rol
            </a>
        </div>

        <!-- Lista de Roles -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($roles as $role)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ $role->name }}
                                </h3>
                                <div class="flex gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <div>
                                        <span class="font-semibold">{{ $role->users_count }}</span> usuarios
                                    </div>
                                    <div>
                                        <span class="font-semibold">{{ $role->permissions_count }}</span> permisos
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($role->permissions->isNotEmpty())
                            <div class="mb-4">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Permisos principales:</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(6) as $permission)
                                        <span class="inline-block px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300 rounded">
                                            {{ str_replace('_', ' ', $permission->name) }}
                                        </span>
                                    @endforeach
                                    @if($role->permissions->count() > 6)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                            +{{ $role->permissions->count() - 6 }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mb-4 text-sm text-gray-500 dark:text-gray-400 italic">
                                Sin permisos asignados
                            </div>
                        @endif

                        <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('configuracion.roles.edit', $role) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Editar
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
