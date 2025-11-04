<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <style>
        :root {
            --color-fao-blue: #0073e6;
            --color-fao-blue-dark: #005bb5;
            --color-fao-green: #2e7d32;
            --color-fao-orange: #ff6b35;
        }

        /* Light Theme */
        .gradient-fao-light {
            background: linear-gradient(135deg, #0073e6 0%, #005bb5 100%);
        }

        /* Dark Theme */
        .gradient-fao-dark {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        .bg-fao-blue {
            background-color: #0073e6;
        }

        .hover\:bg-fao-blue-dark:hover {
            background-color: #005bb5;
        }

        .text-fao-blue {
            color: #0073e6;
        }

        .text-fao-green {
            color: #2e7d32;
        }

        /* Dark mode overrides */
        .dark .bg-fao-blue {
            background-color: #3b82f6;
        }

        .dark .hover\:bg-fao-blue-dark:hover {
            background-color: #2563eb;
        }

        .dark .text-fao-blue {
            color: #60a5fa;
        }
    </style>
</head>
<body
    class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-black min-h-screen flex items-center justify-center p-4">
<!-- Theme Toggle Button -->
<div class="fixed top-4 right-4 z-50">
    <button
        onclick="toggleTheme()"
        class="p-3 rounded-full bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700"
    >
        <!-- Light mode icon -->
        <svg class="w-5 h-5 text-gray-600 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <!-- Dark mode icon -->
        <svg class="w-5 h-5 text-gray-300 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </button>
</div>

<!-- Container Principal -->
<div
    class="w-full max-w-6xl grid md:grid-cols-2 gap-0 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl dark:shadow-gray-900/50 overflow-hidden border border-gray-200 dark:border-gray-700">

    <!-- Panel Izquierdo - Bienvenida -->
    <div
        class="gradient-fao-light dark:gradient-fao-dark p-12 flex flex-col justify-center text-white relative overflow-hidden hidden md:flex">
        <!-- Decoración de fondo -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 dark:bg-white/5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 dark:bg-white/5 rounded-full -ml-24 -mb-24"></div>

        <div class="relative z-10">
            <!-- Logo FAO -->
            <div class="mb-8">
                <div class="w-20 h-20 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>

            <h1 class="text-4xl font-bold mb-4">Sistema de Gestión FAO</h1>
            <p class="text-xl text-blue-100 dark:text-gray-300 mb-8">
                Plataforma integral para la gestión de cartas documento, productos y seguimiento de actividades
            </p>

            <!-- Features -->
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div
                        class="w-8 h-8 bg-white/20 dark:bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">Gestión Centralizada</h3>
                        <p class="text-blue-100 dark:text-gray-400 text-sm">Control total de cartas y documentos en un
                            solo lugar</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="w-8 h-8 bg-white/20 dark:bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">Seguimiento en Tiempo Real</h3>
                        <p class="text-blue-100 dark:text-gray-400 text-sm">Monitoreo continuo del progreso de
                            actividades</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="w-8 h-8 bg-white/20 dark:bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">Colaboración Efectiva</h3>
                        <p class="text-blue-100 dark:text-gray-400 text-sm">Trabajo en equipo con roles y permisos
                            definidos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Derecho - Formulario -->
    <div class="p-8 md:p-12 flex flex-col justify-center bg-white dark:bg-gray-900">
        <!-- Logo móvil -->
        <div class="flex justify-center mb-8 md:hidden">
            <div class="w-16 h-16 bg-blue-600 dark:bg-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Bienvenido</h2>
            <p class="text-gray-600 dark:text-gray-400">Ingresa tus credenciales para continuar</p>
        </div>

        <!-- Aquí va el contenido del slot -->
        {{ $slot }}

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>© 2025 FAO - Organización de las Naciones Unidas para la Alimentación y la Agricultura</p>
        </div>
    </div>
</div>

<script>
    // Theme toggle functionality
    function toggleTheme() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');

        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    }

    // Initialize theme from localStorage
    function initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    // Initialize on load
    initTheme();
</script>

@fluxScripts
</body>
</html>
