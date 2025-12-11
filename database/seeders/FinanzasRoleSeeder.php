<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FinanzasRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos de finanzas
        $permisos = [
            'ver desembolsos',
            'procesar desembolsos',
            'aprobar desembolsos',
            'rechazar desembolsos',
            'ver reportes financieros',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear rol Finanzas
        $rolFinanzas = Role::firstOrCreate(['name' => 'Finanzas']);
        $rolFinanzas->syncPermissions($permisos);

        // También dar permisos al Administrador
        $admin = Role::where('name', 'Administrador')->first();
        if ($admin) {
            $admin->givePermissionTo($permisos);
        }
    }
}
