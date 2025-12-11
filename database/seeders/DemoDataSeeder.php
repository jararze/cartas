<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ========================================
        // IMPORTANTE: Ajusta estos IDs según tu BD
        // ========================================
        $adminId = 11;        // Usuario Administrador FAO
        $coordinadorId = 11;  // Usuario Coordinador FAO
        $proveedorUserId = 9; // Usuario Proveedor (si existe)
        $proveedorId = 3;     // ID en tabla proveedors

        $now = Carbon::now();

        // ========================================
        // CARTA 1: Seguridad Alimentaria (En Ejecución - Con actividades variadas)
        // ========================================
        $carta1Id = DB::table('cartas')->insertGetId([
            'codigo' => 'CDA-2025-001',
            'nombre_proyecto' => 'Fortalecimiento de la Seguridad Alimentaria en Comunidades Rurales del Altiplano',
            'descripcion_servicios' => 'Implementación de sistemas de producción agrícola sostenible, capacitación a productores locales y distribución de insumos agrícolas para mejorar la seguridad alimentaria en 15 comunidades del departamento de La Paz.',
            'creado_por' => $adminId,
            'oficina_fao' => 'FAO Bolivia - La Paz',
            'responsable_fao_nombre' => 'Dr. Carlos Mendoza Quispe',
            'responsable_fao_email' => 'carlos.mendoza@fao.org',
            'responsable_fao_telefono' => '+591 2 2795544',
            'antecedentes' => 'Las comunidades rurales del Altiplano boliviano enfrentan desafíos significativos en términos de seguridad alimentaria debido a factores climáticos adversos, limitado acceso a tecnologías agrícolas y escasa capacitación técnica. Este proyecto busca abordar estas problemáticas de manera integral.',
            'servicios_requeridos' => 'Consultoría técnica agrícola, provisión de semillas certificadas, sistemas de riego por goteo, capacitación en técnicas de cultivo sostenible, monitoreo y evaluación de impacto.',
            'productos_requeridos' => json_encode(['Semillas de papa', 'Semillas de quinua', 'Sistemas de riego', 'Herramientas agrícolas', 'Fertilizantes orgánicos']),
            'fecha_inicio' => '2025-01-15',
            'fecha_fin' => '2025-12-31',
            'monto_total' => 185000.00,
            'moneda' => 'USD',
            'proveedor_id' => $proveedorId,
            'estado' => 'en_ejecucion',
            'tipo_envio' => 'email',
            'mensaje_invitacion' => 'Estimado proveedor, le invitamos a participar en este importante proyecto de seguridad alimentaria.',
            'fecha_envio' => $now->copy()->subMonths(2),
            'fecha_vista' => $now->copy()->subMonths(2)->addDays(1),
            'fecha_respuesta' => $now->copy()->subMonths(2)->addDays(3),
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        // Productos Carta 1
        $producto1_1 = DB::table('productos')->insertGetId([
            'carta_id' => $carta1Id,
            'nombre' => 'Diagnóstico y Línea Base',
            'descripcion' => 'Evaluación inicial de las comunidades beneficiarias, identificación de necesidades y establecimiento de indicadores base para el proyecto.',
            'indicadores_kpi' => json_encode(['Familias evaluadas', 'Comunidades diagnosticadas', 'Informe de línea base']),
            'fecha_inicio' => '2025-01-15',
            'fecha_fin' => '2025-03-15',
            'presupuesto' => 25000.00,
            'orden' => 1,
            'estado' => 'completado',
            'aprobado_por' => $coordinadorId,
            'fecha_aprobacion' => $now->copy()->subMonth(),
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $producto1_2 = DB::table('productos')->insertGetId([
            'carta_id' => $carta1Id,
            'nombre' => 'Distribución de Insumos Agrícolas',
            'descripcion' => 'Adquisición y distribución de semillas certificadas, fertilizantes orgánicos y herramientas agrícolas a las familias beneficiarias.',
            'indicadores_kpi' => json_encode(['Kg de semillas distribuidas', 'Familias beneficiadas', 'Herramientas entregadas']),
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-06-30',
            'presupuesto' => 85000.00,
            'orden' => 2,
            'estado' => 'en_progreso',
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $producto1_3 = DB::table('productos')->insertGetId([
            'carta_id' => $carta1Id,
            'nombre' => 'Capacitación Técnica',
            'descripcion' => 'Programa de formación en técnicas de agricultura sostenible, manejo de plagas y gestión del agua para productores locales.',
            'indicadores_kpi' => json_encode(['Talleres realizados', 'Productores capacitados', 'Certificaciones emitidas']),
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-10-31',
            'presupuesto' => 45000.00,
            'orden' => 3,
            'estado' => 'pendiente',
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $producto1_4 = DB::table('productos')->insertGetId([
            'carta_id' => $carta1Id,
            'nombre' => 'Monitoreo y Evaluación',
            'descripcion' => 'Seguimiento continuo del proyecto, medición de indicadores de impacto y elaboración de informes de avance.',
            'indicadores_kpi' => json_encode(['Visitas de monitoreo', 'Informes elaborados', 'Indicadores medidos']),
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-12-31',
            'presupuesto' => 30000.00,
            'orden' => 4,
            'estado' => 'en_progreso',
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        // Actividades Producto 1.1 (Diagnóstico - COMPLETADO)
        $act1_1_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_1,
            'nombre' => 'Levantamiento de información socioeconómica',
            'descripcion' => 'Aplicación de encuestas y entrevistas a familias de las 15 comunidades para caracterización socioeconómica.',
            'monto' => 12000.00,
            'gasto_acumulado' => 12000.00,
            'fecha_inicio' => '2025-01-15',
            'fecha_fin' => '2025-02-15',
            'fecha_inicio_real' => '2025-01-15',
            'fecha_fin_real' => '2025-02-10',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'observaciones' => 'Actividad completada exitosamente. Se logró encuestar a 450 familias.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $act1_1_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_1,
            'nombre' => 'Elaboración de informe de línea base',
            'descripcion' => 'Sistematización de datos recopilados y elaboración del documento de línea base del proyecto.',
            'monto' => 8000.00,
            'gasto_acumulado' => 8000.00,
            'fecha_inicio' => '2025-02-15',
            'fecha_fin' => '2025-03-15',
            'fecha_inicio_real' => '2025-02-12',
            'fecha_fin_real' => '2025-03-10',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $act1_1_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_1,
            'nombre' => 'Mapeo de actores locales',
            'descripcion' => 'Identificación y mapeo de organizaciones, autoridades y actores clave en las comunidades.',
            'monto' => 5000.00,
            'gasto_acumulado' => 5000.00,
            'fecha_inicio' => '2025-01-20',
            'fecha_fin' => '2025-02-28',
            'fecha_inicio_real' => '2025-01-20',
            'fecha_fin_real' => '2025-02-25',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'media',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        // Actividades Producto 1.2 (Distribución - EN PROGRESO con SOBREGIRO)
        $act1_2_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_2,
            'nombre' => 'Adquisición de semillas certificadas',
            'descripcion' => 'Compra de semillas de papa, quinua y hortalizas certificadas para distribución a beneficiarios.',
            'monto' => 45000.00,
            'gasto_acumulado' => 52500.00, // ⚠️ SOBREGIRO 16.7%
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-04-30',
            'fecha_inicio_real' => '2025-03-05',
            'linea_presupuestaria' => 'Equipamiento',
            'estado' => 'en_curso',
            'progreso' => 85.00,
            'prioridad' => 'critica',
            'observaciones' => 'Se requirió compra adicional de semillas debido a alta demanda. Sobregiro justificado por incremento de beneficiarios.',
            'dificultades' => 'Incremento de precios por temporada alta. Se sumaron 50 familias más al programa.',
            'proximos_pasos' => 'Gestionar aprobación de sobregiro con coordinación FAO.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act1_2_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_2,
            'nombre' => 'Logística de distribución',
            'descripcion' => 'Transporte y entrega de insumos a las 15 comunidades beneficiarias.',
            'monto' => 15000.00,
            'gasto_acumulado' => 9500.00,
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-05-31',
            'fecha_inicio_real' => '2025-04-05',
            'linea_presupuestaria' => 'Logística',
            'estado' => 'en_curso',
            'progreso' => 65.00,
            'prioridad' => 'alta',
            'observaciones' => 'Distribución avanzando según cronograma. 10 comunidades atendidas.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act1_2_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_2,
            'nombre' => 'Entrega de herramientas agrícolas',
            'descripcion' => 'Adquisición y distribución de herramientas básicas: azadones, palas, rastrillos, etc.',
            'monto' => 18000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-05-01',
            'fecha_fin' => '2025-06-30',
            'linea_presupuestaria' => 'Equipamiento',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'media',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // Actividad CANCELADA
        $act1_2_4 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_2,
            'nombre' => 'Implementación de invernaderos familiares',
            'descripcion' => 'Construcción de 50 invernaderos familiares para producción protegida.',
            'monto' => 7000.00,
            'gasto_acumulado' => 1200.00,
            'fecha_inicio' => '2025-04-15',
            'fecha_fin' => '2025-06-15',
            'fecha_inicio_real' => '2025-04-20',
            'linea_presupuestaria' => 'Infraestructura',
            'estado' => 'cancelado',
            'progreso' => 15.00,
            'prioridad' => 'media',
            'observaciones' => 'Actividad cancelada por reasignación de recursos.',
            'motivo_cancelacion' => 'Debido al sobregiro en adquisición de semillas, se decidió postergar esta actividad para una siguiente fase del proyecto. Los fondos fueron reasignados para cubrir la demanda adicional de semillas.',
            'estado_cancelacion' => 'aprobada',
            'fecha_solicitud_cancelacion' => $now->copy()->subDays(15),
            'solicitado_por' => $coordinadorId,
            'aprobado_por' => $adminId,
            'fecha_respuesta_cancelacion' => $now->copy()->subDays(12),
            'respuesta_cancelacion' => 'Se aprueba cancelación. Los fondos no ejecutados serán reasignados a la actividad de adquisición de semillas para cubrir el incremento de beneficiarios.',
            'estado_anterior_cancelacion' => 'en_curso',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // Actividades Producto 1.3 (Capacitación - PENDIENTE)
        $act1_3_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_3,
            'nombre' => 'Diseño de módulos de capacitación',
            'descripcion' => 'Elaboración de contenidos y materiales didácticos para los talleres de formación.',
            'monto' => 8000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-04-30',
            'linea_presupuestaria' => 'Capacitación',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'alta',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act1_3_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_3,
            'nombre' => 'Talleres de agricultura sostenible',
            'descripcion' => 'Realización de 30 talleres prácticos en las comunidades sobre técnicas de cultivo sostenible.',
            'monto' => 25000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-05-01',
            'fecha_fin' => '2025-09-30',
            'linea_presupuestaria' => 'Capacitación',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'alta',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act1_3_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_3,
            'nombre' => 'Certificación de productores',
            'descripcion' => 'Evaluación y certificación de productores que completen el programa de formación.',
            'monto' => 12000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-10-01',
            'fecha_fin' => '2025-10-31',
            'linea_presupuestaria' => 'Capacitación',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'media',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // Actividades Producto 1.4 (Monitoreo - EN PROGRESO)
        $act1_4_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_4,
            'nombre' => 'Visitas de monitoreo mensuales',
            'descripcion' => 'Realización de visitas de seguimiento a las comunidades para verificar avances.',
            'monto' => 18000.00,
            'gasto_acumulado' => 7200.00,
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-12-31',
            'fecha_inicio_real' => '2025-02-01',
            'linea_presupuestaria' => 'Monitoreo',
            'estado' => 'en_curso',
            'progreso' => 40.00,
            'prioridad' => 'media',
            'observaciones' => '4 visitas de monitoreo realizadas. Avance acorde al cronograma.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act1_4_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto1_4,
            'nombre' => 'Elaboración de informes trimestrales',
            'descripcion' => 'Preparación de informes de avance trimestral para FAO y contrapartes.',
            'monto' => 12000.00,
            'gasto_acumulado' => 3000.00,
            'fecha_inicio' => '2025-03-15',
            'fecha_fin' => '2025-12-31',
            'fecha_inicio_real' => '2025-03-15',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'en_curso',
            'progreso' => 25.00,
            'prioridad' => 'alta',
            'observaciones' => 'Primer informe trimestral entregado y aprobado.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // ========================================
        // CARTA 2: Infraestructura de Riego (En Ejecución - Con actividad pendiente cancelación)
        // ========================================
        $carta2Id = DB::table('cartas')->insertGetId([
            'codigo' => 'CDA-2025-002',
            'nombre_proyecto' => 'Mejoramiento de Infraestructura de Riego en el Valle Alto de Cochabamba',
            'descripcion_servicios' => 'Construcción y rehabilitación de sistemas de riego para optimizar el uso del agua en la producción agrícola del Valle Alto.',
            'creado_por' => $adminId,
            'oficina_fao' => 'FAO Bolivia - Cochabamba',
            'responsable_fao_nombre' => 'Ing. María Elena Vargas',
            'responsable_fao_email' => 'maria.vargas@fao.org',
            'responsable_fao_telefono' => '+591 4 4525566',
            'antecedentes' => 'El Valle Alto de Cochabamba es una de las zonas agrícolas más importantes de Bolivia. Sin embargo, la infraestructura de riego existente presenta serias deficiencias que limitan la productividad agrícola.',
            'servicios_requeridos' => 'Estudios de ingeniería, construcción de canales, instalación de compuertas, capacitación en operación y mantenimiento.',
            'productos_requeridos' => json_encode(['Compuertas metálicas', 'Tuberías PVC', 'Cemento', 'Geomembrana']),
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-08-31',
            'monto_total' => 120000.00,
            'moneda' => 'USD',
            'proveedor_id' => $proveedorId,
            'estado' => 'en_ejecucion',
            'tipo_envio' => 'email',
            'fecha_envio' => $now->copy()->subMonths(3),
            'fecha_vista' => $now->copy()->subMonths(3)->addDays(1),
            'fecha_respuesta' => $now->copy()->subMonths(3)->addDays(2),
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        // Productos Carta 2
        $producto2_1 = DB::table('productos')->insertGetId([
            'carta_id' => $carta2Id,
            'nombre' => 'Estudios Técnicos',
            'descripcion' => 'Elaboración de estudios de ingeniería y diseño de las obras de riego.',
            'indicadores_kpi' => json_encode(['Estudios completados', 'Planos aprobados']),
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-03-31',
            'presupuesto' => 20000.00,
            'orden' => 1,
            'estado' => 'completado',
            'aprobado_por' => $adminId,
            'fecha_aprobacion' => $now->copy()->subMonths(2),
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $producto2_2 = DB::table('productos')->insertGetId([
            'carta_id' => $carta2Id,
            'nombre' => 'Construcción de Infraestructura',
            'descripcion' => 'Ejecución de obras civiles: canales, reservorios y sistemas de distribución.',
            'indicadores_kpi' => json_encode(['Metros lineales construidos', 'Reservorios terminados']),
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-07-31',
            'presupuesto' => 85000.00,
            'orden' => 2,
            'estado' => 'en_progreso',
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $producto2_3 = DB::table('productos')->insertGetId([
            'carta_id' => $carta2Id,
            'nombre' => 'Fortalecimiento Organizacional',
            'descripcion' => 'Capacitación a organizaciones de regantes en operación y mantenimiento.',
            'indicadores_kpi' => json_encode(['Organizaciones capacitadas', 'Manuales entregados']),
            'fecha_inicio' => '2025-06-01',
            'fecha_fin' => '2025-08-31',
            'presupuesto' => 15000.00,
            'orden' => 3,
            'estado' => 'pendiente',
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        // Actividades Carta 2
        $act2_1_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_1,
            'nombre' => 'Estudio hidrológico',
            'descripcion' => 'Análisis de disponibilidad y calidad de agua para el sistema de riego.',
            'monto' => 8000.00,
            'gasto_acumulado' => 8000.00,
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-02-28',
            'fecha_inicio_real' => '2025-02-01',
            'fecha_fin_real' => '2025-02-25',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $act2_1_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_1,
            'nombre' => 'Diseño de ingeniería',
            'descripcion' => 'Elaboración de planos y especificaciones técnicas de las obras.',
            'monto' => 12000.00,
            'gasto_acumulado' => 12000.00,
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-03-31',
            'fecha_inicio_real' => '2025-03-01',
            'fecha_fin_real' => '2025-03-28',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        // Actividad con SOBREGIRO ALTO
        $act2_2_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_2,
            'nombre' => 'Construcción de canal principal',
            'descripcion' => 'Construcción de 2.5 km de canal revestido de concreto.',
            'monto' => 45000.00,
            'gasto_acumulado' => 58000.00, // ⚠️ SOBREGIRO 28.9%
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-06-15',
            'fecha_inicio_real' => '2025-04-05',
            'linea_presupuestaria' => 'Infraestructura',
            'estado' => 'en_curso',
            'progreso' => 90.00,
            'prioridad' => 'critica',
            'observaciones' => 'Sobregiro debido a condiciones de suelo imprevistas que requirieron mayor excavación y material.',
            'dificultades' => 'Se encontró roca a menor profundidad de lo esperado, requiriendo uso de maquinaria pesada adicional.',
            'proximos_pasos' => 'Completar últimos 250 metros y gestionar aprobación de sobregiro.',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act2_2_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_2,
            'nombre' => 'Construcción de reservorio',
            'descripcion' => 'Construcción de reservorio de 500 m³ de capacidad.',
            'monto' => 25000.00,
            'gasto_acumulado' => 18000.00,
            'fecha_inicio' => '2025-05-01',
            'fecha_fin' => '2025-07-15',
            'fecha_inicio_real' => '2025-05-03',
            'linea_presupuestaria' => 'Infraestructura',
            'estado' => 'en_curso',
            'progreso' => 72.00,
            'prioridad' => 'alta',
            'observaciones' => 'Excavación completada, se inicia revestimiento.',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // Actividad PENDIENTE DE CANCELACIÓN
        $act2_2_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_2,
            'nombre' => 'Sistema de riego tecnificado',
            'descripcion' => 'Instalación de sistema de riego por aspersión en parcelas demostrativas.',
            'monto' => 15000.00,
            'gasto_acumulado' => 2500.00,
            'fecha_inicio' => '2025-06-01',
            'fecha_fin' => '2025-07-31',
            'fecha_inicio_real' => '2025-06-05',
            'linea_presupuestaria' => 'Equipamiento',
            'estado' => 'pendiente_cancelacion', // ⚠️ PENDIENTE CANCELACIÓN
            'progreso' => 15.00,
            'prioridad' => 'media',
            'observaciones' => 'Se solicita cancelación para reasignar fondos al canal principal.',
            'motivo_cancelacion' => 'Debido al sobregiro significativo en la construcción del canal principal (condiciones de suelo imprevistas), se requiere reasignar los fondos de esta actividad. Se propone postergar el sistema de riego tecnificado para una segunda fase.',
            'estado_cancelacion' => 'pendiente',
            'fecha_solicitud_cancelacion' => $now->copy()->subDays(3),
            'solicitado_por' => $coordinadorId,
            'estado_anterior_cancelacion' => 'en_curso',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(1),
            'updated_at' => $now,
        ]);

        $act2_3_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_3,
            'nombre' => 'Capacitación a comités de riego',
            'descripcion' => 'Talleres de formación en gestión y operación de sistemas de riego.',
            'monto' => 10000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-06-01',
            'fecha_fin' => '2025-08-15',
            'linea_presupuestaria' => 'Capacitación',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'media',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $act2_3_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto2_3,
            'nombre' => 'Elaboración de manuales O&M',
            'descripcion' => 'Desarrollo de manuales de operación y mantenimiento del sistema.',
            'monto' => 5000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-07-01',
            'fecha_fin' => '2025-08-31',
            'linea_presupuestaria' => 'Consultoría',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'baja',
            'responsable_id' => $adminId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // ========================================
        // CARTA 3: Ganadería Sostenible (Más avanzada - Con cancelación rechazada)
        // ========================================
        $carta3Id = DB::table('cartas')->insertGetId([
            'codigo' => 'CDA-2025-003',
            'nombre_proyecto' => 'Desarrollo de la Ganadería Sostenible en el Chaco Boliviano',
            'descripcion_servicios' => 'Implementación de prácticas ganaderas sostenibles, mejoramiento genético y establecimiento de sistemas silvopastoriles en comunidades del Chaco.',
            'creado_por' => $coordinadorId,
            'oficina_fao' => 'FAO Bolivia - Santa Cruz',
            'responsable_fao_nombre' => 'MVZ. Roberto Suárez Flores',
            'responsable_fao_email' => 'roberto.suarez@fao.org',
            'responsable_fao_telefono' => '+591 3 3445566',
            'antecedentes' => 'El Chaco boliviano enfrenta desafíos de degradación de pasturas y baja productividad ganadera. Este proyecto busca introducir prácticas sostenibles que mejoren la producción sin afectar el medio ambiente.',
            'servicios_requeridos' => 'Asistencia técnica veterinaria, provisión de material genético, establecimiento de parcelas silvopastoriles, capacitación a productores.',
            'productos_requeridos' => json_encode(['Semen bovino', 'Semillas de pastos', 'Plantines forestales', 'Equipos veterinarios']),
            'fecha_inicio' => '2025-01-01',
            'fecha_fin' => '2025-10-31',
            'monto_total' => 95000.00,
            'moneda' => 'USD',
            'proveedor_id' => $proveedorId,
            'estado' => 'en_ejecucion',
            'tipo_envio' => 'ambos',
            'fecha_envio' => $now->copy()->subMonths(4),
            'fecha_vista' => $now->copy()->subMonths(4)->addHours(12),
            'fecha_respuesta' => $now->copy()->subMonths(4)->addDays(1),
            'created_at' => $now->copy()->subMonths(5),
            'updated_at' => $now,
        ]);

        // Productos Carta 3
        $producto3_1 = DB::table('productos')->insertGetId([
            'carta_id' => $carta3Id,
            'nombre' => 'Mejoramiento Genético',
            'descripcion' => 'Programa de inseminación artificial y mejoramiento del hato ganadero.',
            'indicadores_kpi' => json_encode(['Vacas inseminadas', 'Tasa de preñez', 'Terneros nacidos']),
            'fecha_inicio' => '2025-01-01',
            'fecha_fin' => '2025-06-30',
            'presupuesto' => 35000.00,
            'orden' => 1,
            'estado' => 'completado',
            'aprobado_por' => $adminId,
            'fecha_aprobacion' => $now->copy()->subWeeks(2),
            'created_at' => $now->copy()->subMonths(5),
            'updated_at' => $now,
        ]);

        $producto3_2 = DB::table('productos')->insertGetId([
            'carta_id' => $carta3Id,
            'nombre' => 'Sistemas Silvopastoriles',
            'descripcion' => 'Establecimiento de 100 hectáreas de sistemas silvopastoriles demostrativos.',
            'indicadores_kpi' => json_encode(['Hectáreas establecidas', 'Productores participantes', 'Especies plantadas']),
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-08-31',
            'presupuesto' => 40000.00,
            'orden' => 2,
            'estado' => 'en_progreso',
            'created_at' => $now->copy()->subMonths(5),
            'updated_at' => $now,
        ]);

        $producto3_3 = DB::table('productos')->insertGetId([
            'carta_id' => $carta3Id,
            'nombre' => 'Capacitación y Asistencia Técnica',
            'descripcion' => 'Programa de formación en manejo ganadero sostenible y sanidad animal.',
            'indicadores_kpi' => json_encode(['Productores capacitados', 'Visitas técnicas', 'Eventos realizados']),
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-10-31',
            'presupuesto' => 20000.00,
            'orden' => 3,
            'estado' => 'en_progreso',
            'created_at' => $now->copy()->subMonths(5),
            'updated_at' => $now,
        ]);

        // Actividades Carta 3 - Producto 1 (COMPLETADO)
        $act3_1_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_1,
            'nombre' => 'Adquisición de material genético',
            'descripcion' => 'Compra de semen y embriones de razas mejoradas adaptadas al Chaco.',
            'monto' => 15000.00,
            'gasto_acumulado' => 15000.00,
            'fecha_inicio' => '2025-01-01',
            'fecha_fin' => '2025-02-15',
            'fecha_inicio_real' => '2025-01-05',
            'fecha_fin_real' => '2025-02-10',
            'linea_presupuestaria' => 'Equipamiento',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'observaciones' => 'Material genético de alta calidad adquirido de centros certificados.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(5),
            'updated_at' => $now,
        ]);

        $act3_1_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_1,
            'nombre' => 'Campaña de inseminación artificial',
            'descripcion' => 'Ejecución del programa de inseminación en 500 vientres.',
            'monto' => 12000.00,
            'gasto_acumulado' => 12000.00,
            'fecha_inicio' => '2025-02-15',
            'fecha_fin' => '2025-05-31',
            'fecha_inicio_real' => '2025-02-20',
            'fecha_fin_real' => '2025-05-25',
            'linea_presupuestaria' => 'Servicios',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'critica',
            'observaciones' => 'Se logró inseminar 520 vientres con tasa de concepción del 65%.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $act3_1_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_1,
            'nombre' => 'Seguimiento de gestaciones',
            'descripcion' => 'Diagnóstico de gestación y monitoreo de vacas preñadas.',
            'monto' => 8000.00,
            'gasto_acumulado' => 8000.00,
            'fecha_inicio' => '2025-04-01',
            'fecha_fin' => '2025-06-30',
            'fecha_inicio_real' => '2025-04-01',
            'fecha_fin_real' => '2025-06-28',
            'linea_presupuestaria' => 'Servicios',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'observaciones' => 'Diagnóstico completado. 338 vacas confirmadas preñadas.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        // Actividades Carta 3 - Producto 2 (EN PROGRESO)
        $act3_2_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_2,
            'nombre' => 'Producción de plantines forestales',
            'descripcion' => 'Establecimiento de vivero y producción de 50,000 plantines de especies nativas.',
            'monto' => 12000.00,
            'gasto_acumulado' => 10500.00,
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-05-31',
            'fecha_inicio_real' => '2025-03-05',
            'fecha_fin_real' => '2025-05-28',
            'linea_presupuestaria' => 'Equipamiento',
            'estado' => 'finalizado',
            'progreso' => 100.00,
            'prioridad' => 'alta',
            'observaciones' => 'Vivero establecido con producción de 52,000 plantines listos para trasplante.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $act3_2_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_2,
            'nombre' => 'Establecimiento de parcelas silvopastoriles',
            'descripcion' => 'Plantación y establecimiento de sistemas silvopastoriles en fincas ganaderas.',
            'monto' => 20000.00,
            'gasto_acumulado' => 14000.00,
            'fecha_inicio' => '2025-06-01',
            'fecha_fin' => '2025-08-31',
            'fecha_inicio_real' => '2025-06-05',
            'linea_presupuestaria' => 'Infraestructura',
            'estado' => 'en_curso',
            'progreso' => 70.00,
            'prioridad' => 'alta',
            'observaciones' => '70 hectáreas establecidas de las 100 planificadas.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // Actividad con cancelación RECHAZADA
        $act3_2_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_2,
            'nombre' => 'Instalación de cercos eléctricos',
            'descripcion' => 'Instalación de cercos eléctricos para manejo rotacional de potreros.',
            'monto' => 8000.00,
            'gasto_acumulado' => 5500.00,
            'fecha_inicio' => '2025-07-01',
            'fecha_fin' => '2025-08-15',
            'fecha_inicio_real' => '2025-07-03',
            'linea_presupuestaria' => 'Equipamiento',
            'estado' => 'en_curso', // Volvió a en_curso después del rechazo
            'progreso' => 65.00,
            'prioridad' => 'media',
            'observaciones' => 'Actividad continúa después de rechazo de solicitud de cancelación.',
            'motivo_cancelacion' => 'Se solicitó cancelar para reasignar fondos a otras actividades del proyecto.',
            'estado_cancelacion' => 'rechazada', // ⚠️ RECHAZADA
            'fecha_solicitud_cancelacion' => $now->copy()->subDays(20),
            'solicitado_por' => $coordinadorId,
            'aprobado_por' => $adminId,
            'fecha_respuesta_cancelacion' => $now->copy()->subDays(18),
            'respuesta_cancelacion' => 'No se aprueba la cancelación. Los cercos eléctricos son esenciales para el éxito del sistema silvopastoril. Se recomienda buscar eficiencias en otras áreas o solicitar ampliación presupuestaria.',
            'estado_anterior_cancelacion' => 'en_curso',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonth(),
            'updated_at' => $now,
        ]);

        // Actividades Carta 3 - Producto 3 (EN PROGRESO)
        $act3_3_1 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_3,
            'nombre' => 'Talleres de manejo ganadero',
            'descripcion' => 'Realización de 12 talleres sobre prácticas de manejo ganadero sostenible.',
            'monto' => 10000.00,
            'gasto_acumulado' => 6500.00,
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-09-30',
            'fecha_inicio_real' => '2025-02-10',
            'linea_presupuestaria' => 'Capacitación',
            'estado' => 'en_curso',
            'progreso' => 58.00,
            'prioridad' => 'alta',
            'observaciones' => '7 talleres realizados con participación de 280 productores.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $act3_3_2 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_3,
            'nombre' => 'Asistencia técnica en finca',
            'descripcion' => 'Visitas de asistencia técnica personalizada a productores participantes.',
            'monto' => 8000.00,
            'gasto_acumulado' => 4800.00,
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-10-31',
            'fecha_inicio_real' => '2025-03-05',
            'linea_presupuestaria' => 'Servicios',
            'estado' => 'en_curso',
            'progreso' => 55.00,
            'prioridad' => 'media',
            'observaciones' => '180 visitas realizadas a 60 productores.',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        $act3_3_3 = DB::table('actividades')->insertGetId([
            'producto_id' => $producto3_3,
            'nombre' => 'Día de campo demostrativo',
            'descripcion' => 'Organización de evento demostrativo con productores de la región.',
            'monto' => 2000.00,
            'gasto_acumulado' => 0.00,
            'fecha_inicio' => '2025-09-15',
            'fecha_fin' => '2025-10-15',
            'linea_presupuestaria' => 'Eventos',
            'estado' => 'pendiente',
            'progreso' => 0.00,
            'prioridad' => 'baja',
            'responsable_id' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        // ========================================
        // SEGUIMIENTOS DE ACTIVIDADES
        // ========================================

        // Seguimientos para actividad con sobregiro (Carta 1 - Adquisición semillas)
        $seg1 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act1_2_1,
            'progreso_anterior' => 0.00,
            'progreso_nuevo' => 25.00,
            'monto_gastado' => 12000.00,
            'gasto_acumulado_anterior' => 0.00,
            'gasto_acumulado_nuevo' => 12000.00,
            'descripcion_avance' => 'Se inició proceso de adquisición. Contacto con proveedores certificados y solicitud de cotizaciones.',
            'logros' => 'Identificados 5 proveedores certificados de semillas.',
            'responsable_nombre' => 'María López',
            'estado_anterior' => 'pendiente',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => false,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 0.00,
            'variacion_presupuesto_porcentaje' => 0.00,
            'nivel_riesgo' => 'bajo',
            'registrado_por' => $proveedorUserId ?? $coordinadorId,
            'fecha_registro' => $now->copy()->subMonths(2),
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now->copy()->subMonths(2),
        ]);

        $seg2 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act1_2_1,
            'progreso_anterior' => 25.00,
            'progreso_nuevo' => 50.00,
            'monto_gastado' => 18000.00,
            'gasto_acumulado_anterior' => 12000.00,
            'gasto_acumulado_nuevo' => 30000.00,
            'descripcion_avance' => 'Primera compra de semillas realizada. 2,500 kg de papa y 800 kg de quinua adquiridos.',
            'logros' => 'Semillas almacenadas en condiciones óptimas.',
            'dificultades' => 'Precios más altos de lo presupuestado debido a temporada alta.',
            'responsable_nombre' => 'María López',
            'estado_anterior' => 'en_curso',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => false,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 0.00,
            'variacion_presupuesto_porcentaje' => 0.00,
            'nivel_riesgo' => 'medio',
            'registrado_por' => $proveedorUserId ?? $coordinadorId,
            'fecha_registro' => $now->copy()->subMonths(1)->subDays(15),
            'created_at' => $now->copy()->subMonths(1)->subDays(15),
            'updated_at' => $now->copy()->subMonths(1)->subDays(15),
        ]);

        $seg3 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act1_2_1,
            'progreso_anterior' => 50.00,
            'progreso_nuevo' => 70.00,
            'monto_gastado' => 15000.00,
            'gasto_acumulado_anterior' => 30000.00,
            'gasto_acumulado_nuevo' => 45000.00,
            'descripcion_avance' => 'Segunda compra completada. Se alcanzó el presupuesto original pero se requiere compra adicional.',
            'logros' => 'Total 4,000 kg de semillas adquiridas.',
            'dificultades' => 'Se identificaron 50 familias adicionales que solicitaron participar en el programa.',
            'proximos_pasos' => 'Solicitar aprobación para compra adicional de semillas.',
            'responsable_nombre' => 'María López',
            'estado_anterior' => 'en_curso',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => false,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 0.00,
            'variacion_presupuesto_porcentaje' => 0.00,
            'nivel_riesgo' => 'medio',
            'registrado_por' => $proveedorUserId ?? $coordinadorId,
            'fecha_registro' => $now->copy()->subMonth(),
            'created_at' => $now->copy()->subMonth(),
            'updated_at' => $now->copy()->subMonth(),
        ]);

        $seg4 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act1_2_1,
            'progreso_anterior' => 70.00,
            'progreso_nuevo' => 85.00,
            'monto_gastado' => 7500.00,
            'gasto_acumulado_anterior' => 45000.00,
            'gasto_acumulado_nuevo' => 52500.00,
            'descripcion_avance' => 'Compra adicional realizada para cubrir incremento de beneficiarios. NOTA: Gasto excede presupuesto original.',
            'logros' => '500 kg adicionales adquiridos. Total 4,500 kg de semillas.',
            'dificultades' => 'Sobregiro del 16.7% sobre presupuesto original.',
            'proximos_pasos' => 'Completar distribución y gestionar formalización de sobregiro.',
            'responsable_nombre' => 'María López',
            'observaciones' => 'Sobregiro justificado por incremento de 50 familias beneficiarias.',
            'estado_anterior' => 'en_curso',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => true, // ⚠️ SOBREGIRO
            'esta_atrasado' => false,
            'variacion_presupuesto' => 7500.00,
            'variacion_presupuesto_porcentaje' => 16.67,
            'nivel_riesgo' => 'alto',
            'riesgos_identificados' => 'Sobregiro presupuestario pendiente de aprobación formal.',
            'acciones_correctivas' => 'Se solicitó cancelación de actividad de invernaderos para cubrir diferencia.',
            'registrado_por' => $proveedorUserId ?? $coordinadorId,
            'fecha_registro' => $now->copy()->subDays(10),
            'created_at' => $now->copy()->subDays(10),
            'updated_at' => $now->copy()->subDays(10),
        ]);

        // Seguimientos para actividad con sobregiro alto (Carta 2 - Canal principal)
        $seg5 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act2_2_1,
            'progreso_anterior' => 0.00,
            'progreso_nuevo' => 20.00,
            'monto_gastado' => 10000.00,
            'gasto_acumulado_anterior' => 0.00,
            'gasto_acumulado_nuevo' => 10000.00,
            'descripcion_avance' => 'Inicio de excavación del canal. Trazo y replanteo completados.',
            'logros' => 'Primeros 500 metros excavados.',
            'responsable_nombre' => 'Ing. Pedro Mamani',
            'estado_anterior' => 'pendiente',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => false,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 0.00,
            'variacion_presupuesto_porcentaje' => 0.00,
            'nivel_riesgo' => 'bajo',
            'registrado_por' => $proveedorUserId ?? $adminId,
            'fecha_registro' => $now->copy()->subMonths(2),
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now->copy()->subMonths(2),
        ]);

        $seg6 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act2_2_1,
            'progreso_anterior' => 20.00,
            'progreso_nuevo' => 45.00,
            'monto_gastado' => 18000.00,
            'gasto_acumulado_anterior' => 10000.00,
            'gasto_acumulado_nuevo' => 28000.00,
            'descripcion_avance' => 'Se encontró formación rocosa a 1.5m de profundidad. Requiere maquinaria pesada adicional.',
            'logros' => '1,100 metros de canal excavados.',
            'dificultades' => 'Condiciones de suelo imprevistas. Roca a menor profundidad de lo indicado en estudios.',
            'proximos_pasos' => 'Contratar retroexcavadora con martillo hidráulico.',
            'responsable_nombre' => 'Ing. Pedro Mamani',
            'estado_anterior' => 'en_curso',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => false,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 0.00,
            'variacion_presupuesto_porcentaje' => 0.00,
            'nivel_riesgo' => 'alto',
            'riesgos_identificados' => 'Probable sobrecosto por condiciones de suelo.',
            'registrado_por' => $proveedorUserId ?? $adminId,
            'fecha_registro' => $now->copy()->subMonths(1)->subDays(15),
            'created_at' => $now->copy()->subMonths(1)->subDays(15),
            'updated_at' => $now->copy()->subMonths(1)->subDays(15),
        ]);

        $seg7 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act2_2_1,
            'progreso_anterior' => 45.00,
            'progreso_nuevo' => 70.00,
            'monto_gastado' => 18000.00,
            'gasto_acumulado_anterior' => 28000.00,
            'gasto_acumulado_nuevo' => 46000.00,
            'descripcion_avance' => 'Excavación de tramo rocoso completada. Costos adicionales por maquinaria pesada.',
            'logros' => '1,750 metros excavados. Revestimiento iniciado en primeros 800m.',
            'dificultades' => 'Se excedió presupuesto original. Requiere aprobación de sobregiro.',
            'responsable_nombre' => 'Ing. Pedro Mamani',
            'observaciones' => 'Gasto excede presupuesto en $1,000 (2.2%)',
            'estado_anterior' => 'en_curso',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => true,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 1000.00,
            'variacion_presupuesto_porcentaje' => 2.22,
            'nivel_riesgo' => 'alto',
            'riesgos_identificados' => 'Sobregiro confirmado. Requiere acciones correctivas.',
            'acciones_correctivas' => 'Se solicitó cancelación de actividad de riego tecnificado.',
            'registrado_por' => $proveedorUserId ?? $adminId,
            'fecha_registro' => $now->copy()->subMonth(),
            'created_at' => $now->copy()->subMonth(),
            'updated_at' => $now->copy()->subMonth(),
        ]);

        $seg8 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act2_2_1,
            'progreso_anterior' => 70.00,
            'progreso_nuevo' => 90.00,
            'monto_gastado' => 12000.00,
            'gasto_acumulado_anterior' => 46000.00,
            'gasto_acumulado_nuevo' => 58000.00,
            'descripcion_avance' => 'Revestimiento de canal avanza bien. 2,250 metros de 2,500 completados.',
            'logros' => 'Estructura principal del canal casi terminada.',
            'dificultades' => 'Sobregiro total de 28.9% sobre presupuesto original.',
            'proximos_pasos' => 'Completar últimos 250 metros y compuertas de regulación.',
            'responsable_nombre' => 'Ing. Pedro Mamani',
            'observaciones' => 'Sobregiro significativo pero obra casi completa.',
            'estado_anterior' => 'en_curso',
            'estado_nuevo' => 'en_curso',
            'excede_presupuesto' => true,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 13000.00,
            'variacion_presupuesto_porcentaje' => 28.89,
            'nivel_riesgo' => 'critico',
            'riesgos_identificados' => 'Sobregiro crítico requiere aprobación urgente.',
            'acciones_correctivas' => 'Solicitud de cancelación de otra actividad en proceso.',
            'registrado_por' => $proveedorUserId ?? $adminId,
            'fecha_registro' => $now->copy()->subDays(5),
            'created_at' => $now->copy()->subDays(5),
            'updated_at' => $now->copy()->subDays(5),
        ]);

        // Seguimientos para Carta 3 - Actividades completadas
        $seg9 = DB::table('seguimiento_actividades')->insertGetId([
            'actividad_id' => $act3_1_2,
            'progreso_anterior' => 0.00,
            'progreso_nuevo' => 100.00,
            'monto_gastado' => 12000.00,
            'gasto_acumulado_anterior' => 0.00,
            'gasto_acumulado_nuevo' => 12000.00,
            'descripcion_avance' => 'Campaña de inseminación completada exitosamente. Se logró inseminar 520 vientres superando la meta de 500.',
            'logros' => 'Tasa de concepción del 65%. 338 vacas preñadas confirmadas.',
            'responsable_nombre' => 'MVZ. Ana Rodríguez',
            'estado_anterior' => 'pendiente',
            'estado_nuevo' => 'finalizado',
            'excede_presupuesto' => false,
            'esta_atrasado' => false,
            'variacion_presupuesto' => 0.00,
            'variacion_presupuesto_porcentaje' => 0.00,
            'nivel_riesgo' => 'bajo',
            'registrado_por' => $proveedorUserId ?? $coordinadorId,
            'fecha_registro' => $now->copy()->subMonths(1),
            'created_at' => $now->copy()->subMonths(1),
            'updated_at' => $now->copy()->subMonths(1),
        ]);

        // ========================================
        // REVISIONES DE SEGUIMIENTOS
        // ========================================

        // Revisiones para seguimiento con sobregiro (seg4)
        DB::table('revisiones_seguimiento')->insert([
            'seguimiento_actividad_id' => $seg4,
            'user_id' => $coordinadorId,
            'tipo' => 'solicitud',
            'comentario' => 'Por favor adjuntar documentación de soporte del incremento de beneficiarios y justificación detallada del sobregiro.',
            'estado' => 'atendido',
            'respuesta_proveedor' => 'Se adjunta listado de las 50 familias adicionales con sus datos de contacto y acta de inclusión firmada por autoridades comunales. El sobregiro se justifica por la demanda no prevista y el compromiso institucional de no excluir a familias interesadas.',
            'respondido_por' => $proveedorUserId ?? $coordinadorId,
            'fecha_respuesta' => $now->copy()->subDays(8),
            'created_at' => $now->copy()->subDays(9),
            'updated_at' => $now->copy()->subDays(8),
        ]);

        DB::table('revisiones_seguimiento')->insert([
            'seguimiento_actividad_id' => $seg4,
            'user_id' => $adminId,
            'tipo' => 'observacion',
            'comentario' => 'Documentación recibida y verificada. El sobregiro está debidamente justificado por el incremento de beneficiarios.',
            'estado' => 'cerrado',
            'created_at' => $now->copy()->subDays(7),
            'updated_at' => $now->copy()->subDays(7),
        ]);

        // Revisiones para seguimiento del canal (seg8)
        DB::table('revisiones_seguimiento')->insert([
            'seguimiento_actividad_id' => $seg8,
            'user_id' => $adminId,
            'tipo' => 'solicitud',
            'comentario' => 'Requiero informe técnico detallado de las condiciones de suelo encontradas y comparativo con lo indicado en el estudio inicial. También necesito presupuesto desglosado del sobrecosto.',
            'estado' => 'pendiente', // Aún no respondido
            'created_at' => $now->copy()->subDays(4),
            'updated_at' => $now->copy()->subDays(4),
        ]);

        DB::table('revisiones_seguimiento')->insert([
            'seguimiento_actividad_id' => $seg8,
            'user_id' => $coordinadorId,
            'tipo' => 'correccion',
            'comentario' => 'El porcentaje de sobregiro reportado (28.89%) parece no coincidir con los montos. Por favor verificar y corregir si es necesario.',
            'estado' => 'atendido',
            'respuesta_proveedor' => 'Tiene razón, hubo un error de cálculo. El sobregiro correcto es: ($58,000 - $45,000) / $45,000 = 28.89%. El porcentaje está correcto, pero el monto de variación debería ser $13,000, no $12,000 como se indicó inicialmente. Se corrige el registro.',
            'respondido_por' => $proveedorUserId ?? $adminId,
            'fecha_respuesta' => $now->copy()->subDays(3),
            'created_at' => $now->copy()->subDays(4),
            'updated_at' => $now->copy()->subDays(3),
        ]);

        // Revisión aprobada
        DB::table('revisiones_seguimiento')->insert([
            'seguimiento_actividad_id' => $seg9,
            'user_id' => $adminId,
            'tipo' => 'aprobacion',
            'comentario' => 'Excelente trabajo. Los resultados superan las metas establecidas. Actividad aprobada para cierre.',
            'estado' => 'cerrado',
            'created_at' => $now->copy()->subDays(25),
            'updated_at' => $now->copy()->subDays(25),
        ]);

        // Revisión de seguimiento intermedio
        DB::table('revisiones_seguimiento')->insert([
            'seguimiento_actividad_id' => $seg6,
            'user_id' => $coordinadorId,
            'tipo' => 'observacion',
            'comentario' => 'Se toma nota de las dificultades encontradas con el suelo rocoso. Favor mantener registro fotográfico detallado como evidencia para justificar posibles sobrecostos.',
            'estado' => 'cerrado',
            'created_at' => $now->copy()->subMonths(1)->subDays(10),
            'updated_at' => $now->copy()->subMonths(1)->subDays(10),
        ]);

        // ========================================
        // KPIs DE EJEMPLO
        // ========================================
        $kpi1 = DB::table('kpis')->insertGetId([
            'carta_id' => $carta1Id,
            'nombre' => 'Familias Beneficiadas',
            'descripcion' => 'Número total de familias que reciben insumos agrícolas del proyecto',
            'tipo' => 'personalizado',
            'categoria' => 'social',
            'tipo_umbral' => 'mayor_mejor',
            'tipo_visualizacion' => 'numero',
            'meta' => 500.00,
            'linea_base' => 0.00,
            'valor_actual' => 450.00,
            'frecuencia' => 'mensual',
            'unidad_medida' => 'familias',
            'color' => '#2563eb',
            'orden' => 1,
            'activo' => true,
            'mostrar_en_dashboard' => true,
            'creado_por' => $adminId,
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $kpi2 = DB::table('kpis')->insertGetId([
            'carta_id' => $carta1Id,
            'nombre' => 'Hectáreas Cultivadas',
            'descripcion' => 'Superficie total bajo cultivo con semillas del proyecto',
            'tipo' => 'personalizado',
            'categoria' => 'productivo',
            'tipo_umbral' => 'mayor_mejor',
            'tipo_visualizacion' => 'numero',
            'meta' => 750.00,
            'linea_base' => 0.00,
            'valor_actual' => 520.00,
            'frecuencia' => 'trimestral',
            'unidad_medida' => 'hectáreas',
            'color' => '#16a34a',
            'orden' => 2,
            'activo' => true,
            'mostrar_en_dashboard' => true,
            'creado_por' => $adminId,
            'created_at' => $now->copy()->subMonths(3),
            'updated_at' => $now,
        ]);

        $kpi3 = DB::table('kpis')->insertGetId([
            'carta_id' => $carta2Id,
            'nombre' => 'Metros de Canal Construido',
            'descripcion' => 'Longitud total de canal de riego revestido',
            'tipo' => 'personalizado',
            'categoria' => 'infraestructura',
            'tipo_umbral' => 'mayor_mejor',
            'tipo_visualizacion' => 'numero',
            'meta' => 2500.00,
            'linea_base' => 0.00,
            'valor_actual' => 2250.00,
            'frecuencia' => 'quincenal',
            'unidad_medida' => 'metros',
            'color' => '#9333ea',
            'orden' => 1,
            'activo' => true,
            'mostrar_en_dashboard' => true,
            'creado_por' => $adminId,
            'created_at' => $now->copy()->subMonths(2),
            'updated_at' => $now,
        ]);

        $kpi4 = DB::table('kpis')->insertGetId([
            'carta_id' => $carta3Id,
            'nombre' => 'Tasa de Preñez',
            'descripcion' => 'Porcentaje de vacas inseminadas que resultaron preñadas',
            'tipo' => 'personalizado',
            'categoria' => 'productivo',
            'tipo_umbral' => 'mayor_mejor',
            'tipo_visualizacion' => 'porcentaje',
            'meta' => 60.00,
            'linea_base' => 45.00,
            'valor_actual' => 65.00,
            'frecuencia' => 'trimestral',
            'unidad_medida' => '%',
            'color' => '#ea580c',
            'orden' => 1,
            'activo' => true,
            'mostrar_en_dashboard' => true,
            'creado_por' => $coordinadorId,
            'created_at' => $now->copy()->subMonths(4),
            'updated_at' => $now,
        ]);

        // Valores KPI
        DB::table('kpi_valores')->insert([
            [
                'kpi_id' => $kpi1,
                'valor' => 450.00,
                'valor_anterior' => 380.00,
                'tendencia' => 'subiendo',
                'porcentaje_cambio' => 18.42,
                'en_alerta' => false,
                'tipo_alerta' => 'normal',
                'fecha_calculo' => $now,
                'calculado_por' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kpi_id' => $kpi4,
                'valor' => 65.00,
                'valor_anterior' => 45.00,
                'tendencia' => 'subiendo',
                'porcentaje_cambio' => 44.44,
                'en_alerta' => false,
                'tipo_alerta' => 'normal',
                'fecha_calculo' => $now,
                'calculado_por' => $coordinadorId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->command->info('✅ Datos de demostración creados exitosamente:');
        $this->command->info('   - 3 Cartas de Acuerdo');
        $this->command->info('   - 10 Productos');
        $this->command->info('   - 24 Actividades (incluyendo sobregiros, canceladas y pendientes)');
        $this->command->info('   - 9 Seguimientos de actividades');
        $this->command->info('   - 6 Revisiones de seguimientos');
        $this->command->info('   - 4 KPIs con valores');
    }
}
