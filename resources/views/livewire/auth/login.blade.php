<x-layouts.auth>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Mensaje de invitación si viene desde carta -->
    @if(request('carta'))
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Has recibido una invitación</p>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">Inicia sesión para ver los detalles completos de la carta: <strong>{{ request('carta') }}</strong></p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
        @csrf

        <!-- Hidden redirect field -->
        @if(request('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
        @endif

        <!-- Email con Flux -->
        <flux:input
            name="email"
            label="Correo Electrónico"
            type="email"
            placeholder="tu@email.com"
            icon:leading="at-symbol"
            value="{{ request('email', old('email')) }}"
            required
            autofocus
            autocomplete="email"
        />

        <!-- Password con Flux -->
        <flux:input
            name="password"
            label="Contraseña"
            type="password"
            placeholder="••••••••"
            icon:leading="lock-closed"
            viewable
            required
            autocomplete="current-password"
        />

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <flux:checkbox
                name="remember"
                label="Recordarme"
                :checked="old('remember')"
            />

            @if (Route::has('password.request'))
                <flux:link :href="route('password.request')">
                    ¿Olvidaste tu contraseña?
                </flux:link>
            @endif
        </div>

        <!-- Botón Login con tamaño corregido -->
        <flux:button
            type="submit"
            variant="primary"
            class="w-full py-3 px-6 text-base font-semibold transform hover:-translate-y-0.5 transition duration-200 shadow-lg hover:shadow-xl"
        >
            Iniciar Sesión
        </flux:button>
    </form>

    <!-- Usuarios de prueba -->
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">Usuarios de prueba:</p>
        <div class="grid grid-cols-2 gap-3">
            <button
                onclick="fillCredentials('admin@fao.org', 'password')"
                class="btn-fao-admin px-4 py-2 rounded-lg transition text-sm font-medium"
            >
                👤 Administrador
            </button>
            <button
                onclick="fillCredentials('proveedor@empresa.com', 'password')"
                class="btn-fao-proveedor px-4 py-2 rounded-lg transition text-sm font-medium"
            >
                🏢 Proveedor
            </button>
        </div>
    </div>

    @if (Route::has('register'))
        <div class="mt-6 space-x-1 text-sm text-center text-gray-600 dark:text-gray-400">
            <span>¿No tienes cuenta?</span>
            <flux:link :href="route('register', request()->only(['email', 'carta', 'redirect']))">Regístrate</flux:link>
        </div>
    @endif

    <script>
        function fillCredentials(email, password) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="password"]').value = password;
        }
    </script>
</x-layouts.auth>
