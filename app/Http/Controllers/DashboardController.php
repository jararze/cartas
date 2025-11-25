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
    public function index(Request $request)
    {
        $user = auth()->user();
        $busqueda = $request->get('busqueda', '');

        // SI ES PROVEEDOR, DASHBOARD PERSONALIZADO
        if ($user->hasRole('Proveedor') && $user->proveedor) {
            return $this->dashboardProveedor($user, $busqueda);
        }

        $busqueda = $request->get('busqueda', '');
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

        // Estadísticas de cartas
        $cartasTotal = Carta::count();
        $cartasActivas = Carta::whereIn('estado', ['en_ejecucion', 'aceptada'])->count();
        $cartasPendientes = Carta::where('estado', 'pendiente')->count();
        $cartasFinalizadas = Carta::where('estado', 'finalizada')->count();
        $cartasBorrador = Carta::where('estado', 'borrador')->count();

        $stats = [
            'presupuesto_total' => $presupuestoTotal,
            'gasto_total' => $gastoTotal,
            'saldo_disponible' => $presupuestoTotal - $gastoTotal,
            'cartas_activas' => $cartasActivas,
            'cartas_total' => $cartasTotal,
            'cartas_pendientes' => $cartasPendientes,
            'cartas_finalizadas' => $cartasFinalizadas,
            'cartas_borrador' => $cartasBorrador,
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
        // 5. CARTAS RECIENTES (Tabla) - CON BÚSQUEDA Y PAGINACIÓN
        // ==========================================

        $cartasQuery = Carta::with(['proveedor', 'productos.actividades'])
            ->orderBy('updated_at', 'desc');

        // Aplicar búsqueda si existe
        if ($busqueda) {
            $cartasQuery->where(function($query) use ($busqueda) {
                $query->where('codigo', 'like', "%{$busqueda}%")
                    ->orWhere('nombre_proyecto', 'like', "%{$busqueda}%")
                    ->orWhereHas('proveedor', function($q) use ($busqueda) {
                        $q->where('nombre', 'like', "%{$busqueda}%");
                    });
            });
        }

        $cartasRecientes = $cartasQuery->paginate(10)->through(function($carta) {
            $actividades = $carta->productos->flatMap->actividades;
            $progreso = $actividades->avg('progreso') ?? 0;
            $actividadesTotal = $actividades->count();
            $actividadesCompletadas = $actividades->where('estado', 'finalizado')->count();

            return [
                'id' => $carta->id,
                'codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'proveedor' => $carta->proveedor ? $carta->proveedor->nombre : 'Sin proveedor',
                'estado' => $carta->estado,
                'progreso' => round($progreso, 1),
                'presupuesto' => $carta->monto_total,
                'gasto' => $actividades->sum('gasto_acumulado'),
                'fecha_inicio' => $carta->fecha_inicio,
                'fecha_fin' => $carta->fecha_fin,
                'actividades_total' => $actividadesTotal,
                'actividades_completadas' => $actividadesCompletadas,
                'dias_restantes' => $carta->fecha_fin
                    ? Carbon::parse($carta->fecha_fin)->diffInDays(Carbon::now(), false)
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
        // 10. RETORNAR VISTA CON TODOS LOS DATOS - MODIFICADO
        // ==========================================

        return view('dashboard', compact(
            'stats',
            'chartData',
            'cartasRecientes',
            'notificaciones',
            'busqueda'
        ));
    }


    private function dashboardProveedor($user, $busqueda)
    {
        $proveedor = $user->proveedor;

        // Invitaciones pendientes
        $invitacionesPendientes = Carta::where('proveedor_id', $proveedor->id)
            ->whereIn('estado', ['enviada', 'vista'])
            ->with('creador')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($carta) {
                return [
                    'id' => $carta->id,
                    'codigo' => $carta->codigo,
                    'nombre_proyecto' => $carta->nombre_proyecto,
                    'responsable' => $carta->responsable_fao_nombre,
                    'fecha_envio' => $carta->fecha_envio,
                    'dias_sin_responder' => $carta->fecha_envio ?
                        Carbon::parse($carta->fecha_envio)->diffInDays(Carbon::now()) : 0,
                ];
            });

        // Cartas activas (aceptadas)
        $cartasActivas = Carta::where('proveedor_id', $proveedor->id)
            ->whereIn('estado', ['aceptada', 'en_ejecucion'])
            ->with('productos.actividades')
            ->get();

        // Estadísticas del proveedor
        $stats = [
            'invitaciones_pendientes' => $invitacionesPendientes->count(),
            'cartas_activas' => $cartasActivas->count(),
            'cartas_finalizadas' => Carta::where('proveedor_id', $proveedor->id)
                ->where('estado', 'finalizada')
                ->count(),
            'cartas_rechazadas' => Carta::where('proveedor_id', $proveedor->id)
                ->where('estado', 'rechazada')
                ->count(),
        ];

        // Actividades del proveedor
        $actividades = $cartasActivas->flatMap(function($carta) {
            return $carta->productos->flatMap->actividades;
        });

        $stats['actividades_total'] = $actividades->count();
        $stats['actividades_completadas'] = $actividades->where('estado', 'finalizado')->count();
        $stats['actividades_en_curso'] = $actividades->where('estado', 'en_curso')->count();
        $stats['actividades_atrasadas'] = $actividades->where('estado', 'atrasado')->count();
        $stats['progreso_general'] = $stats['actividades_total'] > 0
            ? round(($stats['actividades_completadas'] / $stats['actividades_total']) * 100, 1)
            : 0;

        // Cartas recientes con búsqueda
        $cartasQuery = Carta::where('proveedor_id', $proveedor->id)
            ->with('productos.actividades')
            ->orderBy('updated_at', 'desc');

        if ($busqueda) {
            $cartasQuery->where(function($query) use ($busqueda) {
                $query->where('codigo', 'like', "%{$busqueda}%")
                    ->orWhere('nombre_proyecto', 'like', "%{$busqueda}%");
            });
        }

        $cartasRecientes = $cartasQuery->paginate(10)->through(function($carta) {
            $actividades = $carta->productos->flatMap->actividades;
            $progreso = $actividades->avg('progreso') ?? 0;

            return [
                'id' => $carta->id,
                'codigo' => $carta->codigo,
                'nombre_proyecto' => $carta->nombre_proyecto,
                'estado' => $carta->estado,
                'progreso' => round($progreso, 1),
                'fecha_inicio' => $carta->fecha_inicio,
                'fecha_fin' => $carta->fecha_fin,
                'actividades_total' => $actividades->count(),
                'actividades_completadas' => $actividades->where('estado', 'finalizado')->count(),
            ];
        });

        return view('dashboard-proveedor', compact(
            'stats',
            'invitacionesPendientes',
            'cartasRecientes',
            'busqueda'
        ));
    }

    
}
