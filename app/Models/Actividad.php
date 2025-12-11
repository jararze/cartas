<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Actividad extends Model
{
    use HasFactory;

    protected $table = 'actividades';

    protected $guarded = [];

    protected $casts = [
        'monto' => 'decimal:2',
        'gasto_acumulado' => 'decimal:2',
        'progreso' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_inicio_real' => 'date',
        'fecha_fin_real' => 'date',
        'fecha_solicitud_cancelacion' => 'datetime',
        'fecha_respuesta_cancelacion' => 'datetime',
    ];

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(SeguimientoActividad::class)->orderBy('fecha_registro', 'desc');
    }

    /**
     * Obtener el último seguimiento
     */
    public function ultimoSeguimiento()
    {
        return $this->hasOne(SeguimientoActividad::class)->latestOfMany('fecha_registro');
    }

    /**
     * Obtener seguimientos que exceden presupuesto
     */
    public function seguimientosConExceso()
    {
        return $this->seguimientos()->where('excede_presupuesto', true);
    }

    // Métodos calculados
    public function getSaldoDisponibleAttribute(): float
    {
        return $this->monto - $this->gasto_acumulado;
    }

    public function getPorcentajeEjecutadoAttribute(): float
    {
        return $this->monto > 0 ? round(($this->gasto_acumulado / $this->monto) * 100, 2) : 0;
    }

    public function getEstaAtrasadoAttribute(): bool
    {
        return $this->fecha_fin < now() && $this->progreso < 100;
    }

    public function getExcedePresupuestoAttribute(): bool
    {
        return $this->gasto_acumulado > $this->monto;
    }

    public function getDiasRestantesAttribute(): int
    {
        return max(0, now()->diffInDays($this->fecha_fin, false));
    }

    public function getDuracionTotalDiasAttribute(): int
    {
        return $this->fecha_inicio->diffInDays($this->fecha_fin);
    }

    // Scopes
    public function scopeAtrasadas($query)
    {
        return $query->where('fecha_fin', '<', now())
            ->where('progreso', '<', 100)
            ->whereNotIn('estado', ['finalizado', 'cancelado']);
    }

    public function scopeEnCurso($query)
    {
        return $query->where('estado', 'en_curso');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeFinalizadas($query)
    {
        return $query->where('estado', 'finalizado');
    }

    public function scopeConExcesoPresupuesto($query)
    {
        return $query->whereRaw('gasto_acumulado > monto');
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    // Métodos de actualización
    public function actualizarProgreso(float $nuevoProgreso, float $montoGastado, string $descripcion, User $usuario, array $datos = []): SeguimientoActividad
    {
        $progresoAnterior = $this->progreso;
        $gastoAnterior = $this->gasto_acumulado;
        $estadoAnterior = $this->estado;

        // Actualizar actividad
        $this->progreso = $nuevoProgreso;
        $this->gasto_acumulado += $montoGastado;

        // Actualizar fechas reales si es la primera vez
        if ($nuevoProgreso > 0 && !$this->fecha_inicio_real) {
            $this->fecha_inicio_real = now();
        }

        if ($nuevoProgreso >= 100) {
            $this->estado = 'finalizado';
            $this->fecha_fin_real = now();
        } elseif ($nuevoProgreso > 0) {
            $this->estado = 'en_curso';
        }

        // Verificar si está atrasado
        if ($this->esta_atrasado && $this->estado !== 'finalizado') {
            $this->estado = 'atrasado';
        }

        // Actualizar fechas si se proporcionaron
        if (isset($datos['nueva_fecha_inicio'])) {
            $this->fecha_inicio = $datos['nueva_fecha_inicio'];
        }
        if (isset($datos['nueva_fecha_fin'])) {
            $this->fecha_fin = $datos['nueva_fecha_fin'];
        }

        $this->save();

        // Crear registro de seguimiento
        return $this->seguimientos()->create([
            'progreso_anterior' => $progresoAnterior,
            'progreso_nuevo' => $nuevoProgreso,
            'monto_gastado' => $montoGastado,
            'gasto_acumulado_anterior' => $gastoAnterior,
            'gasto_acumulado_nuevo' => $this->gasto_acumulado,
            'descripcion_avance' => $descripcion,
            'nueva_fecha_inicio' => $datos['nueva_fecha_inicio'] ?? null,
            'nueva_fecha_fin' => $datos['nueva_fecha_fin'] ?? null,
            'responsable_nombre' => $datos['responsable_nombre'] ?? $usuario->name,
            'observaciones' => $datos['observaciones'] ?? null,
            'dificultades' => $datos['dificultades'] ?? null,
            'logros' => $datos['logros'] ?? null,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $this->estado,
            'excede_presupuesto' => $this->excede_presupuesto,
            'esta_atrasado' => $this->esta_atrasado,
            'registrado_por' => $usuario->id,
            'fecha_registro' => now(),
        ]);
    }

    public function solicitarCancelacion(string $motivo, int $userId): bool
    {
        if ($this->estado === 'cancelado' || $this->estado === 'pendiente_cancelacion') {
            return false;
        }

        $this->update([
            'estado_anterior_cancelacion' => $this->estado,
            'estado' => 'pendiente_cancelacion',
            'motivo_cancelacion' => $motivo,
            'fecha_solicitud_cancelacion' => now(),
            'solicitado_por' => $userId,
            'estado_cancelacion' => 'pendiente',
        ]);

        return true;
    }

    public function aprobarCancelacion(int $userId, ?string $respuesta = null): bool
    {
        if ($this->estado !== 'pendiente_cancelacion') {
            return false;
        }

        $this->update([
            'estado' => 'cancelado',
            'estado_cancelacion' => 'aprobada',
            'respuesta_cancelacion' => $respuesta,
            'aprobado_por' => $userId,
            'fecha_respuesta_cancelacion' => now(),
        ]);

        // Verificar si el producto debe actualizarse
        $this->producto->verificarYActualizarEstado();

        return true;
    }

    public function rechazarCancelacion(int $userId, string $respuesta): bool
    {
        if ($this->estado !== 'pendiente_cancelacion') {
            return false;
        }

        $this->update([
            'estado' => $this->estado_anterior_cancelacion,
            'estado_cancelacion' => 'rechazada',
            'respuesta_cancelacion' => $respuesta,
            'aprobado_por' => $userId,
            'fecha_respuesta_cancelacion' => now(),
            'estado_anterior_cancelacion' => null,
        ]);

        return true;
    }

// Scopes útiles
    public function scopePendientesCancelacion($query)
    {
        return $query->where('estado', 'pendiente_cancelacion');
    }

    public function scopeActivas($query)
    {
        return $query->whereNotIn('estado', ['cancelado', 'pendiente_cancelacion']);
    }
    public function scopePendientesCancelacionParaCoordinador($query, $userId)
    {
        return $query->where('estado', 'pendiente_cancelacion')
            ->where('estado_cancelacion', 'pendiente')
            ->whereHas('producto.carta', function($q) use ($userId) {
                $q->where('creado_por', $userId);  // ← CORREGIDO
            });
    }
}
