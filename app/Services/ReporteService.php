<?php

namespace App\Services;

use App\Models\Carta;
use App\Models\Actividad;
use App\Models\SeguimientoActividad;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title as ChartTitle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReporteService
{
    public function generarReporte($tipo, $formato, $filtros = [])
    {
        $datos = $this->obtenerDatos($tipo, $filtros);

        if ($formato === 'pdf') {
            return $this->generarPDF($tipo, $datos, $filtros);
        } elseif ($formato === 'excel') {
            return $this->generarExcel($tipo, $datos, $filtros);
        }

        throw new \Exception('Formato no soportado');
    }

    private function obtenerDatos($tipo, $filtros)
    {
        return match($tipo) {
            'financiero' => $this->obtenerDatosFinancieros($filtros),
            'avance' => $this->obtenerDatosAvance($filtros),
            'actividades' => $this->obtenerDatosActividades($filtros),
            'resumen' => $this->obtenerDatosResumen($filtros),
            'ejecutado_vs_planificado' => $this->obtenerDatosEjecutadoVsPlanificado($filtros),
            'plan_trabajo' => $this->obtenerDatosPlanTrabajo($filtros),
            'lineas_presupuestarias' => $this->obtenerDatosLineasPresupuestarias($filtros),
            default => throw new \Exception('Tipo de reporte no válido'),
        };
    }

    private function obtenerDatosFinancieros($filtros)
    {
        $query = Carta::with(['productos.actividades']);

        // Filtro de proveedor
        if (isset($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (isset($filtros['fecha_inicio']) && $filtros['fecha_inicio']) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (isset($filtros['fecha_fin']) && $filtros['fecha_fin']) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        if (isset($filtros['estado']) && $filtros['estado']) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['carta_id']) && $filtros['carta_id']) {
            $query->where('id', $filtros['carta_id']);
        }

        $cartas = $query->get();

        return $cartas->map(function ($carta) {
            $productos = $carta->productos;
            $actividades = $productos->flatMap->actividades;

            return [
                'codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'estado' => $carta->estado,
                'presupuesto_total' => $actividades->sum('monto'),
                'ejecutado_total' => $actividades->sum('gasto_acumulado'),
                'saldo' => $actividades->sum('monto') - $actividades->sum('gasto_acumulado'),
                'porcentaje_ejecucion' => $actividades->sum('monto') > 0
                    ? ($actividades->sum('gasto_acumulado') / $actividades->sum('monto')) * 100
                    : 0,
                'progreso_promedio' => $actividades->avg('progreso') ?? 0,
            ];
        });
    }

    private function obtenerDatosAvance($filtros)
    {
        $query = Carta::with(['productos.actividades']);

        // Filtro de proveedor
        if (isset($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (isset($filtros['fecha_inicio']) && $filtros['fecha_inicio']) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (isset($filtros['fecha_fin']) && $filtros['fecha_fin']) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        if (isset($filtros['carta_id']) && $filtros['carta_id']) {
            $query->where('id', $filtros['carta_id']);
        }

        $cartas = $query->get();

        return $cartas->map(function ($carta) {
            $actividades = $carta->productos->flatMap->actividades;
            $totalActividades = $actividades->count();

            return [
                'codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'progreso_general' => $actividades->avg('progreso') ?? 0,
                'total_actividades' => $totalActividades,
                'completadas' => $actividades->where('progreso', '>=', 100)->count(),
                'en_curso' => $actividades->where('progreso', '>', 0)->where('progreso', '<', 100)->count(),
                'pendientes' => $actividades->where('progreso', 0)->count(),
                'atrasadas' => $actividades->filter(function($act) {
                    return $act->fecha_fin < now() && $act->progreso < 100;
                })->count(),
            ];
        });
    }

    private function obtenerDatosActividades($filtros)
    {
        $query = Actividad::with(['producto.carta', 'responsable']);

        // Filtro de proveedor
        if (isset($filtros['proveedor_id'])) {
            $query->whereHas('producto.carta', function($q) use ($filtros) {
                $q->where('proveedor_id', $filtros['proveedor_id']);
            });
        }

        if (isset($filtros['estado']) && $filtros['estado']) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['fecha_inicio']) && $filtros['fecha_inicio']) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (isset($filtros['fecha_fin']) && $filtros['fecha_fin']) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        if (isset($filtros['carta_id']) && $filtros['carta_id']) {
            $query->whereHas('producto.carta', function($q) use ($filtros) {
                $q->where('id', $filtros['carta_id']);
            });
        }

        return $query->get()->map(function ($actividad) {
            return [
                'carta_codigo' => $actividad->producto->carta->codigo,
                'producto' => $actividad->producto->nombre,
                'actividad' => $actividad->nombre,
                'responsable' => $actividad->responsable?->name ?? 'Sin asignar',
                'fecha_inicio' => $actividad->fecha_inicio->format('d/m/Y'),
                'fecha_fin' => $actividad->fecha_fin->format('d/m/Y'),
                'progreso' => $actividad->progreso,
                'estado' => $actividad->estado,
                'presupuesto' => $actividad->monto,
                'ejecutado' => $actividad->gasto_acumulado,
                'saldo' => $actividad->monto - $actividad->gasto_acumulado,
            ];
        });
    }

    private function obtenerDatosResumen($filtros)
    {
        $cartasQuery = Carta::with(['productos.actividades']);

        // Filtro de proveedor
        if (isset($filtros['proveedor_id'])) {
            $cartasQuery->where('proveedor_id', $filtros['proveedor_id']);
        }

        $cartas = $cartasQuery->get();
        $actividades = $cartas->flatMap(function($carta) {
            return $carta->productos->flatMap->actividades;
        });

        return [
            'estadisticas' => [
                'total_cartas' => $cartas->count(),
                'cartas_activas' => $cartas->whereIn('estado', ['en_ejecucion', 'enviada'])->count(),
                'cartas_finalizadas' => $cartas->where('estado', 'finalizada')->count(),
                'total_presupuesto' => $actividades->sum('monto'),
                'total_ejecutado' => $actividades->sum('gasto_acumulado'),
                'saldo_disponible' => $actividades->sum('monto') - $actividades->sum('gasto_acumulado'),
                'ejecucion_presupuestaria' => $actividades->sum('monto') > 0
                    ? ($actividades->sum('gasto_acumulado') / $actividades->sum('monto')) * 100
                    : 0,
                'progreso_promedio' => $actividades->avg('progreso') ?? 0,
                'actividades_total' => $actividades->count(),
                'actividades_completadas' => $actividades->where('progreso', '>=', 100)->count(),
                'actividades_en_curso' => $actividades->where('progreso', '>', 0)->where('progreso', '<', 100)->count(),
                'actividades_pendientes' => $actividades->where('progreso', 0)->count(),
                'actividades_atrasadas' => $actividades->filter(function($act) {
                    return $act->fecha_fin < now() && $act->progreso < 100;
                })->count(),
            ],
            'alertas' => [
                'atrasadas' => $actividades->filter(function($act) {
                    return $act->fecha_fin < now() && $act->progreso < 100;
                })->count(),
                'exceden_presupuesto' => $actividades->filter(function($act) {
                    return $act->gasto_acumulado > $act->monto;
                })->count(),
                'riesgo_alto' => SeguimientoActividad::whereIn('nivel_riesgo', ['alto', 'critico'])->distinct('actividad_id')->count(),
                'proximas_vencer' => $actividades->filter(function($act) {
                    return $act->fecha_fin->between(now(), now()->addDays(7)) && $act->progreso < 100;
                })->count(),
                'total_alertas' => 0,
            ],
        ];

        $data['alertas']['total_alertas'] =
            $data['alertas']['atrasadas'] +
            $data['alertas']['exceden_presupuesto'] +
            $data['alertas']['riesgo_alto'] +
            $data['alertas']['proximas_vencer'];

        return $data;
    }

    private function obtenerDatosEjecutadoVsPlanificado($filtros)
    {
        $query = Carta::with(['productos.actividades.seguimientos']);

        // Filtro de proveedor
        if (isset($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (isset($filtros['fecha_inicio']) && $filtros['fecha_inicio']) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (isset($filtros['fecha_fin']) && $filtros['fecha_fin']) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        if (isset($filtros['carta_id']) && $filtros['carta_id']) {
            $query->where('id', $filtros['carta_id']);
        }

        $cartas = $query->get();

        return $cartas->map(function ($carta) {
            $actividades = $carta->productos->flatMap->actividades;
            $presupuestoPlanificado = $actividades->sum('monto');
            $presupuestoEjecutado = $actividades->sum('gasto_acumulado');
            $variacion = $presupuestoEjecutado - $presupuestoPlanificado;
            $variacionPorcentaje = $presupuestoPlanificado > 0
                ? ($variacion / $presupuestoPlanificado) * 100
                : 0;

            return [
                'carta_codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'presupuesto_planificado' => $presupuestoPlanificado,
                'presupuesto_ejecutado' => $presupuestoEjecutado,
                'variacion' => $variacion,
                'variacion_porcentaje' => $variacionPorcentaje,
                'estado' => $variacion > 0 ? 'Sobre presupuesto' : ($variacion < 0 ? 'Bajo presupuesto' : 'En presupuesto'),
                'productos' => $carta->productos->map(function($producto) {
                    $actividadesProducto = $producto->actividades;
                    $planificado = $actividadesProducto->sum('monto');
                    $ejecutado = $actividadesProducto->sum('gasto_acumulado');

                    return [
                        'nombre' => $producto->nombre,
                        'planificado' => $planificado,
                        'ejecutado' => $ejecutado,
                        'variacion' => $ejecutado - $planificado,
                        'actividades' => $actividadesProducto->map(function($actividad) {
                            return [
                                'nombre' => $actividad->nombre,
                                'planificado' => $actividad->monto,
                                'ejecutado' => $actividad->gasto_acumulado,
                                'variacion' => $actividad->gasto_acumulado - $actividad->monto,
                            ];
                        }),
                    ];
                }),
            ];
        });
    }

    private function obtenerDatosPlanTrabajo($filtros)
    {
        $query = Carta::with(['productos.actividades.responsable']);

        // Filtro de proveedor
        if (isset($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (isset($filtros['carta_id']) && $filtros['carta_id']) {
            $query->where('id', $filtros['carta_id']);
        }

        $cartas = $query->get();

        return $cartas->map(function ($carta) {
            return [
                'carta_codigo' => $carta->codigo,
                'carta_nombre' => $carta->nombre_proyecto,
                'carta_fecha_inicio' => $carta->fecha_inicio,
                'carta_fecha_fin' => $carta->fecha_fin,
                'carta_presupuesto' => $carta->productos->flatMap->actividades->sum('monto'),
                'productos' => $carta->productos->map(function($producto) {
                    return [
                        'nombre' => $producto->nombre,
                        'descripcion' => $producto->descripcion,
                        'fecha_inicio' => $producto->fecha_inicio,
                        'fecha_fin' => $producto->fecha_fin,
                        'presupuesto' => $producto->actividades->sum('monto'),
                        'actividades' => $producto->actividades->map(function($actividad) {
                            return [
                                'nombre' => $actividad->nombre,
                                'descripcion' => $actividad->descripcion,
                                'responsable' => $actividad->responsable?->name ?? 'Sin asignar',
                                'fecha_inicio' => $actividad->fecha_inicio,
                                'fecha_fin' => $actividad->fecha_fin,
                                'duracion_dias' => $actividad->fecha_inicio->diffInDays($actividad->fecha_fin),
                                'presupuesto' => $actividad->monto,
                                'ejecutado' => $actividad->gasto_acumulado,
                                'progreso' => $actividad->progreso,
                                'estado' => $actividad->estado,
                                'linea_presupuestaria' => $actividad->linea_presupuestaria,
                            ];
                        }),
                    ];
                }),
            ];
        });
    }

    private function obtenerDatosLineasPresupuestarias($filtros)
    {
        $query = Actividad::with(['producto.carta', 'responsable'])
            ->whereHas('producto.carta', function($q) use ($filtros) {
                if (isset($filtros['proveedor_id'])) {
                    $q->where('proveedor_id', $filtros['proveedor_id']);
                }
                if (isset($filtros['carta_id']) && $filtros['carta_id']) {
                    $q->where('id', $filtros['carta_id']);
                }
                if (isset($filtros['estado']) && $filtros['estado']) {
                    $q->where('estado', $filtros['estado']);
                }
            });

        if (isset($filtros['fecha_inicio']) && $filtros['fecha_inicio']) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (isset($filtros['fecha_fin']) && $filtros['fecha_fin']) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        $actividades = $query->get();

        // Agrupar por línea presupuestaria
        $porLinea = $actividades->groupBy('linea_presupuestaria');

        $resultado = [];
        $totales = [
            'planificado' => 0,
            'ejecutado' => 0,
            'saldo' => 0,
        ];

        foreach ($porLinea as $linea => $actividadesLinea) {
            $planificadoLinea = $actividadesLinea->sum('monto');
            $ejecutadoLinea = $actividadesLinea->sum('gasto_acumulado');
            $saldoLinea = $planificadoLinea - $ejecutadoLinea;
            $progresoLinea = $actividadesLinea->avg('progreso') ?? 0;

            $totales['planificado'] += $planificadoLinea;
            $totales['ejecutado'] += $ejecutadoLinea;
            $totales['saldo'] += $saldoLinea;

            // Agrupar por carta dentro de cada línea
            $porCarta = $actividadesLinea->groupBy(function($a) {
                return $a->producto->carta->codigo;
            });

            $cartasData = [];
            foreach ($porCarta as $codigoCarta => $actividadesCarta) {
                $carta = $actividadesCarta->first()->producto->carta;
                $planificadoCarta = $actividadesCarta->sum('monto');
                $ejecutadoCarta = $actividadesCarta->sum('gasto_acumulado');

                // Agrupar por producto dentro de cada carta
                $porProducto = $actividadesCarta->groupBy('producto_id');
                $productosData = [];

                foreach ($porProducto as $productoId => $actividadesProducto) {
                    $producto = $actividadesProducto->first()->producto;
                    $planificadoProducto = $actividadesProducto->sum('monto');
                    $ejecutadoProducto = $actividadesProducto->sum('gasto_acumulado');

                    $productosData[] = [
                        'producto_id' => $productoId,
                        'producto_nombre' => $producto->nombre,
                        'planificado' => $planificadoProducto,
                        'ejecutado' => $ejecutadoProducto,
                        'saldo' => $planificadoProducto - $ejecutadoProducto,
                        'progreso' => $actividadesProducto->avg('progreso') ?? 0,
                        'actividades' => $actividadesProducto->map(function($a) {
                            return [
                                'id' => $a->id,
                                'nombre' => $a->nombre,
                                'planificado' => $a->monto,
                                'ejecutado' => $a->gasto_acumulado,
                                'saldo' => $a->monto - $a->gasto_acumulado,
                                'progreso' => $a->progreso,
                                'estado' => $a->estado,
                                'fecha_inicio' => $a->fecha_inicio,
                                'fecha_fin' => $a->fecha_fin,
                                'responsable' => $a->responsable?->name ?? 'Sin asignar',
                            ];
                        })->values()->toArray(),
                    ];
                }

                $cartasData[] = [
                    'carta_codigo' => $codigoCarta,
                    'carta_nombre' => $carta->nombre_proyecto,
                    'planificado' => $planificadoCarta,
                    'ejecutado' => $ejecutadoCarta,
                    'saldo' => $planificadoCarta - $ejecutadoCarta,
                    'porcentaje_ejecucion' => $planificadoCarta > 0
                        ? ($ejecutadoCarta / $planificadoCarta) * 100 : 0,
                    'productos' => $productosData,
                ];
            }

            $resultado[] = [
                'linea_presupuestaria' => $linea,
                'total_planificado' => $planificadoLinea,
                'total_ejecutado' => $ejecutadoLinea,
                'total_saldo' => $saldoLinea,
                'porcentaje_ejecucion' => $planificadoLinea > 0
                    ? ($ejecutadoLinea / $planificadoLinea) * 100 : 0,
                'progreso_promedio' => $progresoLinea,
                'cantidad_actividades' => $actividadesLinea->count(),
                'cartas' => $cartasData,
            ];
        }

        // Ordenar por línea presupuestaria
        usort($resultado, fn($a, $b) => strcmp($a['linea_presupuestaria'], $b['linea_presupuestaria']));

        return [
            'lineas' => $resultado,
            'totales' => $totales,
            'resumen' => [
                'total_lineas' => count($resultado),
                'total_actividades' => $actividades->count(),
                'porcentaje_ejecucion_global' => $totales['planificado'] > 0
                    ? ($totales['ejecutado'] / $totales['planificado']) * 100 : 0,
            ],
        ];
    }

    private function generarPDF($tipo, $datos, $filtros)
    {
        $vista = match($tipo) {
            'financiero' => 'reportes.pdf.financiero',
            'avance' => 'reportes.pdf.avance',
            'actividades' => 'reportes.pdf.actividades',
            'resumen' => 'reportes.pdf.resumen',
            'ejecutado_vs_planificado' => 'reportes.pdf.ejecutado-vs-planificado',
            'plan_trabajo' => 'reportes.pdf.plan-trabajo',
            'lineas_presupuestarias' => 'reportes.pdf.lineas-presupuestarias',
            default => throw new \Exception('Vista no encontrada'),
        };

        $pdf = Pdf::loadView($vista, [
            'datos' => $datos,
            'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
            'fechaInicio' => $filtros['fecha_inicio'] ?? null,
            'fechaFin' => $filtros['fecha_fin'] ?? null,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $filename = $tipo . '_' . now()->format('Y-m-d_His') . '.pdf';
        $path = 'reportes/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return Storage::disk('public')->url($path);
    }

    private function generarExcel($tipo, $datos, $filtros)
    {
        $spreadsheet = new Spreadsheet();

        match($tipo) {
            'financiero' => $this->generarExcelFinanciero($spreadsheet, $datos),
            'avance' => $this->generarExcelAvance($spreadsheet, $datos),
            'actividades' => $this->generarExcelActividades($spreadsheet, $datos),
            'resumen' => $this->generarExcelResumen($spreadsheet, $datos),
            'ejecutado_vs_planificado' => $this->generarExcelEjecutadoVsPlanificado($spreadsheet, $datos),
            'plan_trabajo' => $this->generarExcelPlanTrabajo($spreadsheet, $datos),
            'lineas_presupuestarias' => $this->generarExcelLineasPresupuestarias($spreadsheet, $datos),
            default => throw new \Exception('Tipo de Excel no soportado'),
        };

        $writer = new Xlsx($spreadsheet);
        $filename = $tipo . '_' . now()->format('Y-m-d_His') . '.xlsx';
        $path = storage_path('app/public/reportes/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $writer->save($path);

        return Storage::disk('public')->url('reportes/' . $filename);
    }

    private function generarExcelFinanciero($spreadsheet, $datos)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte Financiero');

        // Encabezado
        $sheet->setCellValue('A1', 'REPORTE FINANCIERO - FAO');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Cabeceras
        $headers = ['#', 'Código', 'Proyecto', 'Presupuesto', 'Ejecutado', 'Saldo', '% Ejecución', 'Progreso', 'Estado'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('0073e6');
            $sheet->getStyle($col . '3')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Datos
        $row = 4;
        foreach ($datos as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['codigo']);
            $sheet->setCellValue('C' . $row, $item['nombre_proyecto']);
            $sheet->setCellValue('D' . $row, $item['presupuesto_total']);
            $sheet->setCellValue('E' . $row, $item['ejecutado_total']);
            $sheet->setCellValue('F' . $row, $item['saldo']);
            $sheet->setCellValue('G' . $row, $item['porcentaje_ejecucion'] / 100);
            $sheet->setCellValue('H' . $row, $item['progreso_promedio'] / 100);
            $sheet->setCellValue('I' . $row, ucfirst($item['estado']));
            $row++;
        }

        // Formato
        $sheet->getStyle('D4:F' . ($row - 1))->getNumberFormat()
            ->setFormatCode('$#,##0.00');
        $sheet->getStyle('G4:H' . ($row - 1))->getNumberFormat()
            ->setFormatCode('0.00%');

        // Totales
        $sheet->setCellValue('C' . $row, 'TOTALES:');
        $sheet->setCellValue('D' . $row, '=SUM(D4:D' . ($row - 1) . ')');
        $sheet->setCellValue('E' . $row, '=SUM(E4:E' . ($row - 1) . ')');
        $sheet->setCellValue('F' . $row, '=SUM(F4:F' . ($row - 1) . ')');
        $sheet->getStyle('C' . $row . ':F' . $row)->getFont()->setBold(true);

        // Ajustar columnas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function generarExcelEjecutadoVsPlanificado($spreadsheet, $datos)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ejecutado vs Planificado');

        // Encabezado
        $sheet->setCellValue('A1', 'REPORTE: EJECUTADO VS PLANIFICADO');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Cabeceras
        $headers = ['#', 'Código Carta', 'Proyecto', 'Planificado', 'Ejecutado', 'Variación', '% Variación'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('0073e6');
            $sheet->getStyle($col . '3')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Datos
        $row = 4;
        foreach ($datos as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['carta_codigo']);
            $sheet->setCellValue('C' . $row, $item['nombre_proyecto']);
            $sheet->setCellValue('D' . $row, $item['presupuesto_planificado']);
            $sheet->setCellValue('E' . $row, $item['presupuesto_ejecutado']);
            $sheet->setCellValue('F' . $row, $item['variacion']);
            $sheet->setCellValue('G' . $row, $item['variacion_porcentaje'] / 100);

            // Color según variación
            if ($item['variacion'] > 0) {
                $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->getColor()->setRGB('FF0000');
            } elseif ($item['variacion'] < 0) {
                $sheet->getStyle('F' . $row . ':G' . $row)->getFont()->getColor()->setRGB('00AA00');
            }

            $row++;
        }

        // Formato
        $sheet->getStyle('D4:F' . ($row - 1))->getNumberFormat()
            ->setFormatCode('$#,##0.00');
        $sheet->getStyle('G4:G' . ($row - 1))->getNumberFormat()
            ->setFormatCode('0.00%');

        // Totales
        $sheet->setCellValue('C' . $row, 'TOTALES:');
        $sheet->setCellValue('D' . $row, '=SUM(D4:D' . ($row - 1) . ')');
        $sheet->setCellValue('E' . $row, '=SUM(E4:E' . ($row - 1) . ')');
        $sheet->setCellValue('F' . $row, '=SUM(F4:F' . ($row - 1) . ')');
        $sheet->getStyle('C' . $row . ':F' . $row)->getFont()->setBold(true);

        // Ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function generarExcelPlanTrabajo($spreadsheet, $datos)
    {
        // Hoja 1: Plan de Trabajo Detallado
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plan de Trabajo');

        $row = 1;
        foreach ($datos as $carta) {
            // Encabezado Carta
            $sheet->setCellValue('A' . $row, 'CARTA: ' . $carta['carta_codigo']);
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('0073e6');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $row++;

            $sheet->setCellValue('A' . $row, $carta['carta_nombre']);
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $row++;

            // Cabeceras
            $headers = ['Producto/Actividad', 'Descripción', 'Responsable', 'Inicio', 'Fin', 'Duración', 'Presupuesto', 'Ejecutado', 'Progreso', 'Estado'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('CCCCCC');
                $col++;
            }
            $row++;

            foreach ($carta['productos'] as $producto) {
                // Producto
                $sheet->setCellValue('A' . $row, $producto['nombre']);
                $sheet->setCellValue('B' . $row, $producto['descripcion']);
                $sheet->setCellValue('D' . $row, $producto['fecha_inicio']->format('d/m/Y'));
                $sheet->setCellValue('E' . $row, $producto['fecha_fin']->format('d/m/Y'));
                $sheet->setCellValue('G' . $row, $producto['presupuesto']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8F4F8');
                $row++;

                // Actividades
                foreach ($producto['actividades'] as $actividad) {
                    $sheet->setCellValue('A' . $row, '  → ' . $actividad['nombre']);
                    $sheet->setCellValue('B' . $row, $actividad['descripcion']);
                    $sheet->setCellValue('C' . $row, $actividad['responsable']);
                    $sheet->setCellValue('D' . $row, $actividad['fecha_inicio']->format('d/m/Y'));
                    $sheet->setCellValue('E' . $row, $actividad['fecha_fin']->format('d/m/Y'));
                    $sheet->setCellValue('F' . $row, $actividad['duracion_dias'] . ' días');
                    $sheet->setCellValue('G' . $row, $actividad['presupuesto']);
                    $sheet->setCellValue('H' . $row, $actividad['ejecutado']);
                    $sheet->setCellValue('I' . $row, $actividad['progreso'] / 100);
                    $sheet->setCellValue('J' . $row, ucfirst($actividad['estado']));
                    $row++;
                }
            }
            $row += 2; // Espacio entre cartas
        }

        // Formato
        $sheet->getStyle('G4:H' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('I4:I' . $row)->getNumberFormat()->setFormatCode('0%');

        // Ajustar columnas
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Hoja 2: Diagrama de Gantt
        $this->generarGantt($spreadsheet, $datos);
    }

    private function generarExcelLineasPresupuestarias($spreadsheet, $datos)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Líneas Presupuestarias');

        // Encabezado principal
        $sheet->setCellValue('A1', 'REPORTE POR LÍNEAS PRESUPUESTARIAS - FAO');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Fecha de generación: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:J2');

        $row = 4;

        foreach ($datos['lineas'] as $linea) {
            // Encabezado de línea presupuestaria
            $sheet->setCellValue('A' . $row, 'LÍNEA: ' . strtoupper($linea['linea_presupuestaria']));
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('0073e6');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $row++;

            // Resumen de línea
            $sheet->setCellValue('A' . $row, 'Planificado: $' . number_format($linea['total_planificado'], 2));
            $sheet->setCellValue('C' . $row, 'Ejecutado: $' . number_format($linea['total_ejecutado'], 2));
            $sheet->setCellValue('E' . $row, 'Saldo: $' . number_format($linea['total_saldo'], 2));
            $sheet->setCellValue('G' . $row, 'Ejecución: ' . number_format($linea['porcentaje_ejecucion'], 1) . '%');
            $sheet->setCellValue('I' . $row, 'Actividades: ' . $linea['cantidad_actividades']);
            $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setItalic(true);
            $row++;

            foreach ($linea['cartas'] as $carta) {
                // Encabezado de carta
                $sheet->setCellValue('A' . $row, '  📁 ' . $carta['carta_codigo'] . ' - ' . $carta['carta_nombre']);
                $sheet->mergeCells('A' . $row . ':J' . $row);
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E3F2FD');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                foreach ($carta['productos'] as $producto) {
                    // Encabezado de producto
                    $sheet->setCellValue('A' . $row, '    📦 ' . $producto['producto_nombre']);
                    $sheet->mergeCells('A' . $row . ':J' . $row);
                    $sheet->getStyle('A' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F5F5F5');
                    $row++;

                    // Cabeceras de actividades
                    $headers = ['', 'Actividad', 'Planificado', 'Ejecutado', 'Saldo', '% Ejec.', 'Progreso', 'Estado', 'Responsable', 'Fecha Fin'];
                    $col = 'A';
                    foreach ($headers as $header) {
                        $sheet->setCellValue($col . $row, $header);
                        $sheet->getStyle($col . $row)->getFont()->setBold(true)->setSize(9);
                        $sheet->getStyle($col . $row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('E0E0E0');
                        $col++;
                    }
                    $row++;

                    // Datos de actividades
                    foreach ($producto['actividades'] as $actividad) {
                        $sheet->setCellValue('A' . $row, '');
                        $sheet->setCellValue('B' . $row, $actividad['nombre']);
                        $sheet->setCellValue('C' . $row, $actividad['planificado']);
                        $sheet->setCellValue('D' . $row, $actividad['ejecutado']);
                        $sheet->setCellValue('E' . $row, $actividad['saldo']);
                        $porcentajeEjec = $actividad['planificado'] > 0
                            ? ($actividad['ejecutado'] / $actividad['planificado']) * 100 : 0;
                        $sheet->setCellValue('F' . $row, $porcentajeEjec / 100);
                        $sheet->setCellValue('G' . $row, $actividad['progreso'] / 100);
                        $sheet->setCellValue('H' . $row, ucfirst($actividad['estado']));
                        $sheet->setCellValue('I' . $row, $actividad['responsable']);
                        $sheet->setCellValue('J' . $row, $actividad['fecha_fin']?->format('d/m/Y') ?? '-');

                        // Colores según estado
                        if ($actividad['saldo'] < 0) {
                            $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('FF0000');
                        }
                        if ($actividad['estado'] === 'atrasado') {
                            $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('FF0000');
                        } elseif ($actividad['estado'] === 'finalizado') {
                            $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('00AA00');
                        }

                        $row++;
                    }

                    // Subtotal producto
                    $sheet->setCellValue('B' . $row, 'Subtotal Producto:');
                    $sheet->setCellValue('C' . $row, $producto['planificado']);
                    $sheet->setCellValue('D' . $row, $producto['ejecutado']);
                    $sheet->setCellValue('E' . $row, $producto['saldo']);
                    $sheet->getStyle('B' . $row . ':E' . $row)->getFont()->setBold(true)->setSize(9);
                    $row++;
                }

                // Subtotal carta
                $sheet->setCellValue('A' . $row, '  Subtotal Carta:');
                $sheet->setCellValue('C' . $row, $carta['planificado']);
                $sheet->setCellValue('D' . $row, $carta['ejecutado']);
                $sheet->setCellValue('E' . $row, $carta['saldo']);
                $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E3F2FD');
                $row++;
            }

            $row++; // Espacio entre líneas
        }

        // TOTALES GENERALES
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTALES GENERALES');
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('1565C0');
        $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Planificado:');
        $sheet->setCellValue('B' . $row, $datos['totales']['planificado']);
        $sheet->setCellValue('D' . $row, 'Total Ejecutado:');
        $sheet->setCellValue('E' . $row, $datos['totales']['ejecutado']);
        $sheet->setCellValue('G' . $row, 'Saldo Global:');
        $sheet->setCellValue('H' . $row, $datos['totales']['saldo']);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);

        // Formato de moneda
        $sheet->getStyle('C4:E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('F4:G' . $row)->getNumberFormat()->setFormatCode('0.0%');

        // Ajustar columnas
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function generarGantt($spreadsheet, $datos)
    {
        $ganttSheet = $spreadsheet->createSheet();
        $ganttSheet->setTitle('Diagrama Gantt');

        // Calcular rango de fechas
        $fechaMin = null;
        $fechaMax = null;

        foreach ($datos as $carta) {
            if (!$fechaMin || $carta['carta_fecha_inicio'] < $fechaMin) {
                $fechaMin = $carta['carta_fecha_inicio'];
            }
            if (!$fechaMax || $carta['carta_fecha_fin'] > $fechaMax) {
                $fechaMax = $carta['carta_fecha_fin'];
            }
        }

        // Encabezado
        $ganttSheet->setCellValue('A1', 'DIAGRAMA DE GANTT');
        $ganttSheet->mergeCells('A1:D1');
        $ganttSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $ganttSheet->setCellValue('A3', 'Actividad');
        $ganttSheet->setCellValue('B3', 'Inicio');
        $ganttSheet->setCellValue('C3', 'Fin');
        $ganttSheet->setCellValue('D3', 'Duración');
        $ganttSheet->getStyle('A3:D3')->getFont()->setBold(true);

        // Generar columnas de fechas (por semanas)
        $col = 5; // Columna E
        $currentDate = $fechaMin->copy()->startOfWeek();
        $semanas = [];

        while ($currentDate <= $fechaMax) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $ganttSheet->setCellValue($colLetter . '3', $currentDate->format('d/m'));
            $ganttSheet->getStyle($colLetter . '3')->getFont()->setSize(8);
            $ganttSheet->getColumnDimension($colLetter)->setWidth(3);
            $semanas[] = ['col' => $col, 'fecha' => $currentDate->copy()];
            $currentDate->addWeek();
            $col++;
        }

        // Llenar actividades
        $row = 4;
        foreach ($datos as $carta) {
            foreach ($carta['productos'] as $producto) {
                foreach ($producto['actividades'] as $actividad) {
                    $ganttSheet->setCellValue('A' . $row, $actividad['nombre']);
                    $ganttSheet->setCellValue('B' . $row, $actividad['fecha_inicio']->format('d/m/Y'));
                    $ganttSheet->setCellValue('C' . $row, $actividad['fecha_fin']->format('d/m/Y'));
                    $ganttSheet->setCellValue('D' . $row, $actividad['duracion_dias']);

                    // Pintar barra de Gantt
                    foreach ($semanas as $semana) {
                        if ($actividad['fecha_inicio'] <= $semana['fecha'] && $actividad['fecha_fin'] >= $semana['fecha']) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($semana['col']);
                            $ganttSheet->getStyle($colLetter . $row)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('4472C4');
                        }
                    }
                    $row++;
                }
            }
        }

        // Ajustar columnas
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $ganttSheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // Métodos auxiliares para otros reportes
    private function generarExcelAvance($spreadsheet, $datos)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte de Avance');

        // Encabezado
        $sheet->setCellValue('A1', 'REPORTE DE AVANCE - FAO');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Cabeceras
        $headers = ['#', 'Código', 'Proyecto', 'Progreso', 'Total', 'Completadas', 'En Curso', 'Pendientes', 'Atrasadas'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('7c3aed');
            $sheet->getStyle($col . '3')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Datos
        $row = 4;
        foreach ($datos as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['codigo']);
            $sheet->setCellValue('C' . $row, $item['nombre_proyecto']);
            $sheet->setCellValue('D' . $row, $item['progreso_general'] / 100);
            $sheet->setCellValue('E' . $row, $item['total_actividades']);
            $sheet->setCellValue('F' . $row, $item['completadas']);
            $sheet->setCellValue('G' . $row, $item['en_curso']);
            $sheet->setCellValue('H' . $row, $item['pendientes']);
            $sheet->setCellValue('I' . $row, $item['atrasadas']);
            $row++;
        }

        // Formato
        $sheet->getStyle('D4:D' . ($row - 1))->getNumberFormat()->setFormatCode('0%');

        // Totales
        $sheet->setCellValue('D' . $row, 'TOTALES:');
        $sheet->setCellValue('E' . $row, '=SUM(E4:E' . ($row - 1) . ')');
        $sheet->setCellValue('F' . $row, '=SUM(F4:F' . ($row - 1) . ')');
        $sheet->setCellValue('G' . $row, '=SUM(G4:G' . ($row - 1) . ')');
        $sheet->setCellValue('H' . $row, '=SUM(H4:H' . ($row - 1) . ')');
        $sheet->setCellValue('I' . $row, '=SUM(I4:I' . ($row - 1) . ')');
        $sheet->getStyle('D' . $row . ':I' . $row)->getFont()->setBold(true);

        // Ajustar columnas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function generarExcelActividades($spreadsheet, $datos)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lista de Actividades');

        // Encabezado
        $sheet->setCellValue('A1', 'LISTA DE ACTIVIDADES - FAO');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Cabeceras
        $headers = ['#', 'Carta', 'Producto', 'Actividad', 'Responsable', 'Inicio', 'Fin', 'Presupuesto', 'Ejecutado', 'Saldo', 'Progreso', 'Estado'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getStyle($col . '3')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('ea580c');
            $sheet->getStyle($col . '3')->getFont()->getColor()->setRGB('FFFFFF');
            $col++;
        }

        // Datos
        $row = 4;
        foreach ($datos as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item['carta_codigo']);
            $sheet->setCellValue('C' . $row, $item['producto']);
            $sheet->setCellValue('D' . $row, $item['actividad']);
            $sheet->setCellValue('E' . $row, $item['responsable']);
            $sheet->setCellValue('F' . $row, $item['fecha_inicio']);
            $sheet->setCellValue('G' . $row, $item['fecha_fin']);
            $sheet->setCellValue('H' . $row, $item['presupuesto']);
            $sheet->setCellValue('I' . $row, $item['ejecutado']);
            $sheet->setCellValue('J' . $row, $item['saldo']);
            $sheet->setCellValue('K' . $row, $item['progreso'] / 100);
            $sheet->setCellValue('L' . $row, ucfirst($item['estado']));
            $row++;
        }

        // Formato
        $sheet->getStyle('H4:J' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('K4:K' . ($row - 1))->getNumberFormat()->setFormatCode('0%');

        // Totales
        $sheet->setCellValue('G' . $row, 'TOTALES:');
        $sheet->setCellValue('H' . $row, '=SUM(H4:H' . ($row - 1) . ')');
        $sheet->setCellValue('I' . $row, '=SUM(I4:I' . ($row - 1) . ')');
        $sheet->setCellValue('J' . $row, '=SUM(J4:J' . ($row - 1) . ')');
        $sheet->getStyle('G' . $row . ':J' . $row)->getFont()->setBold(true);

        // Ajustar columnas
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function generarExcelResumen($spreadsheet, $datos)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen Ejecutivo');

        // Encabezado
        $sheet->setCellValue('A1', 'RESUMEN EJECUTIVO - FAO');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('0073e6');
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 3;

        // Estadísticas Generales
        $sheet->setCellValue('A' . $row, 'ESTADÍSTICAS GENERALES');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $estadisticas = [
            ['Total de Cartas', $datos['estadisticas']['total_cartas']],
            ['Cartas Activas', $datos['estadisticas']['cartas_activas']],
            ['Cartas Finalizadas', $datos['estadisticas']['cartas_finalizadas']],
            ['Progreso Promedio', $datos['estadisticas']['progreso_promedio'] . '%'],
        ];

        foreach ($estadisticas as $stat) {
            $sheet->setCellValue('A' . $row, $stat[0]);
            $sheet->setCellValue('B' . $row, $stat[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $row += 2;

        // Resumen Presupuestario
        $sheet->setCellValue('A' . $row, 'RESUMEN PRESUPUESTARIO');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Concepto');
        $sheet->setCellValue('B' . $row, 'Monto (USD)');
        $sheet->setCellValue('C' . $row, 'Porcentaje');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $row++;

        $presupuesto = [
            ['Presupuesto Total', $datos['estadisticas']['total_presupuesto'], '100%'],
            ['Ejecutado', $datos['estadisticas']['total_ejecutado'], $datos['estadisticas']['ejecucion_presupuestaria'] . '%'],
            ['Saldo Disponible', $datos['estadisticas']['saldo_disponible'], (100 - $datos['estadisticas']['ejecucion_presupuestaria']) . '%'],
        ];

        foreach ($presupuesto as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->setCellValue('C' . $row, $item[2]);
            if ($row > ($row - count($presupuesto))) {
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            }
            $row++;
        }

        $sheet->getStyle('B' . ($row - 3) . ':B' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');

        $row += 2;

        // Estado de Actividades
        $sheet->setCellValue('A' . $row, 'ESTADO DE ACTIVIDADES');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Estado');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->setCellValue('C' . $row, 'Porcentaje');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $row++;

        $totalAct = $datos['estadisticas']['actividades_total'];
        $actividades = [
            ['Completadas', $datos['estadisticas']['actividades_completadas']],
            ['En Curso', $datos['estadisticas']['actividades_en_curso']],
            ['Pendientes', $datos['estadisticas']['actividades_pendientes']],
            ['Atrasadas', $datos['estadisticas']['actividades_atrasadas']],
        ];

        foreach ($actividades as $act) {
            $sheet->setCellValue('A' . $row, $act[0]);
            $sheet->setCellValue('B' . $row, $act[1]);
            $porcentaje = $totalAct > 0 ? ($act[1] / $totalAct) * 100 : 0;
            $sheet->setCellValue('C' . $row, $porcentaje / 100);
            $row++;
        }

        $sheet->getStyle('C' . ($row - 4) . ':C' . ($row - 1))->getNumberFormat()->setFormatCode('0.0%');

        // Totales
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('B' . $row, $totalAct);
        $sheet->setCellValue('C' . $row, '100%');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);

        // Ajustar columnas
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
