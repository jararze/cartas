<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValor extends Model
{
    use HasFactory;

    protected $table = 'kpi_valores';

    protected $guarded = [];

    protected $casts = [
        'valor' => 'decimal:2',
        'valor_anterior' => 'decimal:2',
        'porcentaje_cambio' => 'decimal:2',
        'datos_calculo' => 'array',
        'en_alerta' => 'boolean',
        'fecha_calculo' => 'datetime',
        'evidencia_path' => 'string',  // NUEVO
    ];

    // Relaciones
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function calculadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculado_por');
    }

    // Scopes
    public function scopeEnAlerta($query)
    {
        return $query->where('en_alerta', true);
    }

    public function scopeUltimoMes($query)
    {
        return $query->where('fecha_calculo', '>=', now()->subMonth());
    }

    // Accessors
    public function getIconoTendenciaAttribute(): string
    {
        return match($this->tendencia) {
            'subiendo' => '↑',
            'bajando' => '↓',
            'estable' => '→',
            default => '·',
        };
    }

    public function getColorTendenciaAttribute(): string
    {
        $esMejoraMejorando = $this->kpi->tipo_umbral === 'mayor_mejor' && $this->tendencia === 'subiendo';
        $esMejoraBajando = $this->kpi->tipo_umbral === 'menor_mejor' && $this->tendencia === 'bajando';

        if ($esMejoraMejorando || $esMejoraBajando) {
            return 'text-green-600';
        } elseif ($this->tendencia === 'estable') {
            return 'text-gray-600';
        } else {
            return 'text-red-600';
        }
    }

    // Formato del cambio para mostrar (+5.2%, -3.1%, etc.)
    public function getCambioFormateadoAttribute(): string
    {
        if ($this->porcentaje_cambio === null) {
            return '-';
        }

        $signo = $this->porcentaje_cambio >= 0 ? '+' : '';
        return $signo . number_format($this->porcentaje_cambio, 1) . '%';
    }

// Fecha formateada para mostrar
    public function getFechaFormateadaAttribute(): string
    {
        return $this->fecha_calculo?->format('d/m/Y') ?? '-';
    }

// Nombre del responsable
    public function getResponsableNombreAttribute(): string
    {
        return $this->calculadoPor?->name ?? 'Sistema';
    }
}
