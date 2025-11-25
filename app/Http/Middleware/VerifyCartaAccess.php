<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Carta;
use Illuminate\Support\Facades\Log;

class VerifyCartaAccess
{
    public function handle(Request $request, Closure $next)
    {
        $codigo = $request->route('codigo');
        $user = auth()->user();

        Log::info('🔍 Verificando acceso', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'codigo' => $codigo,
        ]);

        // Admin/Coordinador pueden ver todas
        if ($user->hasAnyRole(['Administrador', 'Coordinador'])) {
            Log::info('✅ Acceso: Admin/Coordinador');
            return $next($request);
        }

        // Verificar proveedor vinculado
        if (!$user->proveedor) {
            Log::error('❌ Usuario sin proveedor vinculado', [
                'user_id' => $user->id,
            ]);
            abort(403, 'No tienes un perfil de proveedor asociado');
        }

        // Obtener carta
        $carta = Carta::where('codigo', $codigo)->firstOrFail();

        Log::info('📄 Carta encontrada', [
            'carta_id' => $carta->id,
            'proveedor_id' => $carta->proveedor_id,
            'proveedor_email' => $carta->proveedor_email,
        ]);

        // VERIFICAR por proveedor_id O por email
        $tieneAcceso = false;

        if ($carta->proveedor_id && $carta->proveedor_id === $user->proveedor->id) {
            $tieneAcceso = true;
            Log::info('✅ Acceso por proveedor_id');
        } elseif ($carta->proveedor_email && $carta->proveedor_email === $user->proveedor->email) {
            $tieneAcceso = true;
            Log::info('✅ Acceso por proveedor_email');
        }

        if (!$tieneAcceso) {
            Log::warning('❌ Sin acceso', [
                'user_proveedor_id' => $user->proveedor->id,
                'carta_proveedor_id' => $carta->proveedor_id,
                'user_email' => $user->proveedor->email,
                'carta_email' => $carta->proveedor_email,
            ]);
            abort(403, 'No tienes permiso para ver esta carta');
        }

        return $next($request);
    }
}
