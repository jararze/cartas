<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carta;
use App\Models\Kpi;

class KpiPredefinidosSeeder extends Seeder
{
    public function run(): void
    {
        $kpisPredefinidos = [
            // Financieros
            [
                'nombre' => 'Ejecución Presupuestal',
                'descripcion' => 'Porcentaje del presupuesto total ejecutado',
                'codigo' => 'ejecucion_presupuestal',
                'unidad_medida' => '%',
                'umbral_min' => 0,
                'umbral_max' => 100,
                'tipo_umbral' => 'rango',
                'tipo_visualizacion' => 'porcentaje',
            ],
            [
                'nombre' => 'Variación Presupuestal',
                'descripcion' => 'Diferencia entre gasto real y presupuesto planificado',
                'codigo' => 'variacion_presupuestal',
                'unidad_medida' => '$',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'tipo_visualizacion' => 'moneda',
            ],
            [
                'nombre' => 'Burn Rate',
                'descripcion' => 'Velocidad de gasto diario promedio',
                'codigo' => 'burn_rate',
                'unidad_medida' => '$/día',
                'tipo_umbral' => 'mayor_mejor',
                'tipo_visualizacion' => 'moneda',
            ],
            [
                'nombre' => 'CPI (Cost Performance Index)',
                'descripcion' => 'Índice de rendimiento de costos',
                'codigo' => 'cpi',
                'unidad_medida' => '',
                'umbral_min' => 1,
                'tipo_umbral' => 'mayor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            
            // Tiempo
            [
                'nombre' => '% Tiempo Transcurrido',
                'descripcion' => 'Porcentaje del tiempo del proyecto transcurrido',
                'codigo' => 'tiempo_transcurrido',
                'unidad_medida' => '%',
                'tipo_umbral' => 'rango',
                'tipo_visualizacion' => 'porcentaje',
            ],
            [
                'nombre' => 'SPI (Schedule Performance Index)',
                'descripcion' => 'Índice de rendimiento de cronograma',
                'codigo' => 'spi',
                'unidad_medida' => '',
                'umbral_min' => 1,
                'tipo_umbral' => 'mayor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            [
                'nombre' => 'Días de Retraso',
                'descripcion' => 'Días de retraso estimados respecto al cronograma',
                'codigo' => 'dias_retraso',
                'unidad_medida' => 'días',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            
            // Progreso
            [
                'nombre' => '% Completitud General',
                'descripcion' => 'Promedio ponderado de progreso de todas las actividades',
                'codigo' => 'progreso_general',
                'unidad_medida' => '%',
                'umbral_min' => 0,
                'tipo_umbral' => 'mayor_mejor',
                'tipo_visualizacion' => 'porcentaje',
            ],
            [
                'nombre' => 'Actividades Completadas',
                'descripcion' => 'Número de actividades finalizadas',
                'codigo' => 'actividades_completadas',
                'unidad_medida' => '',
                'tipo_umbral' => 'mayor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            [
                'nombre' => 'Productividad',
                'descripcion' => 'Progreso promedio por día',
                'codigo' => 'productividad',
                'unidad_medida' => '%/día',
                'tipo_umbral' => 'mayor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            
            // Riesgo
            [
                'nombre' => 'Actividades en Riesgo',
                'descripcion' => 'Número de actividades con alertas o problemas',
                'codigo' => 'actividades_riesgo',
                'unidad_medida' => '',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            [
                'nombre' => 'Sobrepresupuestos',
                'descripcion' => 'Actividades que exceden su presupuesto',
                'codigo' => 'sobrepresupuestos',
                'unidad_medida' => '',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
            [
                'nombre' => 'Actividades Atrasadas',
                'descripcion' => 'Actividades que están fuera de cronograma',
                'codigo' => 'actividades_atrasadas',
                'unidad_medida' => '',
                'umbral_max' => 0,
                'tipo_umbral' => 'menor_mejor',
                'tipo_visualizacion' => 'numero',
            ],
        ];

        // Crear KPIs predefinidos para cada carta existente
        // O simplemente tenerlos disponibles para crear cuando el usuario los active
        // Por ahora, este seeder sirve como referencia de los KPIs disponibles
        
        $this->command->info('KPIs predefinidos configurados correctamente');
    }
}
