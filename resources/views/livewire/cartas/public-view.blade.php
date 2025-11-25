<?php

use App\Models\Carta;
use Livewire\Volt\Component;

new class extends Component {
    public Carta $carta;
    public string $codigo;

    public function mount($codigo)
    {
        $this->codigo = $codigo;
        $this->carta = Carta::with(['proveedor'])
            ->where('codigo', $codigo)
            ->firstOrFail();

        // Registrar primera vista solo si no ha sido vista
        if (!$this->carta->fecha_vista) {
            $this->carta->update(['fecha_vista' => now()]);
        }
    }
}; ?>

    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación FAO - {{ $carta->codigo }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">

        <!-- Tarjeta Principal -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">

            <!-- Header con logo FAO -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-8 text-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">FAO Bolivia</h1>
                <p class="text-blue-100 text-lg">Invitación de Carta Documento</p>
            </div>

            <!-- Contenido -->
            <div class="p-8 space-y-6">

                <!-- Código de Carta -->
                <div class="text-center bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Código de Invitación</p>
                    <h2 class="text-4xl font-bold text-blue-600 dark:text-blue-400 tracking-wider">{{ $carta->codigo }}</h2>
                </div>

                <!-- Proyecto (solo nombre general) -->
                <div class="text-center">
                    <p class="text-gray-600 dark:text-gray-400 mb-2">Proyecto</p>
                    <h3 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $carta->nombre_proyecto }}</h3>
                </div>

                <!-- Mensaje de destinatario -->
                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-6 border-l-4 border-emerald-500">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300 mb-1">Esta invitación es para:</p>
                            <p class="text-lg font-bold text-emerald-900 dark:text-emerald-200">{{ $carta->proveedor->email }}</p>
                            @if($carta->proveedor->nombre)
                                <p class="text-emerald-700 dark:text-emerald-300 mt-1">{{ $carta->proveedor->nombre }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Mensaje de seguridad -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                            <strong>Por seguridad:</strong> Para ver los detalles completos de la invitación, descripción del proyecto, archivos adjuntos y poder aceptar/rechazar, necesitas iniciar sesión o crear una cuenta.
                        </p>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="space-y-3 pt-4">
                    <a href="{{ route('login', ['email' => $carta->proveedor->email, 'carta' => $carta->codigo, 'redirect' => route('cartas.view', $carta->codigo)]) }}"
                       class="block w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 px-6 rounded-xl text-center transition duration-200 transform hover:scale-105 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Iniciar Sesión para Ver Detalles
                    </a>

                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">¿No tienes cuenta?</span>
                        </div>
                    </div>

                    <a href="{{ route('register', ['email' => $carta->proveedor->email, 'carta' => $carta->codigo, 'redirect' => route('cartas.view', $carta->codigo)]) }}"
                       class="block w-full bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-semibold py-4 px-6 rounded-xl text-center transition duration-200 border-2 border-gray-300 dark:border-gray-600">
                        <svg class="w-5 h-5 inline-block mr-2 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Crear Cuenta Nueva
                    </a>
                </div>

                <!-- Info adicional -->
                <div class="text-center pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Si tienes alguna consulta, contacta a:
                    </p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                        {{ $carta->responsable_fao_email ?? 'FAO Bolivia' }}
                    </p>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                © {{ date('Y') }} FAO Bolivia - Organización de las Naciones Unidas para la Alimentación y la Agricultura
            </p>
        </div>

    </div>
</div>

</body>
</html>
