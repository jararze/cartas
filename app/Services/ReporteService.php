<?php

namespace App\Services;

use App\Models\Carta;
use App\Models\Producto;
use App\Models\Actividad;
use App\Models\SeguimientoActividad;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteService
{
    /**
     * Obtener estadísticas generales del sistema
     */
    public function getEstadisticasGenerales()
    {
        $totalCartas = Carta::count();
        $totalPresupuesto = Carta::sum('monto_total');
        $totalEjecutado = Actividad::sum('gasto_acumulado');
        $saldoDisponible = $totalPresupuesto - $totalEjecutado;

        $cartasActivas = Carta::whereIn('estado', ['en_ejecucion', 'enviada', 'aceptada'])->count();
        $cartasFinalizadas = Carta::where('estado', 'finalizada')->count();

        $actividadesTotal = Actividad::count();
        $actividadesCompletadas = Actividad::where('estado', 'finalizado')->count();
        $actividadesEnCurso = Actividad::where('estado', 'en_curso')->count();
        $actividadesPendientes = Actividad::where('estado', 'pendiente')->count();
        $actividadesAtrasadas = Actividad::where('estado', 'atrasado')->count();

        $progresoPromedio = Actividad::avg('progreso') ?? 0;
        $ejecucionPresupuestaria = $totalPresupuesto > 0
            ? ($totalEjecutado / $totalPresupuesto) * 100
            : 0;

        return [
            'total_cartas' => $totalCartas,
            'cartas_activas' => $cartasActivas,
            'cartas_finalizadas' => $cartasFinalizadas,

            'total_presupuesto' => $totalPresupuesto,
            'total_ejecutado' => $totalEjecutado,
            'saldo_disponible' => $saldoDisponible,
            'ejecucion_presupuestaria' => round($ejecucionPresupuestaria, 2),

            'actividades_total' => $actividadesTotal,
            'actividades_completadas' => $actividadesCompletadas,
            'actividades_en_curso' => $actividadesEnCurso,
            'actividades_pendientes' => $actividadesPendientes,
            'actividades_atrasadas' => $actividadesAtrasadas,

            'progreso_promedio' => round($progresoPromedio, 2),
        ];
    }

    /**
     * Obtener datos financieros con filtros
     */
    public function getDatosFinancieros($filtros = [])
    {
        $query = Carta::with(['productos.actividades']);

        // Aplicar filtros
        if (!empty($filtros['fecha_inicio'])) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['carta_id'])) {
            $query->where('id', $filtros['carta_id']);
        }

        $cartas = $query->get();

        return $cartas->map(function ($carta) {
            $presupuestoTotal = $carta->productos->sum(function ($producto) {
                return $producto->actividades->sum('monto');
            });

            $ejecutadoTotal = $carta->productos->sum(function ($producto) {
                return $producto->actividades->sum('gasto_acumulado');
            });

            $progresoPromedio = $carta->productos->avg(function ($producto) {
                return $producto->actividades->avg('progreso');
            }) ?? 0;

            return [
                'id' => $carta->id,
                'codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'presupuesto_total' => $presupuestoTotal,
                'ejecutado_total' => $ejecutadoTotal,
                'saldo' => $presupuestoTotal - $ejecutadoTotal,
                'porcentaje_ejecucion' => $presupuestoTotal > 0
                    ? round(($ejecutadoTotal / $presupuestoTotal) * 100, 2)
                    : 0,
                'progreso_promedio' => round($progresoPromedio, 2),
                'fecha_inicio' => $carta->fecha_inicio,
                'fecha_fin' => $carta->fecha_fin,
                'estado' => $carta->estado,
                'productos_count' => $carta->productos->count(),
                'actividades_count' => $carta->productos->sum(function ($p) {
                    return $p->actividades->count();
                }),
            ];
        });
    }

    /**
     * Obtener reporte de avance de actividades
     */
    public function getReporteAvanceActividades($filtros = [])
    {
        $query = Actividad::with(['producto.carta', 'responsable', 'seguimientos']);

        if (!empty($filtros['carta_id'])) {
            $query->whereHas('producto', function ($q) use ($filtros) {
                $q->where('carta_id', $filtros['carta_id']);
            });
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_inicio'])) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->where('fecha_fin', '<=', $filtros['fecha_fin']);
        }

        return $query->get()->map(function ($actividad) {
            $diasPlanificados = Carbon::parse($actividad->fecha_inicio)
                ->diffInDays(Carbon::parse($actividad->fecha_fin));

            $diasTranscurridos = Carbon::parse($actividad->fecha_inicio)
                ->diffInDays(now());

            $avanceEsperado = $diasPlanificados > 0
                ? min(100, ($diasTranscurridos / $diasPlanificados) * 100)
                : 0;

            $desviacion = $actividad->progreso - $avanceEsperado;

            return [
                'id' => $actividad->id,
                'nombre' => $actividad->nombre,
                'carta' => $actividad->producto->carta->codigo,
                'producto' => $actividad->producto->nombre,
                'linea_presupuestaria' => $actividad->linea_presupuestaria,
                'presupuesto' => $actividad->monto,
                'ejecutado' => $actividad->gasto_acumulado,
                'saldo' => $actividad->monto - $actividad->gasto_acumulado,
                'progreso_real' => $actividad->progreso,
                'avance_esperado' => round($avanceEsperado, 2),
                'desviacion' => round($desviacion, 2),
                'estado' => $actividad->estado,
                'fecha_inicio' => $actividad->fecha_inicio,
                'fecha_fin' => $actividad->fecha_fin,
                'responsable' => $actividad->responsable->name ?? 'Sin asignar',
                'seguimientos_count' => $actividad->seguimientos->count(),
            ];
        });
    }

    /**
     * Obtener comparativo entre cartas
     */
    public function getComparativoCartas($cartaIds = [])
    {
        $query = Carta::with(['productos.actividades']);

        if (!empty($cartaIds)) {
            $query->whereIn('id', $cartaIds);
        }

        return $query->get()->map(function ($carta) {
            $actividades = $carta->productos->flatMap->actividades;

            $presupuestoTotal = $actividades->sum('monto');
            $ejecutadoTotal = $actividades->sum('gasto_acumulado');

            return [
                'codigo' => $carta->codigo,
                'nombre' => $carta->nombre_proyecto,
                'presupuesto' => $presupuestoTotal,
                'ejecutado' => $ejecutadoTotal,
                'eficiencia' => $presupuestoTotal > 0
                    ? round(($ejecutadoTotal / $presupuestoTotal) * 100, 2)
                    : 0,
                'progreso' => round($actividades->avg('progreso'), 2),
                'actividades_total' => $actividades->count(),
                'actividades_completadas' => $actividades->where('estado', 'finalizado')->count(),
                'productos_count' => $carta->productos->count(),
                'duracion_dias' => Carbon::parse($carta->fecha_inicio)
                    ->diffInDays(Carbon::parse($carta->fecha_fin)),
            ];
        });
    }

    /**
     * Obtener análisis de desempeño por línea presupuestaria
     */
    public function getAnalisisPorLineaPresupuestaria($filtros = [])
    {
        $query = Actividad::select(
            'linea_presupuestaria',
            DB::raw('COUNT(*) as total_actividades'),
            DB::raw('SUM(monto) as presupuesto_total'),
            DB::raw('SUM(gasto_acumulado) as ejecutado_total'),
            DB::raw('AVG(progreso) as progreso_promedio'),
            DB::raw('COUNT(CASE WHEN estado = "finalizado" THEN 1 END) as completadas'),
            DB::raw('COUNT(CASE WHEN estado = "atrasado" THEN 1 END) as atrasadas')
        );

        if (!empty($filtros['carta_id'])) {
            $query->whereHas('producto', function ($q) use ($filtros) {
                $q->where('carta_id', $filtros['carta_id']);
            });
        }

        return $query->groupBy('linea_presupuestaria')
            ->get()
            ->map(function ($item) {
                $eficiencia = $item->presupuesto_total > 0
                    ? ($item->ejecutado_total / $item->presupuesto_total) * 100
                    : 0;

                return [
                    'linea' => $item->linea_presupuestaria,
                    'actividades' => $item->total_actividades,
                    'presupuesto' => $item->presupuesto_total,
                    'ejecutado' => $item->ejecutado_total,
                    'saldo' => $item->presupuesto_total - $item->ejecutado_total,
                    'eficiencia' => round($eficiencia, 2),
                    'progreso' => round($item->progreso_promedio, 2),
                    'completadas' => $item->completadas,
                    'atrasadas' => $item->atrasadas,
                    'tasa_completitud' => $item->total_actividades > 0
                        ? round(($item->completadas / $item->total_actividades) * 100, 2)
                        : 0,
                ];
            });
    }

    /**
     * Obtener timeline de seguimientos
     */
    public function getTimelineSeguimientos($actividadId)
    {
        return SeguimientoActividad::where('actividad_id', $actividadId)
            ->with(['registradoPor'])
            ->orderBy('fecha_registro', 'desc')
            ->get()
            ->map(function ($seguimiento) {
                return [
                    'fecha' => $seguimiento->fecha_registro,
                    'progreso_anterior' => $seguimiento->progreso_anterior,
                    'progreso_nuevo' => $seguimiento->progreso_nuevo,
                    'incremento_progreso' => $seguimiento->progreso_nuevo - $seguimiento->progreso_anterior,
                    'monto_gastado' => $seguimiento->monto_gastado,
                    'gasto_acumulado' => $seguimiento->gasto_acumulado_nuevo,
                    'descripcion' => $seguimiento->descripcion_avance,
                    'responsable' => $seguimiento->responsable_nombre,
                    'registrado_por' => $seguimiento->registradoPor->name,
                    'excede_presupuesto' => $seguimiento->excede_presupuesto,
                    'nivel_riesgo' => $seguimiento->nivel_riesgo,
                ];
            });
    }

    /**
     * Obtener alertas y riesgos
     */
    public function getAlertasYRiesgos()
    {
        $actividadesAtrasadas = Actividad::where('estado', 'atrasado')->count();

        $actividadesExcedenPresupuesto = Actividad::whereRaw('gasto_acumulado > monto')->count();

        $actividadesRiesgoAlto = SeguimientoActividad::where('nivel_riesgo', 'alto')
            ->orWhere('nivel_riesgo', 'critico')
            ->distinct('actividad_id')
            ->count();

        $actividadesProximasVencer = Actividad::where('estado', 'en_curso')
            ->where('fecha_fin', '<=', now()->addDays(7))
            ->count();

        return [
            'atrasadas' => $actividadesAtrasadas,
            'exceden_presupuesto' => $actividadesExcedenPresupuesto,
            'riesgo_alto' => $actividadesRiesgoAlto,
            'proximas_vencer' => $actividadesProximasVencer,
            'total_alertas' => $actividadesAtrasadas + $actividadesExcedenPresupuesto +
                $actividadesRiesgoAlto + $actividadesProximasVencer,
        ];
    }

    /**
     * Obtener tendencias mensuales
     */
    public function getTendenciasMensuales($meses = 6)
    {
        $fechaInicio = now()->subMonths($meses)->startOfMonth();

        return DB::table('seguimiento_actividades')
            ->select(
                DB::raw('DATE_FORMAT(fecha_registro, "%Y-%m") as mes'),
                DB::raw('SUM(monto_gastado) as gasto_mensual'),
                DB::raw('COUNT(*) as seguimientos_registrados'),
                DB::raw('AVG(progreso_nuevo - progreso_anterior) as incremento_promedio')
            )
            ->where('fecha_registro', '>=', $fechaInicio)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }
}
