<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Juan FAO Manager',
            'email' => 'admin@fao.org',
            'password' => 'password',
            'phone' => '+591 2 1234567',
            'position' => 'Representante FAO',
            'office' => 'FAO Bolivia',
            'country' => 'Bolivia',
            'user_type' => 'fao',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Administrador');

        // Usuario Coordinador
        $coordinador = User::create([
            'name' => 'María Rodríguez',
            'email' => 'coordinador@fao.org',
            'password' => 'password',
            'phone' => '+591 2 1234568',
            'position' => 'Coordinadora de Proyectos',
            'office' => 'FAO Bolivia',
            'country' => 'Bolivia',
            'user_type' => 'fao',
            'email_verified_at' => now(),
        ]);
        $coordinador->assignRole('Coordinador');

        // Usuario Técnico
        $tecnico = User::create([
            'name' => 'Carlos Mendoza',
            'email' => 'tecnico@fao.org',
            'password' => 'password',
            'phone' => '+591 2 1234569',
            'position' => 'Técnico de Campo',
            'office' => 'FAO Bolivia',
            'country' => 'Bolivia',
            'user_type' => 'fao',
            'email_verified_at' => now(),
        ]);
        $tecnico->assignRole('Técnico');

        // Usuario Proveedor
        $proveedor = User::create([
            'name' => 'Ana Silva Empresa',
            'email' => 'proveedor@empresa.com',
            'password' => 'password',
            'phone' => '+591 7 9876543',
            'position' => 'Gerente General',
            'office' => 'Empresa XYZ',
            'country' => 'Bolivia',
            'user_type' => 'proveedor',
            'email_verified_at' => now(),
        ]);
        $proveedor->assignRole('Proveedor');
    }
}
