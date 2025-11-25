<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        Log::info('📝 Registro iniciado', [
            'email' => $request->email,
            'redirect' => $request->redirect,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        event(new Registered($user));
        Auth::login($user);

        Log::info('✅ Usuario registrado y logueado', ['user_id' => $user->id]);

        // Redirección
        if ($request->has('redirect')) {
            return redirect($request->input('redirect'));
        }

        return redirect()->route('dashboard');
    }
}
