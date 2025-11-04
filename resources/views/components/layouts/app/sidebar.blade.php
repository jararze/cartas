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

        /* Personalizar sidebar de Flux */
        [data-flux-sidebar] {
            background-color: white;
            border-right: 1px solid rgb(229 231 235);
        }

        .dark [data-flux-sidebar] {
            background-color: rgb(24 24 27);
            border-right: 1px solid rgb(63 63 70);
        }

        /* Personalizar items activos */
        [data-flux-navlist-item][data-current="true"] {
            background-color: rgba(0, 115, 230, 0.1);
            border-left: 4px solid var(--color-fao-blue);
            color: var(--color-fao-blue);
        }

        .dark [data-flux-navlist-item][data-current="true"] {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            color: #60a5fa;
        }

        /* Hover effect */
        [data-flux-navlist-item]:hover {
            background-color: rgba(0, 115, 230, 0.05);
        }

        .dark [data-flux-navlist-item]:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
    </style>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900">

<flux:sidebar sticky stashable class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>

    <!-- Logo FAO -->
    <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse mb-8" wire:navigate>
        <x-app-logo/>
    </a>

    <!-- Navigation Menu -->
    <flux:navlist variant="outline">
        <flux:navlist.group heading="Principal" class="grid">
            <flux:navlist.item
                icon="chart-bar-square"
                :href="route('dashboard')"
                :current="request()->routeIs('dashboard')"
                wire:navigate
            >
                Dashboard
            </flux:navlist.item>
        </flux:navlist.group>

        <flux:navlist.group heading="Gestión de Cartas" class="grid">
            <flux:navlist.item
                icon="document-text"
                href="/cartas"
                :current="request()->is('cartas*')"
                wire:navigate
            >
                Cartas Documento
            </flux:navlist.item>

            <flux:navlist.item
                icon="cube"
                href="/productos"
                :current="request()->is('productos*')"
                wire:navigate
            >
                Productos
            </flux:navlist.item>

            <flux:navlist.item
                icon="clipboard-document-check"
                href="/actividades"
                :current="request()->is('actividades*')"
                wire:navigate
            >
                Actividades
            </flux:navlist.item>

            <flux:navlist.item
                icon="chart-pie"
                href="/seguimiento"
                :current="request()->is('seguimiento*')"
                wire:navigate
            >
                Seguimiento
            </flux:navlist.item>
        </flux:navlist.group>

        @can('ver_usuarios')
            <flux:navlist.group heading="Administración" class="grid">
                <flux:navlist.item
                    icon="user-group"
                    href="/usuarios"
                    :current="request()->is('usuarios*')"
                    wire:navigate
                >
                    Usuarios
                </flux:navlist.item>

                <flux:navlist.item
                    icon="cog-6-tooth"
                    href="/configuracion"
                    :current="request()->is('configuracion*')"
                    wire:navigate
                >
                    Configuración
                </flux:navlist.item>
            </flux:navlist.group>
        @endcan

        <flux:navlist.group heading="Reportes" class="grid">
            <flux:navlist.item
                icon="document-chart-bar"
                href="/reportes"
                :current="request()->is('reportes*')"
                wire:navigate
            >
                Reportes
            </flux:navlist.item>

            <flux:navlist.item
                icon="arrow-down-tray"
                href="/exportar"
                :current="request()->is('exportar*')"
                wire:navigate
            >
                Exportar Datos
            </flux:navlist.item>
        </flux:navlist.group>
    </flux:navlist>

    <flux:spacer/>

    <!-- Support Links -->
    <flux:navlist variant="outline">
        <flux:navlist.item icon="question-mark-circle" href="/ayuda" target="_blank">
            Ayuda
        </flux:navlist.item>

        <flux:navlist.item icon="book-open" href="https://laravel.com/docs" target="_blank">
            Documentación
        </flux:navlist.item>
    </flux:navlist>

    <!-- Desktop User Menu -->
    <flux:dropdown class="hidden lg:block" position="bottom" align="start">
        <flux:profile
            :name="auth()->user()->name"
            :initials="auth()->user()->initials()"
            icon:trailing="chevrons-up-down"
            data-test="sidebar-menu-button"
        />

        <flux:menu class="w-[250px]">
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-3 px-3 py-3 text-start text-sm">
                <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-lg">
                    <span
                        class="flex h-full w-full items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500 text-white font-bold text-sm">
                        {{ auth()->user()->initials() }}
                    </span>
                </span>

                        <div class="grid flex-1 text-start leading-tight">
                            <span
                                class="truncate font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                            <span
                                class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</span>

                            <!-- Rol con badge -->
                            <div class="mt-1">
                                @if(auth()->user()->hasRole('Administrador'))
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                Administrador
                            </span>
                                @elseif(auth()->user()->hasRole('Coordinador'))
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                Coordinador
                            </span>
                                @elseif(auth()->user()->hasRole('Técnico'))
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Técnico
                            </span>
                                @elseif(auth()->user()->hasRole('Proveedor'))
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                Proveedor
                            </span>
                                @elseif(auth()->user()->hasRole('Contraparte'))
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                Contraparte
                            </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                Usuario
                            </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <!-- Información adicional del usuario -->
            @if(auth()->user()->office)
                <div
                    class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h2M9 7h6m-6 4h6m-6 4h6"/>
                        </svg>
                        <span>{{ auth()->user()->office }}</span>
                    </div>
                    @if(auth()->user()->position)
                        <div class="flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>{{ auth()->user()->position }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                    Configuración
                </flux:menu.item>
                <flux:menu.item href="/mi-perfil" icon="user" wire:navigate>
                    Mi Perfil
                </flux:menu.item>
                @if(auth()->user()->hasRole(['Administrador', 'Coordinador']))
                    <flux:menu.item href="/reportes" icon="document-chart-bar" wire:navigate>
                        Mis Reportes
                    </flux:menu.item>
                @endif
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <!-- Información de última conexión -->
            @if(auth()->user()->last_login_at)
                <div class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Última conexión: {{ auth()->user()->last_login_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
                <flux:menu.separator/>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                                data-test="logout-button">
                    Cerrar Sesión
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:sidebar>

<!-- Mobile User Menu -->
<!-- Mobile User Menu -->
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:spacer />

    <flux:dropdown position="top" align="end">
        <flux:profile
            :initials="auth()->user()->initials()"
            icon-trailing="chevron-down"
        />

        <flux:menu class="w-[280px]">
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-3 px-3 py-3 text-start text-sm">
                        <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-lg">
                            <span class="flex h-full w-full items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500 text-white font-bold">
                                {{ auth()->user()->initials() }}
                            </span>
                        </span>

                        <div class="grid flex-1 text-start leading-tight">
                            <span class="truncate font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</span>

                            <!-- Rol badge - versión móvil -->
                            <div class="mt-1">
                                @if(auth()->user()->hasRole('Administrador'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        Admin
                                    </span>
                                @elseif(auth()->user()->hasRole('Coordinador'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        Coordinador
                                    </span>
                                @elseif(auth()->user()->hasRole('Técnico'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Técnico
                                    </span>
                                @elseif(auth()->user()->hasRole('Proveedor'))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                        Proveedor
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400">
                                        Usuario
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <flux:menu.radio.group>
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Configuración</flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator />

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-600" data-test="logout-button">
                    Cerrar Sesión
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>

{{ $slot }}

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@fluxScripts
</body>
</html>
