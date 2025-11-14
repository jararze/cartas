<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public Role $role;

    public $name;
    public $selectedPermissions = [];

    public function mount(Role $role)
    {
        $this->role = $role;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    public function with(): array
    {
        $permissions = Permission::all();

        $permissionsGrouped = $permissions->groupBy(function($permission) {
            $parts = explode('_', $permission->name);
            if (count($parts) > 1) {
                return $parts[1];
            }
            return 'general';
        });

        $usersWithRole = \App\Models\User::role($this->role->name)->count();

        return [
            'permissionsGrouped' => $permissionsGrouped,
            'allPermissions' => $permissions,
            'usersWithRole' => $usersWithRole,
        ];
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . $this->role->id,
            'selectedPermissions' => 'array',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        $this->role->update(['name' => $validated['name']]);
        $this->role->syncPermissions($this->selectedPermissions);

        session()->flash('success', 'Rol actualizado exitosamente');

        return $this->redirect(route('configuracion.roles.index'));
    }

    public function delete()
    {
        // Verificar si hay usuarios con este rol
        $usersCount = \App\Models\User::role($this->role->name)->count();

        if ($usersCount > 0) {
            session()->flash('error', "No se puede eliminar el rol porque tiene {$usersCount} usuario(s) asignado(s)");
            return;
        }

        $this->role->delete();

        session()->flash('success', 'Rol eliminado exitosamente');

        return $this->redirect(route('configuracion.roles.index'));
    }

    public function selectAll($category)
    {
        $permissions = Permission::all()->filter(function($permission) use ($category) {
            return str_contains($permission->name, '_' . $category);
        })->pluck('name')->toArray();

        $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $permissions));
    }

    public function deselectAll($category)
    {
        $permissions = Permission::all()->filter(function($permission) use ($category) {
            return str_contains($permission->name, '_' . $category);
        })->pluck('name')->toArray();

        $this->selectedPermissions = array_diff($this->selectedPermissions, $permissions);
    }
}; ?>

<div title="Editar Rol">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('configuracion.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Configuración</a></li>
                <li>/</li>
                <li><a href="{{ route('configuracion.roles.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Roles</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">{{ $role->name }}</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Editar Rol</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $role->name }} - {{ $usersWithRole }} usuario(s) con este rol</p>
            </div>

            <button
                wire:click="delete"
                wire:confirm="¿Está seguro de eliminar este rol? Esta acción no se puede deshacer."
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Eliminar Rol
            </button>
        </div>

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario -->
        <form wire:submit="save">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Nombre del Rol -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Rol</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nombre del Rol <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            wire:model="name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        />
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Permisos -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Permisos del Rol
                    </h2>

                    <div class="space-y-6">
                        @foreach($permissionsGrouped as $category => $permissions)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white capitalize">
                                        {{ str_replace('_', ' ', $category) }}
                                    </h3>
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            wire:click="selectAll('{{ $category }}')"
                                            class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            Seleccionar todos
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deselectAll('{{ $category }}')"
                                            class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400">
                                            Deseleccionar todos
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer {{ in_array($permission->name, $selectedPermissions) ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '' }}">
                                            <input
                                                type="checkbox"
                                                wire:model="selectedPermissions"
                                                value="{{ $permission->name }}"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ str_replace('_', ' ', $permission->name) }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Resumen -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Resumen</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Este rol tiene
                        <span class="font-semibold text-gray-900 dark:text-white">{{ count($selectedPermissions) }}</span>
                        permiso(s) asignado(s) y está siendo usado por
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $usersWithRole }}</span>
                        usuario(s)
                    </p>
                </div>

                <!-- Botones -->
                <div class="p-6 flex justify-end gap-3">
                    <a href="{{ route('configuracion.roles.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
