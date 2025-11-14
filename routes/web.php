<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReporteController;
use App\Models\Carta;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// En routes/web.php
Route::get('/invitation/{codigo}', function ($codigo) {
    $carta = Carta::where('codigo', $codigo)->firstOrFail();
    return view('livewire.cartas.public-view', compact('carta'));
})->name('cartas.public');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Rutas de Cartas con Volt::route
    Volt::route('cartas', 'cartas.index')->name('cartas.index');
    Volt::route('cartas/crear', 'cartas.create')->name('cartas.create');
    Volt::route('cartas/{carta}', 'cartas.show')->name('cartas.show');

    Volt::route('actividades/{actividad}/historial', 'actividades.historial')
        ->name('actividades.historial');

    Volt::route('reportes', 'reportes.index')->name('reportes.index');

    Route::get('reportes/descargar', [ReporteController::class, 'descargar'])
        ->middleware('auth')
        ->name('reportes.descargar');

    Volt::route('productos', 'productos.index')->name('productos.index');
    Volt::route('productos/{producto}', 'productos.show')->name('productos.show');

    Volt::route('actividades', 'actividades.index')->name('actividades.index');
    Volt::route('actividades/{actividad}', 'actividades.show')->name('actividades.show');

    Volt::route('proveedores', 'proveedores.index')->name('proveedores.index');
    Volt::route('proveedores/crear', 'proveedores.create')->name('proveedores.create');
    Volt::route('proveedores/{proveedor}/editar', 'proveedores.edit')->name('proveedores.edit');
    Volt::route('proveedores/{proveedor}', 'proveedores.show')->name('proveedores.show');

    Volt::route('usuarios', 'usuarios.index')->name('usuarios.index');
    Volt::route('usuarios/crear', 'usuarios.create')->name('usuarios.create');
    Volt::route('usuarios/{user}/editar', 'usuarios.edit')->name('usuarios.edit');
    Volt::route('usuarios/{user}', 'usuarios.show')->name('usuarios.show');

    // Configuración
    Volt::route('configuracion', 'configuracion.index')->name('configuracion.index');

    // Roles
    Volt::route('configuracion/roles', 'configuracion.roles.index')->name('configuracion.roles.index');
    Volt::route('configuracion/roles/crear', 'configuracion.roles.create')->name('configuracion.roles.create');
    Volt::route('configuracion/roles/{role}/editar', 'configuracion.roles.edit')->name('configuracion.roles.edit');

    // Permisos
    Volt::route('configuracion/permisos', 'configuracion.permisos.index')->name('configuracion.permisos.index');

});
