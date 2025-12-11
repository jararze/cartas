<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
        try {
            // Validar parámetros
            $validated = $request->validate([
                'tipo' => 'required|in:resumen,financiero,avance,actividades,ejecutado_vs_planificado,plan_trabajo,lineas_presupuestarias',
                'formato' => 'required|in:pdf,excel',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'carta_id' => 'nullable|exists:cartas,id',
                'estado' => 'nullable|string',
            ]);

            $tipo = $validated['tipo'];
            $formato = $validated['formato'];

            // Preparar filtros
            $filtros = [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'carta_id' => $request->carta_id,
                'estado' => $request->estado,
            ];

            // Agregar proveedor_id si el usuario es proveedor
            $user = auth()->user();
            if ($user->hasRole('Proveedor') && $user->proveedor) {
                $filtros['proveedor_id'] = $user->proveedor->id;
            }

            // Generar reporte y obtener la URL
            $url = $this->reporteService->generarReporte($tipo, $formato, $filtros);

            // Extraer el path del storage desde la URL
            $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
            $fullPath = storage_path('app/public/' . $path);

            // Verificar que el archivo existe
            if (!file_exists($fullPath)) {
                throw new \Exception('Archivo no encontrado: ' . $fullPath);
            }

            // Nombre del archivo para descarga
            $extension = $formato === 'pdf' ? 'pdf' : 'xlsx';
            $filename = $tipo . '_' . now()->format('Y-m-d_His') . '.' . $extension;

            // Descargar archivo
            return response()->download($fullPath, $filename)->deleteFileAfterSend(true);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación:', $e->errors());
            return redirect()->back()->with('error', 'Error de validación: ' . json_encode($e->errors()));

        } catch (\Exception $e) {
            \Log::error('Error al generar reporte:', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return redirect()->back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }
}
