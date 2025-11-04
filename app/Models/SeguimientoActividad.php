<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoActividad extends Model
{
    use HasFactory;

    protected $table = 'seguimiento_actividades';

    protected $guarded = [];

    protected $casts = [
        'progreso_anterior' => 'decimal:2',
        'progreso_nuevo' => 'decimal:2',
        'monto_gastado' => 'decimal:2',
        'gasto_acumulado_anterior' => 'decimal:2',
        'gasto_acumulado_nuevo' => 'decimal:2',
        'variacion_presupuesto' => 'decimal:2',
        'variacion_presupuesto_porcentaje' => 'decimal:2',
        'indice_eficiencia' => 'decimal:2',
        'costo_por_unidad_trabajo' => 'decimal:2',
        'excede_presupuesto' => 'boolean',
        'esta_atrasado' => 'boolean',
        'nueva_fecha_inicio' => 'date',
        'nueva_fecha_fin' => 'date',
        'proxima_revision' => 'date',
        'fecha_revision' => 'datetime',
        'fecha_registro' => 'datetime',
        'archivos_adjuntos' => 'array',
        'imagenes' => 'array',
        'etiquetas' => 'array',
    ];

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    /**
     * Relación con el usuario que registró
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Relación con el usuario que revisó
     */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    /**
     * Scope para seguimientos que exceden presupuesto
     */
    public function scopeExcedePresupuesto($query)
    {
        return $query->where('excede_presupuesto', true);
    }

    /**
     * Scope para seguimientos atrasados
     */
    public function scopeAtrasados($query)
    {
        return $query->where('esta_atrasado', true);
    }

    /**
     * Scope para seguimientos por nivel de riesgo
     */
    public function scopePorNivelRiesgo($query, $nivel)
    {
        return $query->where('nivel_riesgo', $nivel);
    }

    /**
     * Accessor para el incremento de progreso
     */
    public function getIncrementoProgresoAttribute(): float
    {
        return $this->progreso_nuevo - $this->progreso_anterior;
    }

    /**
     * Accessor para el incremento de gasto
     */
    public function getIncrementoGastoAttribute(): float
    {
        return $this->gasto_acumulado_nuevo - $this->gasto_acumulado_anterior;
    }

    /**
     * Accessor para verificar si es eficiente
     */
    public function getEsEficienteAttribute(): bool
    {
        return $this->indice_eficiencia >= 1;
    }

    // Relaciones

    public function getCambioEstadoAttribute(): bool
    {
        return $this->estado_anterior !== $this->estado_nuevo;
    }

    public function getCambioFechasAttribute(): bool
    {
        return $this->nueva_fecha_inicio !== null || $this->nueva_fecha_fin !== null;
    }

    // Scopes
    public function scopeConAlertas($query)
    {
        return $query->where('excede_presupuesto', true)
            ->orWhere('esta_atrasado', true);
    }

    public function scopeUltimoMes($query)
    {
        return $query->where('fecha_registro', '>=', now()->subMonth());
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('registrado_por', $userId);
    }
}
