<?php

use App\Models\Proveedor;
use Livewire\Volt\Component;

new class extends Component {
    public $nombre = '';
    public $email = '';
    public $telefono = '';
    public $whatsapp = '';
    public $empresa = '';
    public $contacto_principal = '';
    public $cargo = '';
    public $especialidades = [];
    public $especialidadInput = '';
    public $notas = '';
    public $activo = true;

    public function addEspecialidad()
    {
        $especialidad = trim($this->especialidadInput);

        if (!empty($especialidad) && !in_array($especialidad, $this->especialidades)) {
            $this->especialidades[] = $especialidad;
            $this->especialidadInput = '';
        }
    }

    public function removeEspecialidad($index)
    {
        unset($this->especialidades[$index]);
        $this->especialidades = array_values($this->especialidades);
    }

    public function rules()
    {
        return [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:proveedors,email',
            'telefono' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'empresa' => 'nullable|string|max:255',
            'contacto_principal' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'especialidades' => 'nullable|array',
            'notas' => 'nullable|string',
            'activo' => 'boolean',
        ];
    }

    public function save()
    {
        $validated = $this->validate();

        // Convertir especialidades a JSON
        $validated['especialidades'] = !empty($validated['especialidades'])
            ? json_encode($validated['especialidades'])
            : null;

        $proveedor = Proveedor::create($validated);

        session()->flash('success', 'Proveedor creado exitosamente');

        return $this->redirect(route('proveedores.show', $proveedor));
    }
}; ?>

<div title="Nuevo Proveedor">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('proveedores.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Proveedores</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Nuevo</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nuevo Proveedor</h1>
            <p class="text-gray-600 dark:text-gray-400">Complete los datos del proveedor</p>
        </div>

        <!-- Formulario -->
        <form wire:submit="save">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Información Básica -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Básica</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre del Proveedor <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="nombre"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Nombre completo del proveedor"
                            />
                            @error('nombre')
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

                        <!-- Empresa -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Empresa
                            </label>
                            <input
                                type="text"
                                wire:model="empresa"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Nombre de la empresa"
                            />
                            @error('empresa')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Teléfono
                            </label>
                            <input
                                type="text"
                                wire:model="telefono"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="+591 12345678"
                            />
                            @error('telefono')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- WhatsApp -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                WhatsApp
                            </label>
                            <input
                                type="text"
                                wire:model="whatsapp"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="+591 12345678"
                            />
                            @error('whatsapp')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Persona de Contacto -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Persona de Contacto</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contacto Principal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre del Contacto
                            </label>
                            <input
                                type="text"
                                wire:model="contacto_principal"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Nombre de la persona de contacto"
                            />
                            @error('contacto_principal')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cargo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Cargo
                            </label>
                            <input
                                type="text"
                                wire:model="cargo"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Cargo o posición"
                            />
                            @error('cargo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Especialidades y Notas -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Adicional</h2>

                    <div class="space-y-6">
                        <!-- Especialidades con Tags -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Especialidades
                            </label>

                            <!-- Input para agregar -->
                            <div class="flex gap-2 mb-3">
                                <input
                                    type="text"
                                    wire:model="especialidadInput"
                                    wire:keydown.enter.prevent="addEspecialidad"
                                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    placeholder="Escriba y presione Enter para agregar"
                                />
                                <button
                                    type="button"
                                    wire:click="addEspecialidad"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                    Agregar
                                </button>
                            </div>

                            <!-- Tags agregados -->
                            @if(count($especialidades) > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($especialidades as $index => $especialidad)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ $especialidad }}
                                            <button
                                                type="button"
                                                wire:click="removeEspecialidad({{ $index }})"
                                                class="ml-2 hover:text-blue-900 dark:hover:text-blue-100">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                    No hay especialidades agregadas
                                </p>
                            @endif

                            @error('especialidades')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notas
                            </label>
                            <textarea
                                wire:model="notas"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="Notas adicionales sobre el proveedor..."
                            ></textarea>
                            @error('notas')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Estado -->
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model="activo"
                                id="activo"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"
                            />
                            <label for="activo" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Proveedor activo
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3">
                    <a href="{{ route('proveedores.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Proveedor
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
