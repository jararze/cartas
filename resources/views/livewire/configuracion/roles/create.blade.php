<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;

new class extends Component {
    public $name = '';
    public $selectedPermissions = [];

    public function with(): array
    {
        // Agrupar permisos por categoría
        $permissions = Permission::all();

        $permissionsGrouped = $permissions->groupBy(function($permission) {
            // Extraer el prefijo (crear_, editar_, ver_, etc)
            $parts = explode('_', $permission->name);
            if (count($parts) > 1) {
                return $parts[1]; // cartas, productos, actividades, etc
            }
            return 'general';
        });

        return [
            'permissionsGrouped' => $permissionsGrouped,
            'allPermissions' => $permissions,
        ];
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'selectedPermissions' => 'array',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        $role = Role::create(['name' => $validated['name']]);

        if (!empty($this->selectedPermissions)) {
            $role->syncPermissions($this->selectedPermissions);
        }

        session()->flash('success', 'Rol creado exitosamente');

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

<div title="Nuevo Rol">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('configuracion.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Configuración</a></li>
                <li>/</li>
                <li><a href="{{ route('configuracion.roles.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Roles</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Nuevo</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nuevo Rol</h1>
            <p class="text-gray-600 dark:text-gray-400">Crear un nuevo rol y asignar permisos</p>
        </div>

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
                            placeholder="Ej: Editor, Supervisor, Consultor"
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
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Seleccione los permisos que tendrá este rol
                    </p>

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

                    @error('selectedPermissions')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Resumen -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Resumen</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Este rol tendrá
                        <span class="font-semibold text-gray-900 dark:text-white">{{ count($selectedPermissions) }}</span>
                        permiso(s) asignado(s)
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
                        Crear Rol
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
