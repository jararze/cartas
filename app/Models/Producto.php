<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Actividad;

class Producto extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'presupuesto' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'indicadores_kpi' => 'array',
    ];

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROGRESO = 'en_progreso';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_DESEMBOLSADO = 'desembolsado';

    // Relaciones
    public function carta(): BelongsTo
    {
        return $this->belongsTo(Carta::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class)->orderBy('fecha_inicio');
    }

    public function desembolsos(): HasMany
    {
        return $this->hasMany(Desembolso::class);
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    // Métodos calculados
    public function getPresupuestoTotalAttribute(): float
    {
        return $this->actividades->sum('monto');
    }

    public function getGastoTotalAttribute(): float
    {
        return $this->actividades->sum('gasto_acumulado');
    }

    public function getSaldoDisponibleAttribute(): float
    {
        return $this->presupuesto_total - $this->gasto_total;
    }

    public function getProgresoPromedioAttribute(): float
    {
        $actividades = $this->actividades;
        return $actividades->count() > 0 ? round($actividades->avg('progreso'), 2) : 0;
    }

    public function getPorcentajeEjecutadoAttribute(): float
    {
        return $this->presupuesto_total > 0 ?
            round(($this->gasto_total / $this->presupuesto_total) * 100, 2) : 0;
    }

    public function getTieneActividadesAtrasadasAttribute(): bool
    {
        return $this->actividades()->atrasadas()->exists();
    }

    public function getTieneExcesoPresupuestoAttribute(): bool
    {
        return $this->actividades()->conExcesoPresupuesto()->exists();
    }

    // Scopes
    public function scopeConActividades($query)
    {
        return $query->with(['actividades' => function($q) {
            $q->orderBy('fecha_inicio');
        }]);
    }

    /**
     * Presupuesto real basado en actividades
     */
    public function getPresupuestoRealAttribute(): float
    {
        return $this->actividades->sum('monto');
    }

    /**
     * Verificar si excede el presupuesto estimado
     */
    public function getExcedePresupuestoAttribute(): bool
    {
        return $this->presupuesto_real > $this->presupuesto;
    }

    /**
     * Diferencia entre presupuesto estimado y real
     */
    public function getDiferenciaPresupuestoAttribute(): float
    {
        return $this->presupuesto - $this->presupuesto_real;
    }

    /**
     * Porcentaje utilizado del presupuesto estimado
     */
    public function getPorcentajeUtilizadoAttribute(): float
    {
        return $this->presupuesto > 0
            ? round(($this->presupuesto_real / $this->presupuesto) * 100, 2)
            : 0;
    }

    public static function estados(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_EN_PROGRESO => 'En Progreso',
            self::ESTADO_COMPLETADO => 'Completado',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
            self::ESTADO_DESEMBOLSADO => 'Desembolsado',
        ];
    }

    // Verificar si está completado (100% progreso)
    public function getEstaCompletadoAttribute(): bool
    {
        return $this->progreso_promedio >= 100;
    }

// Verificar si puede solicitar desembolso
    public function getPuedeSolicitarDesembolsoAttribute(): bool
    {
        return $this->estado === self::ESTADO_APROBADO
            && !$this->desembolsos()->whereIn('estado', ['pendiente', 'en_proceso', 'pagado'])->exists();
    }

// Verificar si está listo para aprobación del coordinador
    public function getListoParaAprobacionAttribute(): bool
    {
        return $this->esta_completado && $this->estado === self::ESTADO_EN_PROGRESO;
    }

// Badge de estado
    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Pendiente</span>',
            self::ESTADO_EN_PROGRESO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">En Progreso</span>',
            self::ESTADO_COMPLETADO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Completado</span>',
            self::ESTADO_APROBADO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprobado</span>',
            self::ESTADO_RECHAZADO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rechazado</span>',
            self::ESTADO_DESEMBOLSADO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Desembolsado</span>',
            default => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">-</span>',
        };
    }

// Obtener desglose por línea presupuestaria
    public function getDesglosePorLineaAttribute(): array
    {
        return Actividad::where('producto_id', $this->id)
            ->selectRaw('linea_presupuestaria, SUM(monto) as total_planificado, SUM(gasto_acumulado) as total_ejecutado')
            ->groupBy('linea_presupuestaria')
            ->get()
            ->map(fn($item) => [
                'linea' => $item->linea_presupuestaria,
                'planificado' => (float) $item->total_planificado,
                'ejecutado' => (float) $item->total_ejecutado,
            ])
            ->toArray();
    }

// Aprobar producto (Coordinador)
    public function aprobar(int $userId, ?string $observaciones = null): void
    {
        $this->update([
            'estado' => self::ESTADO_APROBADO,
            'aprobado_por' => $userId,
            'fecha_aprobacion' => now(),
        ]);
    }

// Rechazar producto (Coordinador)
    public function rechazar(int $userId, string $motivo): void
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADO,
            'aprobado_por' => $userId,
            'fecha_aprobacion' => now(),
            'motivo_rechazo' => $motivo,
        ]);
    }

// Marcar como completado automáticamente
    public function verificarYActualizarEstado(): void
    {
        // Obtener solo actividades activas (no canceladas ni pendientes de cancelación)
        $actividadesActivas = $this->actividades()
            ->whereNotIn('estado', ['cancelado', 'pendiente_cancelacion'])
            ->get();

        // Si no hay actividades activas, no cambiar estado
        if ($actividadesActivas->isEmpty()) {
            return;
        }

        // Verificar si todas las actividades activas están finalizadas
        $todasFinalizadas = $actividadesActivas->every(function ($actividad) {
            return $actividad->progreso >= 100 || $actividad->estado === 'finalizado';
        });

        if ($todasFinalizadas && $this->estado !== 'completado') {
            $this->update(['estado' => 'completado']);
        }
    }

// Accessor para progreso considerando solo actividades activas
    public function getProgresoCalculadoAttribute(): float
    {
        $actividadesActivas = $this->actividades()
            ->whereNotIn('estado', ['cancelado', 'pendiente_cancelacion'])
            ->get();

        if ($actividadesActivas->isEmpty()) {
            return 0;
        }

        return round($actividadesActivas->avg('progreso'), 2);
    }

// Accessor para presupuesto de actividades activas
    public function getPresupuestoActivoAttribute(): float
    {
        return $this->actividades()
            ->whereNotIn('estado', ['cancelado', 'pendiente_cancelacion'])
            ->sum('monto');
    }

    public function getGastoActivoAttribute(): float
    {
        return $this->actividades()
            ->whereNotIn('estado', ['cancelado', 'pendiente_cancelacion'])
            ->sum('gasto_acumulado');
    }

// Scopes
    public function scopeCompletados($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }

    public function scopePendientesAprobacion($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    public function scopeListosParaDesembolso($query)
    {
        return $query->where('estado', self::ESTADO_APROBADO);
    }
}
