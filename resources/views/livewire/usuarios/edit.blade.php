<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public User $user;

    public $name;
    public $email;
    public $password = '';
    public $password_confirmation = '';
    public $selectedRoles = [];

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
    }

    public function with(): array
    {
        $roles = Role::all();

        return [
            'roles' => $roles,
        ];
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'selectedRoles' => 'required|array|min:1',
        ];

        // Solo validar password si se está cambiando
        if (!empty($this->password)) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'selectedRoles.required' => 'Debe seleccionar al menos un rol',
            'selectedRoles.min' => 'Debe seleccionar al menos un rol',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        // Actualizar datos básicos
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        // Solo actualizar password si se proporcionó uno nuevo
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);

        // Actualizar roles
        $this->user->syncRoles($this->selectedRoles);

        session()->flash('success', 'Usuario actualizado exitosamente');

        return $this->redirect(route('usuarios.show', $this->user));
    }

    public function delete()
    {
        // No permitir eliminar el propio usuario
        if ($this->user->id === auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propio usuario');
            return;
        }

        $this->user->delete();

        session()->flash('success', 'Usuario eliminado exitosamente');

        return $this->redirect(route('usuarios.index'));
    }

    public function getRoleColor($roleName)
    {
        return match($roleName) {
            'Administrador' => 'border-red-300 dark:border-red-700',
            'Coordinador' => 'border-purple-300 dark:border-purple-700',
            'Técnico' => 'border-blue-300 dark:border-blue-700',
            'Proveedor' => 'border-green-300 dark:border-green-700',
            'Contraparte' => 'border-yellow-300 dark:border-yellow-700',
            'Invitado' => 'border-gray-300 dark:border-gray-700',
            default => 'border-gray-300',
        };
    }
}; ?>

<div title="Editar Usuario">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('usuarios.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Usuarios</a></li>
                <li>/</li>
                <li><a href="{{ route('usuarios.show', $user) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $user->name }}</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Editar</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Editar Usuario</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $user->name }}</p>
            </div>

            @if($user->id !== auth()->id())
                <button
                    wire:click="delete"
                    wire:confirm="¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer."
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Eliminar Usuario
                </button>
            @endif
        </div>

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario -->
        <form wire:submit="save">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Información Básica -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Personal</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre Completo <span class="text-red-600">*</span>
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

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="email"
                                wire:model="email"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nueva Contraseña
                            </label>
                            <input
                                type="password"
                                wire:model="password"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Dejar en blanco para mantener actual"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Dejar vacío si no desea cambiar la contraseña
                            </p>
                            @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Confirmar Nueva Contraseña
                            </label>
                            <input
                                type="password"
                                wire:model="password_confirmation"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Repita la nueva contraseña"
                            />
                        </div>
                    </div>
                </div>

                <!-- Roles -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Roles del Usuario <span class="text-red-600">*</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($roles as $role)
                            <label class="relative flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $this->getRoleColor($role->name) }} {{ in_array($role->name, $selectedRoles) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                <input
                                    type="checkbox"
                                    wire:model="selectedRoles"
                                    value="{{ $role->name }}"
                                    class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <div class="ml-3 flex-1">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $role->name }}</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $role->permissions->count() }} permisos
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @error('selectedRoles')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Botones -->
                <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3">
                    <a href="{{ route('usuarios.show', $user) }}"
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
