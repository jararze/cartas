<?php

namespace Database\Seeders;

use App\Models\Carta;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CartaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@fao.org')->first();
        $coordinador = User::where('email', 'coordinador@fao.org')->first();

        // Carta 1 - En ejecución
        Carta::create([
            'codigo' => 'CARTA-2025-001',
            'nombre_proyecto' => 'Proyecto de Seguridad Alimentaria Bolivia',
            'descripcion_servicios' => 'Implementación de sistemas de seguridad alimentaria en comunidades rurales de Bolivia, incluyendo distribución de semillas, capacitación técnica y seguimiento.',
            'creado_por' => $admin->id,
            'oficina_fao' => $admin->office,
            'responsable_fao_nombre' => $admin->name,
            'responsable_fao_email' => $admin->email,
            'responsable_fao_telefono' => $admin->phone,
            'antecedentes' => 'Las comunidades rurales de Bolivia enfrentan desafíos significativos en seguridad alimentaria debido a factores climáticos y económicos.',
            'servicios_requeridos' => 'Distribución de semillas certificadas, capacitación en técnicas agrícolas sostenibles, implementación de sistemas de riego.',
            'productos_requeridos' => [
                'Diagnóstico situacional',
                'Distribución de semillas',
                'Capacitación técnica'
            ],
            'fecha_inicio' => '2025-01-01',
            'fecha_fin' => '2025-08-31',
            'monto_total' => 96500.00,
            'moneda' => 'USD',
            'proveedor_nombre' => 'Ana Silva',
            'proveedor_email' => 'proveedor@empresa.com',
            'proveedor_telefono' => '+591 7 9876543',
            'proveedor_empresa' => 'Empresa XYZ',
            'estado' => 'en_ejecucion',
            'fecha_envio' => now()->subDays(30),
            'fecha_vista' => now()->subDays(28),
            'fecha_respuesta' => now()->subDays(25),
        ]);

        // Carta 2 - Enviada
        Carta::create([
            'codigo' => 'CARTA-2025-002',
            'nombre_proyecto' => 'Capacitación en Agricultura Sostenible',
            'descripcion_servicios' => 'Programa de capacitación especializada para agricultores locales en técnicas de agricultura sostenible y conservación de suelos.',
            'creado_por' => $coordinador->id,
            'oficina_fao' => $coordinador->office,
            'responsable_fao_nombre' => $coordinador->name,
            'responsable_fao_email' => $coordinador->email,
            'antecedentes' => 'Necesidad de fortalecer las capacidades técnicas de los agricultores en prácticas sostenibles.',
            'servicios_requeridos' => 'Diseño e implementación de módulos de capacitación, materiales didácticos, evaluación de impacto.',
            'productos_requeridos' => [
                'Módulos de capacitación'
            ],
            'fecha_inicio' => '2025-10-01',
            'fecha_fin' => '2025-10-31',
            'monto_total' => 3500.00,
            'moneda' => 'USD',
            'proveedor_email' => 'consultor@capacitacion.com',
            'estado' => 'enviada',
            'fecha_envio' => now()->subDays(5),
        ]);

        // Carta 3 - Borrador
        Carta::create([
            'codigo' => 'CARTA-2025-003',
            'nombre_proyecto' => 'Desarrollo de Infraestructura Agrícola',
            'descripcion_servicios' => 'Construcción de sistemas de riego y almacenamiento en zonas rurales prioritarias.',
            'creado_por' => $admin->id,
            'oficina_fao' => $admin->office,
            'responsable_fao_nombre' => $admin->name,
            'responsable_fao_email' => $admin->email,
            'antecedentes' => 'Las comunidades requieren infraestructura básica para mejorar la productividad agrícola.',
            'servicios_requeridos' => 'Diseño, construcción e instalación de sistemas de riego y almacenes.',
            'productos_requeridos' => [
                'Sistema de riego',
                'Almacenes rurales'
            ],
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-06-30',
            'monto_total' => 93000.00,
            'moneda' => 'USD',
            'estado' => 'borrador',
        ]);
    }
}
