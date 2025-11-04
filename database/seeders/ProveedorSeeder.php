<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proveedores = [
            [
                'nombre' => 'María Elena Rodríguez',
                'email' => 'maria.rodriguez@agroconsulting.com',
                'telefono' => '+591 2 2441789',
                'whatsapp' => '+591 7 1234567',
                'empresa' => 'Agro Consulting Bolivia',
                'contacto_principal' => 'María Elena Rodríguez',
                'cargo' => 'Gerente General',
                'especialidades' => ['Agricultura Sostenible', 'Capacitación Rural', 'Seguridad Alimentaria'],
                'notas' => 'Especialista en proyectos de desarrollo rural con más de 15 años de experiencia.',
                'activo' => true,
            ],
            [
                'nombre' => 'Carlos Mendoza Silva',
                'email' => 'carlos.mendoza@consultecnico.bo',
                'telefono' => '+591 4 4521234',
                'whatsapp' => '+591 7 2345678',
                'empresa' => 'Consultoría Técnica del Sur',
                'contacto_principal' => 'Carlos Mendoza Silva',
                'cargo' => 'Director Técnico',
                'especialidades' => ['Riego Tecnificado', 'Infraestructura Agrícola', 'Estudios de Suelo'],
                'notas' => 'Experto en sistemas de riego y manejo de recursos hídricos.',
                'activo' => true,
            ],
            [
                'nombre' => 'Ana Patricia Vargas',
                'email' => 'ana.vargas@innovagro.org',
                'telefono' => '+591 3 3567890',
                'whatsapp' => '+591 7 3456789',
                'empresa' => 'InnovAgro Solutions',
                'contacto_principal' => 'Ana Patricia Vargas',
                'cargo' => 'Coordinadora de Proyectos',
                'especialidades' => ['Tecnología Agrícola', 'Semillas Mejoradas', 'Asistencia Técnica'],
                'notas' => 'Especializada en implementación de tecnologías innovadoras en agricultura.',
                'activo' => true,
            ],
            [
                'nombre' => 'Roberto Quispe Mamani',
                'email' => 'roberto.quispe@desarrollorural.gov.bo',
                'telefono' => '+591 2 2789456',
                'whatsapp' => '+591 7 4567890',
                'empresa' => 'Ministerio de Desarrollo Rural',
                'contacto_principal' => 'Roberto Quispe Mamani',
                'cargo' => 'Técnico Especialista',
                'especialidades' => ['Políticas Públicas', 'Desarrollo Comunitario', 'Organización Rural'],
                'notas' => 'Funcionario público con amplia experiencia en desarrollo rural.',
                'activo' => true,
            ],
            [
                'nombre' => 'Laura Fernández Cruz',
                'email' => 'laura.fernandez@ecosistemas.com',
                'telefono' => '+591 4 4123789',
                'whatsapp' => '+591 7 5678901',
                'empresa' => 'Ecosistemas Sustentables Ltda.',
                'contacto_principal' => 'Laura Fernández Cruz',
                'cargo' => 'Bióloga Senior',
                'especialidades' => ['Medio Ambiente', 'Conservación', 'Estudios de Impacto'],
                'notas' => 'Especialista en conservación y manejo sostenible de recursos naturales.',
                'activo' => true,
            ],
            [
                'nombre' => 'José Antonio Morales',
                'email' => 'jose.morales@capacitacionagro.edu.bo',
                'telefono' => '+591 3 3234567',
                'whatsapp' => '+591 7 6789012',
                'empresa' => 'Centro de Capacitación Agrícola',
                'contacto_principal' => 'José Antonio Morales',
                'cargo' => 'Instructor Principal',
                'especialidades' => ['Educación Rural', 'Transferencia Tecnológica', 'Extensión Agrícola'],
                'notas' => 'Educador con más de 20 años formando agricultores.',
                'activo' => true,
            ],
            [
                'nombre' => 'Carmen Rosa Gutiérrez',
                'email' => 'carmen.gutierrez@mujerrural.org',
                'telefono' => '+591 2 2345123',
                'whatsapp' => '+591 7 7890123',
                'empresa' => 'Fundación Mujer Rural',
                'contacto_principal' => 'Carmen Rosa Gutiérrez',
                'cargo' => 'Directora Ejecutiva',
                'especialidades' => ['Género y Desarrollo', 'Empoderamiento Femenino', 'Microfinanzas'],
                'notas' => 'Líder en proyectos de empoderamiento de mujeres rurales.',
                'activo' => true,
            ],
            [
                'nombre' => 'Diego Alejandro Paz',
                'email' => 'diego.paz@logisticaagro.com',
                'telefono' => '+591 4 4567234',
                'whatsapp' => '+591 7 8901234',
                'empresa' => 'Logística Agroindustrial S.A.',
                'contacto_principal' => 'Diego Alejandro Paz',
                'cargo' => 'Gerente de Operaciones',
                'especialidades' => ['Cadenas de Suministro', 'Almacenamiento', 'Distribución'],
                'notas' => 'Experto en logística y distribución de productos agrícolas.',
                'activo' => true,
            ],
            [
                'nombre' => 'Silvia Beatriz Mamani',
                'email' => 'silvia.mamani@semillas.coop',
                'telefono' => '+591 3 3678901',
                'whatsapp' => '+591 7 9012345',
                'empresa' => 'Cooperativa de Productores de Semillas',
                'contacto_principal' => 'Silvia Beatriz Mamani',
                'cargo' => 'Presidenta',
                'especialidades' => ['Producción de Semillas', 'Cooperativismo', 'Agricultura Familiar'],
                'notas' => 'Líder cooperativista especializada en producción de semillas certificadas.',
                'activo' => true,
            ],
            [
                'nombre' => 'Fernando Raúl Cortez',
                'email' => 'fernando.cortez@freelancer.com',
                'telefono' => '+591 2 2890567',
                'whatsapp' => '+591 7 0123456',
                'empresa' => null, // Freelancer sin empresa
                'contacto_principal' => 'Fernando Raúl Cortez',
                'cargo' => 'Consultor Independiente',
                'especialidades' => ['Monitoreo y Evaluación', 'Sistematización', 'Investigación Rural'],
                'notas' => 'Consultor independiente con experiencia en M&E de proyectos rurales.',
                'activo' => true,
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::create($proveedor);
        }
    }
}
