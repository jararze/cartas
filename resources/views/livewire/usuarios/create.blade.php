<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $selectedRoles = [];

    public function mount()
    {
        // Cargar roles disponibles
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
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'selectedRoles' => 'required|array|min:1',
        ];
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

        // Crear usuario
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(), // Verificar automáticamente
        ]);

        // Asignar roles
        $user->syncRoles($this->selectedRoles);

        session()->flash('success', 'Usuario creado exitosamente');

        return $this->redirect(route('usuarios.show', $user));
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

<div title="Nuevo Usuario">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('usuarios.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Usuarios</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Nuevo</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nuevo Usuario</h1>
            <p class="text-gray-600 dark:text-gray-400">Complete los datos del nuevo usuario</p>
        </div>

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
                                placeholder="Nombre completo del usuario"
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
                                placeholder="correo@ejemplo.com"
                            />
                            @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Contraseña <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="password"
                                wire:model="password"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Mínimo 8 caracteres"
                            />
                            @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Confirmar Contraseña <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="password"
                                wire:model="password_confirmation"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Repita la contraseña"
                            />
                        </div>
                    </div>
                </div>

                <!-- Roles y Permisos -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Roles del Usuario <span class="text-red-600">*</span>
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Seleccione uno o más roles para el usuario. Los roles determinan los permisos y accesos del usuario en el sistema.
                    </p>

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

                                    <!-- Mostrar algunos permisos clave -->
                                    @if($role->permissions->count() > 0)
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach($role->permissions->take(3) as $permission)
                                                <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                                    {{ str_replace('_', ' ', $permission->name) }}
                                                </span>
                                            @endforeach
                                            @if($role->permissions->count() > 3)
                                                <span class="inline-block px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                                    +{{ $role->permissions->count() - 3 }} más
                                                </span>
                                            @endif
                                        </div>
                                    @endif
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
                    <a href="{{ route('usuarios.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Crear Usuario
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
