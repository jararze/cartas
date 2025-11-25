<?php

use App\Models\Carta;
use App\Models\Proveedor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Services\InvitationDocumentGenerator;

new class extends Component {
    use WithFileUploads;

    public $project_name = '';
    public $service_description = '';
    public $background = '';
    public $required_services = [];
    public $required_products = [];
    public $start_date = '';
    public $end_date = '';
    public $total_amount = '';
    public $currency = 'USD';
    public $provider_email = '';
    public $invitation_message = '';
    public $new_product = '';
    public $new_service = '';
    public $provider_whatsapp = '';
    public $send_type = 'email';
    public $lastCreatedCarta;

    public $show_provider_modal = false;
    public $provider_search = '';
    public $selected_provider = null;

    public $new_provider_name = '';
    public $new_provider_email = '';
    public $new_provider_phone = '';
    public $new_provider_whatsapp = '';
    public $new_provider_company = '';
    public $showWhatsAppModal = false;
    public $selected_provider_id = null;

    // Archivos adjuntos
    public $archivos_adjuntos = [];
    public $archivos_guardados = [];

    public function rules()
    {
        return [
            'project_name' => 'required|min:5|max:255',
            'service_description' => 'required|min:20',
            'background' => 'required|min:10',
            'required_services' => 'required|array|min:1',
            'required_services.*' => 'string|max:255',
            'required_products' => 'array',
            'required_products.*' => 'string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'selected_provider_id' => 'required|exists:proveedors,id',
            'send_type' => 'required|in:email,whatsapp,ambos',
            'archivos_adjuntos.*' => 'nullable|file|max:10240', // max 10MB por archivo
        ];
    }

    public function updatedArchivosAdjuntos()
    {
        $this->validate([
            'archivos_adjuntos.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
        ]);
    }

    public function removeArchivo($index)
    {
        array_splice($this->archivos_adjuntos, $index, 1);
    }

    public function addProduct()
    {
        if (!empty($this->new_product)) {
            $this->required_products[] = $this->new_product;
            $this->new_product = '';
        }
    }

    public function removeProduct($index)
    {
        unset($this->required_products[$index]);
        $this->required_products = array_values($this->required_products);
    }

    public function addService()
    {
        if (!empty($this->new_service)) {
            $this->required_services[] = $this->new_service;
            $this->new_service = '';
        }
    }

    public function removeService($index)
    {
        unset($this->required_services[$index]);
        $this->required_services = array_values($this->required_services);
    }

    public function searchProviders()
    {
        return Proveedor::where('activo', true)
            ->where(function ($query) {
                $query->where('nombre', 'like', '%'.$this->provider_search.'%')
                    ->orWhere('email', 'like', '%'.$this->provider_search.'%')
                    ->orWhere('empresa', 'like', '%'.$this->provider_search.'%');
            })
            ->limit(10)
            ->get();
    }

    public function selectProvider($providerId)
    {
        $provider = Proveedor::find($providerId);
        if ($provider) {
            $this->selected_provider = $provider;
            $this->selected_provider_id = $provider->id;
            $this->show_provider_modal = false;
            $this->provider_search = '';
        }
    }

    public function openProviderModal()
    {
        $this->show_provider_modal = true;
        $this->provider_search = '';
    }

    public function closeProviderModal()
    {
        $this->show_provider_modal = false;
        $this->provider_search = '';
        $this->resetNewProviderFields();
    }

    public function resetNewProviderFields()
    {
        $this->new_provider_name = '';
        $this->new_provider_email = '';
        $this->new_provider_phone = '';
        $this->new_provider_whatsapp = '';
        $this->new_provider_company = '';
    }

    public function createNewProvider()
    {
        $validated = $this->validate([
            'new_provider_name' => 'required|min:2',
            'new_provider_email' => 'required|email|unique:proveedors,email',
            'new_provider_phone' => 'nullable|string',
            'new_provider_whatsapp' => 'nullable|string',
            'new_provider_company' => 'nullable|string',
        ]);

        $provider = Proveedor::create([
            'nombre' => $this->new_provider_name,
            'email' => $this->new_provider_email,
            'telefono' => $this->new_provider_phone,
            'whatsapp' => $this->new_provider_whatsapp,
            'empresa' => $this->new_provider_company,
        ]);

        $this->selectProvider($provider->id);
        $this->resetNewProviderFields();
    }

    public function closeWhatsAppModal()
    {
        $this->showWhatsAppModal = false;
        session()->flash('success', 'Carta creada exitosamente');
        return redirect()->route('cartas.index');
    }

    private function guardarArchivos($cartaId)
    {
        $archivosGuardados = [];

        if (!empty($this->archivos_adjuntos)) {
            foreach ($this->archivos_adjuntos as $archivo) {
                $filename = time() . '_' . $archivo->getClientOriginalName();
                $path = $archivo->storeAs('cartas/adjuntos/' . $cartaId, $filename, 'public');

                $archivosGuardados[] = [
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'nombre_guardado' => $filename,
                    'path' => $path,
                    'mime_type' => $archivo->getMimeType(),
                    'size' => $archivo->getSize(),
                ];
            }
        }

        return $archivosGuardados;
    }

    public function save()
    {
        $this->validate([
            'project_name' => 'required|min:5|max:255',
            'service_description' => 'required|min:20',
            'background' => 'required|min:10',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'selected_provider_id' => 'required|exists:proveedors,id',
            'send_type' => 'required|in:email,whatsapp,ambos',
        ]);

        if (empty($this->required_services)) {
            $this->addError('required_services', 'Debe agregar al menos un servicio requerido.');
            return;
        }

        DB::beginTransaction();

        try {
            $carta = Carta::create([
                'codigo' => Carta::generarCodigo(),
                'nombre_proyecto' => $this->project_name,
                'descripcion_servicios' => $this->service_description,
                'creado_por' => auth()->id(),
                'oficina_fao' => auth()->user()->office ?? 'FAO Bolivia',
                'responsable_fao_nombre' => auth()->user()->name,
                'responsable_fao_email' => auth()->user()->email,
                'responsable_fao_telefono' => auth()->user()->phone,
                'antecedentes' => $this->background,
                'servicios_requeridos' => implode(', ', $this->required_services),
                'productos_requeridos' => $this->required_products,
                'fecha_inicio' => $this->start_date,
                'fecha_fin' => $this->end_date,
                'monto_total' => $this->total_amount ?: null,
                'moneda' => $this->currency,
                'proveedor_id' => $this->selected_provider_id,
                'tipo_envio' => $this->send_type,
                'mensaje_invitacion' => $this->invitation_message,
                'estado' => 'borrador',
                'archivos_adjuntos' => null,
            ]);

            // Guardar archivos adjuntos
            $archivosGuardados = $this->guardarArchivos($carta->id);

            if (!empty($archivosGuardados)) {
                $carta->update(['archivos_adjuntos' => $archivosGuardados]);
            }

            DB::commit();

            session()->flash('success', 'Carta creada exitosamente como borrador.');
            return redirect()->route('cartas.show', $carta);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando carta: ' . $e->getMessage());
            session()->flash('error', 'Error al guardar la carta: ' . $e->getMessage());
        }
    }

    public function sendInvitation()
    {
        try {
            Log::info('=== INICIO sendInvitation ===');

            $this->validate([
                'project_name' => 'required|min:5|max:255',
                'service_description' => 'required|min:20',
                'background' => 'required|min:10',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'selected_provider_id' => 'required|exists:proveedors,id',
                'send_type' => 'required|in:email,whatsapp,ambos',
            ]);

            if (empty($this->required_services)) {
                $this->addError('required_services', 'Debe agregar al menos un servicio requerido.');
                session()->flash('error', 'Debe agregar al menos un servicio requerido.');
                return;
            }

            DB::beginTransaction();

            $carta = Carta::create([
                'codigo' => Carta::generarCodigo(),
                'nombre_proyecto' => $this->project_name,
                'descripcion_servicios' => $this->service_description,
                'creado_por' => auth()->id(),
                'oficina_fao' => auth()->user()->office ?? 'FAO Bolivia',
                'responsable_fao_nombre' => auth()->user()->name,
                'responsable_fao_email' => auth()->user()->email,
                'responsable_fao_telefono' => auth()->user()->phone,
                'antecedentes' => $this->background,
                'servicios_requeridos' => implode(', ', $this->required_services),
                'productos_requeridos' => $this->required_products,
                'fecha_inicio' => $this->start_date,
                'fecha_fin' => $this->end_date,
                'monto_total' => $this->total_amount ?: null,
                'moneda' => $this->currency,
                'proveedor_id' => $this->selected_provider_id,
                'tipo_envio' => $this->send_type,
                'mensaje_invitacion' => $this->invitation_message,
                'estado' => 'enviada',
                'fecha_envio' => now(),
            ]);

            Log::info('✅ Carta creada con ID: ' . $carta->id);

            // Guardar archivos adjuntos
            $archivosGuardados = $this->guardarArchivos($carta->id);

            if (!empty($archivosGuardados)) {
                $carta->update(['archivos_adjuntos' => $archivosGuardados]);
            }

            // Generar el documento Word
            $generator = new InvitationDocumentGenerator($carta);
            $generator->generate();
            $filepath = $generator->save();
            $carta->update(['document_path' => $filepath]);

            DB::commit();

            $this->lastCreatedCarta = $carta;

            if ($this->send_type === 'whatsapp' || $this->send_type === 'ambos') {
                $this->prepareWhatsAppMessage($carta);
                $this->showWhatsAppModal = true;
            } else {
                session()->flash('success', 'Carta creada, documento generado y enviada exitosamente');
                return redirect()->route('cartas.index');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al crear carta: ' . $e->getMessage());
            session()->flash('error', 'Error al crear la carta: ' . $e->getMessage());
        }
    }

    private function prepareWhatsAppMessage(Carta $carta)
    {
        $provider = $carta->proveedor;
        $phoneNumber = preg_replace('/[^0-9]/', '', $provider->whatsapp ?: $provider->telefono);

        $documentUrl = '';
        if ($carta->document_path && file_exists($carta->document_path)) {
            $filename = basename($carta->document_path);
            $documentUrl = asset('storage/invitations/'.$filename);
        }

        $message = "¡Hola {$provider->nombre}! 👋\n\n";
        $message .= "Te enviamos una invitación de la FAO para participar en:\n\n";
        $message .= "📋 *{$carta->nombre_proyecto}*\n";
        $message .= "🔢 Código: {$carta->codigo}\n\n";

        if ($carta->fecha_fin) {
            $fechaLimite = Carbon::parse($carta->fecha_fin)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
            $message .= "📅 Fecha límite: *{$fechaLimite}*\n\n";
        }

        if ($carta->monto_total) {
            $monto = number_format($carta->monto_total, 2, ',', '.');
            $message .= "💰 Presupuesto estimado: {$monto} {$carta->moneda}\n\n";
        }

        $message .= "Para revisar los detalles y responder, visita:\n";
        $message .= route('cartas.public', $carta->codigo)."\n\n";

        if ($documentUrl) {
            $message .= "📄 *Descarga el documento completo aquí:*\n";
            $message .= $documentUrl."\n\n";
        }

        $message .= "¡Gracias por tu atención! 🙏";

        $whatsappUrl = "https://wa.me/{$phoneNumber}?text=".urlencode($message);

        session()->flash('whatsapp_url', $whatsappUrl);
        session()->flash('provider_name', $provider->nombre);
    }

    public function openWhatsAppAndRedirect()
    {
        session()->flash('success', 'Invitación enviada por WhatsApp');
        return redirect()->route('cartas.show', $this->lastCreatedCarta);
    }

    public function saveAndGenerateDocument()
    {
        try {
            $this->validate([
                'project_name' => 'required|min:5|max:255',
                'service_description' => 'required|min:20',
                'background' => 'required|min:10',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'selected_provider_id' => 'required|exists:proveedors,id',
            ]);

            if (empty($this->required_services)) {
                $this->addError('required_services', 'Debe agregar al menos un servicio requerido.');
                return;
            }

            DB::beginTransaction();

            $carta = Carta::create([
                'codigo' => Carta::generarCodigo(),
                'nombre_proyecto' => $this->project_name,
                'descripcion_servicios' => $this->service_description,
                'creado_por' => auth()->id(),
                'oficina_fao' => auth()->user()->office ?? 'FAO Bolivia',
                'responsable_fao_nombre' => auth()->user()->name,
                'responsable_fao_email' => auth()->user()->email,
                'responsable_fao_telefono' => auth()->user()->phone,
                'antecedentes' => $this->background,
                'servicios_requeridos' => implode(', ', $this->required_services),
                'productos_requeridos' => $this->required_products,
                'fecha_inicio' => $this->start_date,
                'fecha_fin' => $this->end_date,
                'monto_total' => $this->total_amount ?: null,
                'moneda' => $this->currency,
                'proveedor_id' => $this->selected_provider_id,
                'tipo_envio' => $this->send_type ?? 'email',
                'mensaje_invitacion' => $this->invitation_message,
                'estado' => 'borrador',
            ]);

            // Guardar archivos adjuntos
            $archivosGuardados = $this->guardarArchivos($carta->id);

            if (!empty($archivosGuardados)) {
                $carta->update(['archivos_adjuntos' => $archivosGuardados]);
            }

            // Generar el documento Word
            $generator = new InvitationDocumentGenerator($carta);
            $generator->generate();
            $filepath = $generator->save();
            $carta->update(['document_path' => $filepath]);

            DB::commit();

            $this->lastCreatedCarta = $carta;

            session()->flash('success', 'Carta guardada y documento generado exitosamente');

            return response()->download($filepath);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando carta y generando documento: ' . $e->getMessage());
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

}; ?>

<div class="p-4 sm:p-6 lg:px-8">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('cartas.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
           wire:navigate>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Nueva Carta Documento</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Complete los datos y envíe la invitación al
                proveedor</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Información del Responsable FAO -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6 border border-blue-200 dark:border-blue-800">
        <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Información FAO</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-blue-600 dark:text-blue-400 font-medium">Responsable:</span>
                <span class="text-gray-700 dark:text-gray-300 ml-1">{{ auth()->user()->name }}</span>
            </div>
            <div>
                <span class="text-blue-600 dark:text-blue-400 font-medium">Email:</span>
                <span class="text-gray-700 dark:text-gray-300 ml-1">{{ auth()->user()->email }}</span>
            </div>
            <div>
                <span class="text-blue-600 dark:text-blue-400 font-medium">Oficina:</span>
                <span class="text-gray-700 dark:text-gray-300 ml-1">{{ auth()->user()->office ?? 'FAO Bolivia' }}</span>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <form wire:submit="save" class="space-y-6">

                <!-- Información Básica -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="lg:col-span-2">
                        <flux:input
                            wire:model="project_name"
                            label="Nombre del Proyecto / Carta"
                            placeholder="Ej: Proyecto de Seguridad Alimentaria..."
                            required
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <flux:textarea
                            wire:model="service_description"
                            label="Descripción de Servicios"
                            placeholder="Describe el objetivo y alcance de los servicios requeridos..."
                            rows="3"
                            required
                        />
                    </div>
                </div>

                <!-- Antecedentes -->
                <div>
                    <flux:textarea
                        wire:model="background"
                        label="Antecedentes"
                        placeholder="Contexto y justificación del proyecto..."
                        rows="4"
                        required
                    />
                </div>

                <!-- Servicios Requeridos como Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Servicios Requeridos
                    </label>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                        @if(count($required_services) > 0)
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($required_services as $index => $service)
                                    <span
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-medium">
                                        {{ $service }}
                                        <button type="button" wire:click="removeService({{ $index }})"
                                                class="text-green-600 hover:text-green-800 dark:text-green-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <flux:input
                                wire:model="new_service"
                                placeholder="Escriba un servicio y presione Agregar..."
                                wire:keydown.enter.prevent="addService"
                                class="flex-1"
                            />
                            <flux:button type="button" wire:click="addService" variant="outline">
                                Agregar
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Productos Requeridos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Productos Requeridos
                    </label>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                        @if(count($required_products) > 0)
                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($required_products as $index => $product)
                                    <span
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium">
                                        {{ $product }}
                                        <button type="button" wire:click="removeProduct({{ $index }})"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <flux:input
                                wire:model="new_product"
                                placeholder="Escriba un producto y presione Agregar..."
                                wire:keydown.enter.prevent="addProduct"
                                class="flex-1"
                            />
                            <flux:button type="button" wire:click="addProduct" variant="outline">
                                Agregar
                            </flux:button>
                        </div>
                    </div>
                </div>

                <!-- Archivos Adjuntos - NUEVO -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Archivos Adjuntos
                    </label>
                    <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700">
                        <!-- Mostrar archivos adjuntos -->
                        @if(count($archivos_adjuntos) > 0)
                            <div class="space-y-2 mb-4">
                                @foreach($archivos_adjuntos as $index => $archivo)
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $archivo->getClientOriginalName() }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($archivo->getSize() / 1024, 2) }} KB</p>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="removeArchivo({{ $index }})"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Input de archivos -->
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">Click para subir</span> o arrastra archivos
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (MAX. 10MB)</p>
                                </div>
                                <input type="file" wire:model="archivos_adjuntos" multiple class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" />
                            </label>
                        </div>

                        @error('archivos_adjuntos.*')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div wire:loading wire:target="archivos_adjuntos" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                            Subiendo archivos...
                        </div>
                    </div>
                </div>

                <!-- Fechas y Presupuesto -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <flux:input
                            wire:model="start_date"
                            label="Fecha Inicio"
                            type="date"
                            required
                        />
                    </div>
                    <div>
                        <flux:input
                            wire:model="end_date"
                            label="Fecha Fin"
                            type="date"
                            required
                        />
                    </div>
                    <div>
                        <flux:input
                            wire:model="total_amount"
                            label="Monto Total (Opcional)"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Moneda
                        </label>
                        <select
                            wire:model="currency"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                        >
                            <option value="USD">USD - Dólares</option>
                            <option value="BOB">BOB - Bolivianos</option>
                        </select>
                    </div>
                </div>

                <!-- Información del Proveedor -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Información del Proveedor</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione o cree un nuevo proveedor
                                para este proyecto</p>
                        </div>
                    </div>

                    @if($selected_provider)
                        <div
                            class="relative overflow-hidden bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 p-6 rounded-xl border border-emerald-200 dark:border-emerald-800 mb-6">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-100 dark:bg-emerald-800/30 rounded-full -mr-16 -mt-16 opacity-50"></div>

                            <div class="relative flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-12 h-12 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-md">
                                        {{ substr($selected_provider->nombre, 0, 2) }}
                                    </div>

                                    <div class="flex-1">
                                        <h4 class="text-lg font-bold text-emerald-800 dark:text-emerald-300 mb-1">
                                            {{ $selected_provider->nombre }}
                                        </h4>

                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none"
                                                     stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                                <span
                                                    class="text-emerald-700 dark:text-emerald-300 font-medium">{{ $selected_provider->email }}</span>
                                            </div>

                                            @if($selected_provider->empresa)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                    <span
                                                        class="text-emerald-700 dark:text-emerald-300">{{ $selected_provider->empresa }}</span>
                                                </div>
                                            @endif

                                            @if($selected_provider->telefono)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                    </svg>
                                                    <span
                                                        class="text-emerald-700 dark:text-emerald-300">{{ $selected_provider->telefono }}</span>
                                                </div>
                                            @endif

                                            @if($selected_provider->whatsapp)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
                                                         fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.109"/>
                                                    </svg>
                                                    <span
                                                        class="text-emerald-700 dark:text-emerald-300">{{ $selected_provider->whatsapp }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button"
                                            wire:click="openProviderModal"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-800/50 rounded-lg hover:bg-emerald-200 dark:hover:bg-emerald-800 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Cambiar
                                    </button>

                                    <button type="button"
                                            wire:click="$set('selected_provider', null)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-800/50 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Quitar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="relative">
                            <div
                                class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors duration-200">
                                <div
                                    class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>

                                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">
                                    Seleccionar Proveedor
                                </h4>
                                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                                    Busque un proveedor existente en la base de datos o cree uno nuevo para continuar
                                    con la carta.
                                </p>

                                <button type="button"
                                        wire:click="openProviderModal"
                                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Buscar o Crear Proveedor
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Configuración de envío -->
                    @if($selected_provider)
                        <div
                            class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                Invitación proveedor
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Option Email -->
                                <label
                                    class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md {{ $send_type === 'email' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-600' }}">
                                    <input type="radio" wire:model.live="send_type" value="email" class="sr-only">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 {{ $send_type === 'email' ? 'bg-blue-600' : 'bg-gray-400' }} rounded-full flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span
                                                class="text-sm font-semibold {{ $send_type === 'email' ? 'text-blue-800 dark:text-blue-300' : 'text-gray-800 dark:text-white' }}">Solo Email</span>
                                            <p class="text-xs text-gray-500">Envío tradicional por correo</p>
                                        </div>
                                    </div>
                                    @if($send_type === 'email')
                                        <div class="absolute top-2 right-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </label>

                                <!-- Option WhatsApp -->
                                <label
                                    class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md {{ $send_type === 'whatsapp' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-600' }}">
                                    <input type="radio" wire:model.live="send_type" value="whatsapp" class="sr-only">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 {{ $send_type === 'whatsapp' ? 'bg-green-600' : 'bg-gray-400' }} rounded-full flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.109"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span
                                                class="text-sm font-semibold {{ $send_type === 'whatsapp' ? 'text-green-800 dark:text-green-300' : 'text-gray-800 dark:text-white' }}">Solo WhatsApp</span>
                                            <p class="text-xs text-gray-500">Envío directo por WhatsApp</p>
                                        </div>
                                    </div>
                                    @if($send_type === 'whatsapp')
                                        <div class="absolute top-2 right-2">
                                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </label>

                                <!-- Option Ambos -->
                                <label
                                    class="relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all hover:shadow-md {{ $send_type === 'ambos' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-gray-200 dark:border-gray-600' }}">
                                    <input type="radio" wire:model.live="send_type" value="ambos" class="sr-only">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 {{ $send_type === 'ambos' ? 'bg-purple-600' : 'bg-gray-400' }} rounded-full flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span
                                                class="text-sm font-semibold {{ $send_type === 'ambos' ? 'text-purple-800 dark:text-purple-300' : 'text-gray-800 dark:text-white' }}">Email y WhatsApp</span>
                                            <p class="text-xs text-gray-500">Envío por ambos canales</p>
                                        </div>
                                    </div>
                                    @if($send_type === 'ambos')
                                        <div class="absolute top-2 right-2">
                                            <svg class="w-5 h-5 text-purple-600" fill="currentColor"
                                                 viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </label>
                            </div>
                        </div>
                    @endif

                    <!-- Mensaje personalizado -->
                    @if($selected_provider)
                        <div class="mt-6">
                            <flux:textarea
                                wire:model="invitation_message"
                                label="Mensaje Personalizado (Opcional)"
                                placeholder="Agregue un mensaje personalizado que se incluirá en la invitación..."
                                rows="4"
                            />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Este mensaje aparecerá junto con la invitación oficial de la carta documento.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Modal de Proveedores -->
                @if($show_provider_modal)
                    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div
                            class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Seleccionar
                                        Proveedor</h2>
                                    <button type="button" wire:click="closeProviderModal"
                                            class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <flux:input
                                    wire:model.live="provider_search"
                                    placeholder="Buscar por nombre, email o empresa..."
                                    icon:leading="magnifying-glass"
                                />
                            </div>

                            <div class="p-6">
                                @if($provider_search)
                                    <div class="space-y-3 mb-6">
                                        @forelse($this->searchProviders() as $provider)
                                            <div
                                                class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                                wire:click="selectProvider({{ $provider->id }})">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <h4 class="font-semibold text-gray-800 dark:text-white">{{ $provider->nombre }}</h4>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $provider->email }}</p>
                                                        @if($provider->empresa)
                                                            <p class="text-sm text-gray-500 dark:text-gray-500">{{ $provider->empresa }}</p>
                                                        @endif
                                                    </div>
                                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                         viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No se
                                                encontraron proveedores</p>
                                        @endforelse
                                    </div>
                                @endif

                                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Crear Nuevo
                                        Proveedor</h3>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input
                                            wire:model="new_provider_name"
                                            label="Nombre *"
                                            placeholder="Nombre del contacto"
                                            required
                                        />
                                        <flux:input
                                            wire:model="new_provider_email"
                                            label="Email *"
                                            type="email"
                                            placeholder="email@empresa.com"
                                            required
                                        />
                                        <flux:input
                                            wire:model="new_provider_phone"
                                            label="Teléfono"
                                            placeholder="+591 2 1234567"
                                        />
                                        <flux:input
                                            wire:model="new_provider_whatsapp"
                                            label="WhatsApp"
                                            placeholder="+591 7 1234567"
                                        />
                                        <div class="md:col-span-2">
                                            <flux:input
                                                wire:model="new_provider_company"
                                                label="Empresa"
                                                placeholder="Nombre de la empresa"
                                            />
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-3 mt-6">
                                        <flux:button type="button" wire:click="closeProviderModal" variant="outline">
                                            Cancelar
                                        </flux:button>
                                        <flux:button type="button" wire:click="createNewProvider" variant="primary">
                                            Crear Proveedor
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:button type="button" variant="outline" href="{{ route('cartas.index') }}" wire:navigate>
                        Cancelar
                    </flux:button>
                    <flux:button type="submit" variant="outline">
                        Guardar como Borrador
                    </flux:button>
                    <flux:button
                        type="button"
                        wire:click="sendInvitation"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed">
                            <span wire:loading.remove wire:target="sendInvitation">
                                Crear y Enviar Invitación
                            </span>
                        <span wire:loading wire:target="sendInvitation" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </span>
                    </flux:button>
                </div>

                @if (session()->has('error'))
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-semibold text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                        <p class="text-sm font-semibold text-red-800 mb-2">Errores de validación:</p>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </form>
        </div>
    </div>

    @if($showWhatsAppModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.109"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-bold text-gray-800 mb-2">Carta Creada Exitosamente</h3>
                    <p class="text-gray-600 mb-6">Haz clic en el botón para enviar la invitación
                        a {{ $selected_provider->nombre ?? 'el proveedor' }} por WhatsApp</p>

                    <div class="space-y-3">
                        <a href="{{ session('whatsapp_url') }}"
                           target="_blank"
                           onclick="window.open(this.href); return false;"
                           class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 shadow-md hover:shadow-lg inline-flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.109"/>
                            </svg>
                            Enviar por WhatsApp
                        </a>

                        <button wire:click="closeWhatsAppModal"
                                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                            Ver Carta Creada
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
