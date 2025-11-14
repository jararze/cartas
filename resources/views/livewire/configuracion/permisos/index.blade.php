<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;

new class extends Component {
    public $newPermissionName = '';
    public $showCreateModal = false;

    public function with(): array
    {
        $permissions = Permission::withCount('roles')->get();

        // Agrupar permisos por categoría
        $permissionsGrouped = $permissions->groupBy(function($permission) {
            $parts = explode('_', $permission->name);
            if (count($parts) > 1) {
                return $parts[1]; // cartas, productos, actividades, etc
            }
            return 'general';
        });

        $totalPermisos = $permissions->count();
        $permisosUsados = $permissions->filter(fn($p) => $p->roles_count > 0)->count();
        $permisosSinUsar = $totalPermisos - $permisosUsados;

        return [
            'permissionsGrouped' => $permissionsGrouped,
            'stats' => [
                'total' => $totalPermisos,
                'usados' => $permisosUsados,
                'sin_usar' => $permisosSinUsar,
            ],
        ];
    }

    public function createPermission()
    {
        $this->validate([
            'newPermissionName' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create(['name' => $this->newPermissionName]);

        $this->newPermissionName = '';
        $this->showCreateModal = false;

        session()->flash('success', 'Permiso creado exitosamente');
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);

        // Verificar si el permiso está siendo usado
        if ($permission->roles()->count() > 0) {
            session()->flash('error', 'No se puede eliminar el permiso porque está asignado a uno o más roles');
            return;
        }

        $permission->delete();

        session()->flash('success', 'Permiso eliminado exitosamente');
    }
}; ?>

<div title="Gestión de Permisos">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('configuracion.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Configuración</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Permisos</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gestión de Permisos</h1>
                <p class="text-gray-600 dark:text-gray-400">Administrar los permisos disponibles en el sistema</p>
            </div>
            <button
                wire:click="$set('showCreateModal', true)"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Permiso
            </button>
        </div>

        <!-- Alertas -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Permisos</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $stats['total'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Permisos en Uso</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                    {{ $stats['usados'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Sin Usar</div>
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400 mt-2">
                    {{ $stats['sin_usar'] }}
                </div>
            </div>
        </div>

        <!-- Lista de Permisos por Categoría -->
        <div class="space-y-6">
            @foreach($permissionsGrouped as $category => $permissions)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white capitalize">
                                {{ str_replace('_', ' ', $category) }}
                            </h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $permissions->count() }} permiso(s)
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Permiso</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Roles Asignados</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Estado</th>
                                    <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Acciones</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($permissions as $permission)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="py-4 px-4">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $permission->name }}
                                            </div>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                    {{ $permission->roles_count }} roles
                                                </span>
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            @if($permission->roles_count > 0)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                        En uso
                                                    </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                        Sin usar
                                                    </span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-center">
                                            @if($permission->roles_count == 0)
                                                <button
                                                    wire:click="deletePermission({{ $permission->id }})"
                                                    wire:confirm="¿Está seguro de eliminar este permiso?"
                                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-500 dark:text-gray-400">En uso</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Modal Crear Permiso -->
        @if($showCreateModal)
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nuevo Permiso</h3>
                            <button
                                wire:click="$set('showCreateModal', false)"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <form wire:submit="createPermission">
                        <div class="p-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre del Permiso <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="newPermissionName"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Ej: ver_reportes, editar_configuracion"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Use el formato: accion_modulo (ej: crear_cartas, ver_usuarios)
                            </p>
                            @error('newPermissionName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3">
                            <button
                                type="button"
                                wire:click="$set('showCreateModal', false)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Crear Permiso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
