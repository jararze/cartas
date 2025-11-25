<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    public function update(User $user, Producto $producto): bool
    {
        // Admin y Coordinador pueden editar cualquier producto
        if ($user->hasRole(['Administrador', 'Coordinador'])) {
            return true;
        }

        // Proveedor solo puede editar productos de sus cartas
        if ($user->hasRole('Proveedor') && $user->proveedor) {
            return $producto->carta->proveedor_id === $user->proveedor->id;
        }

        return false;
    }
}
