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


});
