<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filterRole = '';

    public function with(): array
    {
        $totalUsuarios = User::count();
        $usuariosActivos = User::whereNotNull('email_verified_at')->count();

        // Contar usuarios por rol
        $roles = Role::withCount('users')->get();

        $query = User::with('roles')
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterRole, function($q) {
                $q->role($this->filterRole);
            })
            ->latest();

        $usuarios = $query->paginate(15);

        return [
            'kpis' => [
                'total' => $totalUsuarios,
                'activos' => $usuariosActivos,
                'sin_verificar' => $totalUsuarios - $usuariosActivos,
            ],
            'usuarios' => $usuarios,
            'roles' => $roles,
            'todosRoles' => Role::all(),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterRole()
    {
        $this->resetPage();
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
}; ?>

<div title="Usuarios">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Usuarios</h1>
                <p class="text-gray-600 dark:text-gray-400">Gestión de usuarios y roles del sistema</p>
            </div>
            <a href="{{ route('usuarios.create') }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Usuario
            </a>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Usuarios</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ number_format($kpis['total']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Usuarios Activos</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                    {{ number_format($kpis['activos']) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400">Sin Verificar</div>
                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                    {{ number_format($kpis['sin_verificar']) }}
                </div>
            </div>
        </div>

        <!-- Usuarios por Rol -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Usuarios por Rol</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($roles as $role)
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $role->users_count }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $role->name }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre o email..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                </div>

                <select
                    wire:model.live="filterRole"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="">Todos los roles</option>
                    @foreach($todosRoles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Usuario</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Email</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Roles</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Estado</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Registro</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($usuarios as $usuario)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="py-4 px-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                        {{ strtoupper(substr($usuario->name, 0, 2)) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $usuario->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $usuario->email }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1 justify-center">
                                    @forelse($usuario->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getRoleColor($role->name) }}">
                                                {{ $role->name }}
                                            </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Sin rol</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($usuario->email_verified_at)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            Verificado
                                        </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">
                                            Sin verificar
                                        </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ $usuario->created_at->format('d/m/Y') }}
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('usuarios.show', $usuario) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Ver
                                    </a>
                                    <a href="{{ route('usuarios.edit', $usuario) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron usuarios
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $usuarios->links() }}
            </div>
        </div>
    </div>
</div>
