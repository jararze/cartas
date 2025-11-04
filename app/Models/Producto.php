<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'carta_id', 'nombre', 'descripcion', 'presupuesto',
        'fecha_inicio', 'fecha_fin', 'indicadores_kpi', 'orden', 'estado'
    ];

    protected $casts = [
        'presupuesto' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'indicadores_kpi' => 'array',
    ];

    // Relaciones
    public function carta(): BelongsTo
    {
        return $this->belongsTo(Carta::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class)->orderBy('fecha_inicio');
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
}
