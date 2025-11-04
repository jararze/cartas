<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // Cartas
            'ver_cartas',
            'crear_cartas',
            'editar_cartas',
            'eliminar_cartas',
            'enviar_cartas',

            // Productos
            'ver_productos',
            'crear_productos',
            'editar_productos',
            'eliminar_productos',

            // Actividades
            'ver_actividades',
            'crear_actividades',
            'editar_actividades',
            'eliminar_actividades',
            'registrar_seguimiento',

            // Usuarios
            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',

            // Reportes
            'ver_reportes',
            'exportar_reportes',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::create(['name' => 'Administrador']);
        $coordinadorRole = Role::create(['name' => 'Coordinador']);
        $tecnicoRole = Role::create(['name' => 'Técnico']);
        $proveedorRole = Role::create(['name' => 'Proveedor']);
        $contraparteRole = Role::create(['name' => 'Contraparte']);
        $invitadoRole = Role::create(['name' => 'Invitado']);

        // Asignar permisos a roles
        $adminRole->givePermissionTo(Permission::all());

        $coordinadorRole->givePermissionTo([
            'ver_cartas', 'crear_cartas', 'editar_cartas', 'enviar_cartas',
            'ver_productos', 'crear_productos', 'editar_productos',
            'ver_actividades', 'crear_actividades', 'editar_actividades',
            'registrar_seguimiento', 'ver_reportes', 'exportar_reportes'
        ]);

        $tecnicoRole->givePermissionTo([
            'ver_cartas', 'ver_productos', 'ver_actividades',
            'editar_actividades', 'registrar_seguimiento'
        ]);

        $proveedorRole->givePermissionTo([
            'ver_cartas', 'ver_productos', 'ver_actividades', 'registrar_seguimiento'
        ]);

        $contraparteRole->givePermissionTo([
            'ver_cartas', 'ver_productos', 'ver_actividades'
        ]);

        $invitadoRole->givePermissionTo([
            'ver_cartas'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
