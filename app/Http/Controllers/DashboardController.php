<?php

namespace App\Http\Controllers;

use App\Models\Carta;
use App\Models\Producto;
use App\Models\Actividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ==========================================
        // 1. ESTADÍSTICAS GENERALES (KPIs)
        // ==========================================

        $presupuestoTotal = Carta::sum('monto_total') ?? 0;
        $gastoTotal = Actividad::sum('gasto_acumulado') ?? 0;

        $actividadesTotal = Actividad::count();
        $actividadesCompletadas = Actividad::where('estado', 'finalizado')->count();
        $actividadesEnCurso = Actividad::where('estado', 'en_curso')->count();
        $actividadesPendientes = Actividad::where('estado', 'pendiente')->count();
        $actividadesAtrasadas = Actividad::where('estado', 'atrasado')->count();

        $stats = [
            'presupuesto_total' => $presupuestoTotal,
            'gasto_total' => $gastoTotal,
            'saldo_disponible' => $presupuestoTotal - $gastoTotal,
            'cartas_activas' => Carta::whereIn('estado', ['en_ejecucion', 'aceptada'])->count(),
            'cartas_total' => Carta::count(),
            'cartas_pendientes' => Carta::where('estado', 'pendiente')->count(),
            'cartas_finalizadas' => Carta::where('estado', 'finalizada')->count(),
            'productos_total' => Producto::count(),
            'actividades_total' => $actividadesTotal,
            'actividades_completadas' => $actividadesCompletadas,
            'actividades_en_curso' => $actividadesEnCurso,
            'actividades_pendientes' => $actividadesPendientes,
            'actividades_atrasadas' => $actividadesAtrasadas,
            'progreso_general' => $actividadesTotal > 0
                ? round(($actividadesCompletadas / $actividadesTotal) * 100, 1)
                : 0,
            'ejecucion_presupuestaria' => $presupuestoTotal > 0
                ? round(($gastoTotal / $presupuestoTotal) * 100, 1)
                : 0,
        ];

        // ==========================================
        // 2. PROGRESO POR CARTA (Para gráfico de barras)
        // ==========================================

        $cartasProgreso = Carta::with('productos.actividades')
            ->whereIn('estado', ['en_ejecucion', 'aceptada'])
            ->get()
            ->map(function($carta) {
                $actividades = $carta->productos->flatMap->actividades;
                $progreso = $actividades->avg('progreso') ?? 0;

                return [
                    'id' => $carta->id,
                    'codigo' => $carta->codigo,
                    'progreso' => round($progreso, 1),
                    'actividades_total' => $actividades->count(),
                    'actividades_completadas' => $actividades->where('estado', 'finalizado')->count(),
                ];
            });

        // ==========================================
        // 3. ACTIVIDADES POR ESTADO (Para gráfico de dona)
        // ==========================================

        $actividadesPorEstado = [
            'completadas' => $actividadesCompletadas,
            'en_curso' => $actividadesEnCurso,
            'pendientes' => $actividadesPendientes,
            'atrasadas' => $actividadesAtrasadas,
        ];

        // ==========================================
        // 4. EJECUCIÓN PRESUPUESTARIA (Para gráfico de pastel)
        // ==========================================

        $presupuestoPorCarta = Carta::select('id', 'codigo', 'monto_total')
            ->whereIn('estado', ['en_ejecucion', 'aceptada'])
            ->get()
            ->map(function($carta) {
                $gastoActividades = $carta->productos()
                    ->with('actividades')
                    ->get()
                    ->flatMap->actividades
                    ->sum('gasto_acumulado');

                return [
                    'codigo' => $carta->codigo,
                    'presupuesto' => $carta->monto_total,
                    'gasto' => $gastoActividades,
                    'ejecucion' => $carta->monto_total > 0
                        ? round(($gastoActividades / $carta->monto_total) * 100, 1)
                        : 0,
                ];
            });

        // ==========================================
        // 5. ACTIVIDADES RECIENTES (Tabla)
        // ==========================================

        $actividadesRecientes = Actividad::with(['producto.carta'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($actividad) {
                return [
                    'id' => $actividad->id,
                    'nombre' => $actividad->nombre,
                    'producto' => $actividad->producto->nombre,
                    'carta_codigo' => $actividad->producto->carta->codigo,
                    'estado' => $actividad->estado,
                    'progreso' => $actividad->progreso,
                    'presupuesto' => $actividad->monto,
                    'gasto' => $actividad->gasto_acumulado,
                    'fecha_inicio' => $actividad->fecha_inicio,
                    'fecha_fin' => $actividad->fecha_fin,
                    'dias_restantes' => $actividad->fecha_fin
                        ? Carbon::parse($actividad->fecha_fin)->diffInDays(Carbon::now(), false)
                        : null,
                ];
            });

        // ==========================================
        // 6. NOTIFICACIONES Y ALERTAS
        // ==========================================

        $notificaciones = [];

        // Actividades atrasadas
        $actividadesAtrasadasList = Actividad::where('estado', 'atrasado')
            ->with('producto.carta')
            ->orderBy('fecha_fin', 'asc')
            ->limit(5)
            ->get();

        foreach ($actividadesAtrasadasList as $actividad) {
            $notificaciones[] = [
                'tipo' => 'error',
                'titulo' => 'Actividad atrasada',
                'mensaje' => "{$actividad->nombre} - {$actividad->producto->carta->codigo}",
                'fecha' => $actividad->fecha_fin,
                'icono' => 'alert-circle',
            ];
        }

        // Actividades próximas a vencer (próximos 7 días)
        $actividadesProximasVencer = Actividad::where('estado', 'en_curso')
            ->whereBetween('fecha_fin', [Carbon::now(), Carbon::now()->addDays(7)])
            ->with('producto.carta')
            ->orderBy('fecha_fin', 'asc')
            ->limit(5)
            ->get();

        foreach ($actividadesProximasVencer as $actividad) {
            $diasRestantes = Carbon::parse($actividad->fecha_fin)->diffInDays(Carbon::now());
            $notificaciones[] = [
                'tipo' => 'warning',
                'titulo' => 'Próximo vencimiento',
                'mensaje' => "{$actividad->nombre} - Vence en {$diasRestantes} días",
                'fecha' => $actividad->fecha_fin,
                'icono' => 'clock',
            ];
        }

        // Actividades completadas recientemente (últimas 24 horas)
        $actividadesCompletadasRecientes = Actividad::where('estado', 'finalizado')
            ->where('updated_at', '>=', Carbon::now()->subDay())
            ->with('producto.carta')
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($actividadesCompletadasRecientes as $actividad) {
            $notificaciones[] = [
                'tipo' => 'success',
                'titulo' => 'Actividad completada',
                'mensaje' => "{$actividad->nombre} - {$actividad->producto->carta->codigo}",
                'fecha' => $actividad->updated_at,
                'icono' => 'check-circle',
            ];
        }

        // Presupuesto en alerta (>80% ejecutado)
        $cartasPresupuestoAlerta = Carta::whereIn('estado', ['en_ejecucion', 'aceptada'])
            ->get()
            ->filter(function($carta) {
                $gastoActividades = $carta->productos()
                    ->with('actividades')
                    ->get()
                    ->flatMap->actividades
                    ->sum('gasto_acumulado');

                return $carta->monto_total > 0 &&
                    (($gastoActividades / $carta->monto_total) * 100) > 80;
            });

        foreach ($cartasPresupuestoAlerta as $carta) {
            $notificaciones[] = [
                'tipo' => 'warning',
                'titulo' => 'Alerta de presupuesto',
                'mensaje' => "{$carta->codigo} - Más del 80% ejecutado",
                'fecha' => Carbon::now(),
                'icono' => 'alert-triangle',
            ];
        }

        // Ordenar notificaciones por fecha (más recientes primero)
        usort($notificaciones, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        // Limitar a las 10 más recientes
        $notificaciones = array_slice($notificaciones, 0, 10);

        // ==========================================
        // 7. TENDENCIAS Y ANÁLISIS (últimos 6 meses)
        // ==========================================

        $tendencias = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $mesInicio = $mes->copy()->startOfMonth();
            $mesFin = $mes->copy()->endOfMonth();

            $actividadesCompletadasMes = Actividad::where('estado', 'finalizado')
                ->whereBetween('updated_at', [$mesInicio, $mesFin])
                ->count();

            $gastoMes = Actividad::whereBetween('updated_at', [$mesInicio, $mesFin])
                ->sum('gasto_acumulado');

            $tendencias[] = [
                'mes' => $mes->locale('es')->isoFormat('MMM YYYY'),
                'actividades_completadas' => $actividadesCompletadasMes,
                'gasto' => $gastoMes,
            ];
        }

        // ==========================================
        // 8. TOP CARTAS (por progreso y ejecución)
        // ==========================================

        $topCartasProgreso = $cartasProgreso->sortByDesc('progreso')->take(5)->values();
        $topCartasEjecucion = $presupuestoPorCarta->sortByDesc('ejecucion')->take(5)->values();

        // ==========================================
        // 9. CONSOLIDAR DATOS PARA GRÁFICOS
        // ==========================================

        $chartData = [
            'cartas_progreso' => $cartasProgreso->values()->toArray(),
            'actividades_por_estado' => $actividadesPorEstado,
            'presupuesto_por_carta' => $presupuestoPorCarta->values()->toArray(),
            'tendencias' => $tendencias,
            'top_cartas_progreso' => $topCartasProgreso->toArray(),
            'top_cartas_ejecucion' => $topCartasEjecucion->toArray(),
        ];

        // ==========================================
        // 10. RETORNAR VISTA CON TODOS LOS DATOS
        // ==========================================

        return view('dashboard', compact(
            'stats',
            'chartData',
            'actividadesRecientes',
            'notificaciones'
        ));
    }
}
