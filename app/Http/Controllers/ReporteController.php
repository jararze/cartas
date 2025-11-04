<?php

namespace App\Http\Controllers;

use App\Exports\ReporteLineaPresupuestariaExport;
use App\Exports\ReporteResumenExport;
use App\Services\ReporteService;
use App\Exports\ReporteFinancieroExport;
use App\Exports\ReporteAvanceExport;
use App\Exports\ReporteComparativoExport;
use Illuminate\Http\Request; // ← CORRECCIÓN AQUÍ
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteController extends Controller
{
    protected $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Descargar reporte según tipo y formato
     */
    public function descargar(Request $request)
    {
        \Log::info('=== INICIO DESCARGA REPORTE ===');
        \Log::info('Request completo:', $request->all());

        try {
            // Validar parámetros
            $validated = $request->validate([
                'tipo' => 'required|in:resumen,financiero,avance,comparativo,linea_presupuestaria',
                'formato' => 'required|in:pdf,excel,csv',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'carta_id' => 'nullable|exists:cartas,id',
                'estado' => 'nullable|string',
            ]);

            \Log::info('Validación exitosa:', $validated);

            $tipo = $validated['tipo'];
            $formato = $validated['formato'];

            // Generar nombre de archivo
            $timestamp = now()->format('Y-m-d_His');
            $nombreArchivo = "reporte_{$tipo}_{$timestamp}";

            \Log::info('Obteniendo datos del reporte...');

            // Obtener datos según el tipo de reporte
            $datos = $this->obtenerDatosReporte($tipo, $request->all());

            \Log::info('Datos obtenidos, generando archivo...');

            // Generar según formato
            switch ($formato) {
                case 'excel':
                    \Log::info('Generando Excel...');
                    return $this->generarExcel($tipo, $datos, $nombreArchivo, $request);

                case 'pdf':
                    \Log::info('Generando PDF...');
                    return $this->generarPDF($tipo, $datos, $nombreArchivo, $request);

                case 'csv':
                    \Log::info('Generando CSV...');
                    return $this->generarCSV($tipo, $datos, $nombreArchivo, $request);

                default:
                    \Log::error('Formato no válido: ' . $formato);
                    abort(400, 'Formato no válido');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación:', $e->errors());
            return redirect()->back()->with('error', 'Error de validación: ' . json_encode($e->errors()));

        } catch (\Exception $e) {
            \Log::error('Error al generar reporte:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Obtener datos según el tipo de reporte
     */
    private function obtenerDatosReporte($tipo, $filtros)
    {
        \Log::info("Obteniendo datos para tipo: {$tipo}");

        try {
            switch ($tipo) {
                case 'resumen':
                    \Log::info('Tipo: Resumen');
                    $data = [
                        'estadisticas' => $this->reporteService->getEstadisticasGenerales(),
                        'alertas' => $this->reporteService->getAlertasYRiesgos(),
                    ];
                    \Log::info('Datos resumen obtenidos:', $data);
                    return $data;

                case 'financiero':
                    \Log::info('Tipo: Financiero');
                    return $this->reporteService->getDatosFinancieros($filtros);

                case 'avance':
                    \Log::info('Tipo: Avance');
                    return $this->reporteService->getReporteAvanceActividades($filtros);

                case 'comparativo':
                    \Log::info('Tipo: Comparativo');
                    $cartaIds = $filtros['carta_ids'] ?? [];
                    return $this->reporteService->getComparativoCartas($cartaIds);

                case 'linea_presupuestaria':
                    \Log::info('Tipo: Línea Presupuestaria');
                    return $this->reporteService->getAnalisisPorLineaPresupuestaria($filtros);

                default:
                    \Log::error('Tipo de reporte no válido: ' . $tipo);
                    throw new \Exception('Tipo de reporte no válido');
            }
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosReporte: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar archivo Excel
     */
    private function generarExcel($tipo, $datos, $nombreArchivo, $request)
    {
        $export = match($tipo) {
            'resumen' => new ReporteResumenExport($datos),
            'financiero' => new ReporteFinancieroExport(
                $datos,
                $request->fecha_inicio,
                $request->fecha_fin
            ),
            'avance' => new ReporteAvanceExport($datos),
            'comparativo' => new ReporteComparativoExport($datos),
            'linea_presupuestaria' => new ReporteLineaPresupuestariaExport($datos), // ← NUEVA LÍNEA
            default => new ReporteFinancieroExport($datos),
        };

        return Excel::download($export, $nombreArchivo . '.xlsx');
    }

    /**
     * Generar archivo PDF
     */
    private function generarPDF($tipo, $datos, $nombreArchivo, $request)
    {
        $vistaMap = [
            'resumen' => 'reportes.pdf.resumen',
            'financiero' => 'reportes.pdf.financiero',
            'avance' => 'reportes.pdf.avance',
            'comparativo' => 'reportes.pdf.comparativo',
            'linea_presupuestaria' => 'reportes.pdf.linea-presupuestaria',
        ];

        $vista = $vistaMap[$tipo] ?? 'reportes.pdf.financiero';

        $pdf = Pdf::loadView($vista, [
            'datos' => $datos,
            'fechaInicio' => $request->fecha_inicio,
            'fechaFin' => $request->fecha_fin,
            'fechaGeneracion' => now()->format('d/m/Y H:i:s'),
        ]);

        // Configuración del PDF
        $pdf->setPaper('letter', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);
        $pdf->setOption('margin-right', 10);

        return $pdf->download($nombreArchivo . '.pdf');
    }

    /**
     * Generar archivo CSV
     */
    private function generarCSV($tipo, $datos, $nombreArchivo, $request)
    {
        $export = match($tipo) {
            'financiero' => new ReporteFinancieroExport(
                $datos,
                $request->fecha_inicio,
                $request->fecha_fin
            ),
            default => new ReporteFinancieroExport($datos),
        };

        return Excel::download($export, $nombreArchivo . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
