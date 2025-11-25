<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Mensaje de invitación si viene desde carta -->
        @if(request('carta'))
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Invitación FAO</p>
                        <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">Crea tu cuenta para ver los detalles de la carta: <strong>{{ request('carta') }}</strong></p>
                    </div>
                </div>
            </div>
        @endif

        <!-- ERRORES DE VALIDACIÓN -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-800">Errores en el formulario:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- DEBUG: Mostrar valores recibidos (QUITAR EN PRODUCCIÓN) -->
            @if(config('app.debug'))
                <div class="text-xs bg-gray-100 p-2 rounded">
                    <strong>DEBUG:</strong><br>
                    Email: {{ request('email') }}<br>
                    Carta: {{ request('carta') }}<br>
                    Redirect: {{ request('redirect') }}
                </div>
            @endif

            <!-- Hidden redirect field -->
            @if(request('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect') }}">
            @endif

            <!-- Hidden carta field -->
            @if(request('carta'))
                <input type="hidden" name="carta" value="{{ request('carta') }}">
            @endif

            <!-- Name -->
            <div class="space-y-1">
                <flux:input
                    name="name"
                    :label="__('Name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('Full name')"
                    value="{{ old('name') }}"
                />
                @error('name')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="space-y-1">
                <flux:input
                    name="email"
                    :label="__('Email address')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                    value="{{ request('email', old('email')) }}"
                />
                @error('email')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror

                @if(request('email'))
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                        📧 Este email está asociado a tu invitación
                    </p>
                @endif
            </div>

            <!-- Password -->
            <div class="space-y-1">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                    viewable
                />
                @error('password')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="space-y-1">
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                    viewable
                />
                @error('password_confirmation')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end mt-2">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login', request()->only(['email', 'carta', 'redirect']))" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
