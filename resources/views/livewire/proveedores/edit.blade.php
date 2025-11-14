<?php

use App\Models\Proveedor;
use Livewire\Volt\Component;

new class extends Component {
    public Proveedor $proveedor;

    public $nombre;
    public $email;
    public $telefono;
    public $whatsapp;
    public $empresa;
    public $contacto_principal;
    public $cargo;
    public $especialidades = [];
    public $especialidadInput = '';
    public $notas;
    public $activo;

    public function mount(Proveedor $proveedor)
    {
        $this->proveedor = $proveedor;
        $this->nombre = $proveedor->nombre;
        $this->email = $proveedor->email;
        $this->telefono = $proveedor->telefono;
        $this->whatsapp = $proveedor->whatsapp;
        $this->empresa = $proveedor->empresa;
        $this->contacto_principal = $proveedor->contacto_principal;
        $this->cargo = $proveedor->cargo;
        $this->notas = $proveedor->notas;
        $this->activo = $proveedor->activo;

        // Convertir especialidades
        if ($proveedor->especialidades) {
            $esp = is_string($proveedor->especialidades)
                ? json_decode($proveedor->especialidades, true)
                : $proveedor->especialidades;
            $this->especialidades = is_array($esp) ? $esp : [];
        }
    }

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
            'email' => 'required|email|unique:proveedors,email,' . $this->proveedor->id,
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

        $validated['especialidades'] = !empty($validated['especialidades'])
            ? json_encode($validated['especialidades'])
            : null;

        $this->proveedor->update($validated);

        session()->flash('success', 'Proveedor actualizado exitosamente');

        return $this->redirect(route('proveedores.show', $this->proveedor));
    }

    public function delete()
    {
        if ($this->proveedor->cartas()->count() > 0) {
            session()->flash('error', 'No se puede eliminar el proveedor porque tiene cartas asociadas');
            return;
        }

        $this->proveedor->delete();

        session()->flash('success', 'Proveedor eliminado exitosamente');

        return $this->redirect(route('proveedores.index'));
    }
}; ?>

<div title="Editar Proveedor">
    <div class="p-6">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('proveedores.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Proveedores</a></li>
                <li>/</li>
                <li><a href="{{ route('proveedores.show', $proveedor) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $proveedor->nombre }}</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Editar</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Editar Proveedor</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $proveedor->nombre }}</p>
            </div>

            <button
                wire:click="delete"
                wire:confirm="¿Está seguro de eliminar este proveedor? Esta acción no se puede deshacer."
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Eliminar
            </button>
        </div>

        @if (session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <!-- Formulario (igual al create pero con los campos poblados) -->
        <form wire:submit="save">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Información Básica -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Básica</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre del Proveedor <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="nombre"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('nombre')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

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

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Empresa
                            </label>
                            <input
                                type="text"
                                wire:model="empresa"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Teléfono
                            </label>
                            <input
                                type="text"
                                wire:model="telefono"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                WhatsApp
                            </label>
                            <input
                                type="text"
                                wire:model="whatsapp"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Persona de Contacto -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Persona de Contacto</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre del Contacto
                            </label>
                            <input
                                type="text"
                                wire:model="contacto_principal"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Cargo
                            </label>
                            <input
                                type="text"
                                wire:model="cargo"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Especialidades con Tags -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información Adicional</h2>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Especialidades
                            </label>

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
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notas
                            </label>
                            <textarea
                                wire:model="notas"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                        </div>

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
                    <a href="{{ route('proveedores.show', $proveedor) }}"
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
