<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user): void
    {
        Log::info('🔵 UserObserver::created disparado', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        try {
            // BUSCAR proveedor existente
            $proveedor = Proveedor::where('email', $user->email)->first();

            if ($proveedor) {
                // VINCULAR proveedor existente
                $proveedor->update(['user_id' => $user->id]);

                Log::info('✅ Proveedor VINCULADO', [
                    'proveedor_id' => $proveedor->id,
                    'user_id' => $user->id,
                ]);
            } else {
                // CREAR nuevo proveedor
                $proveedor = Proveedor::create([
                    'user_id' => $user->id,
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'activo' => true,
                ]);

                Log::info('✅ Proveedor CREADO', [
                    'proveedor_id' => $proveedor->id,
                ]);
            }

            // Asignar rol
            if (!$user->hasRole('Proveedor')) {
                $user->assignRole('Proveedor');
                Log::info('✅ Rol Proveedor asignado', ['user_id' => $user->id]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
