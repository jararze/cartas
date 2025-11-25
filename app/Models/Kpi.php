<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kpi extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'campos_calculo' => 'array',
        'umbral_min' => 'decimal:2',
        'umbral_max' => 'decimal:2',
        'activo' => 'boolean',
        'mostrar_en_dashboard' => 'boolean',
    ];

    // Relaciones
    public function carta(): BelongsTo
    {
        return $this->belongsTo(Carta::class);
    }

    public function valores(): HasMany
    {
        return $this->hasMany(KpiValor::class)->orderBy('fecha_calculo', 'desc');
    }

    public function ultimoValor()
    {
        return $this->hasOne(KpiValor::class)->latestOfMany('fecha_calculo');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePredefinidos($query)
    {
        return $query->where('tipo', 'predefinido');
    }

    public function scopePersonalizados($query)
    {
        return $query->where('tipo', 'personalizado');
    }

    public function scopeEnDashboard($query)
    {
        return $query->where('mostrar_en_dashboard', true);
    }

    // Métodos de cálculo
    public function calcularValor(): KpiValor
    {
        $carta = $this->carta;
        $valorAnterior = $this->valores()->first()?->valor;

        $valor = match($this->codigo) {
            'ejecucion_presupuestal' => $this->calcularEjecucionPresupuestal($carta),
            'variacion_presupuestal' => $this->calcularVariacionPresupuestal($carta),
            'burn_rate' => $this->calcularBurnRate($carta),
            'cpi' => $this->calcularCPI($carta),
            'tiempo_transcurrido' => $this->calcularTiempoTranscurrido($carta),
            'spi' => $this->calcularSPI($carta),
            'dias_retraso' => $this->calcularDiasRetraso($carta),
            'progreso_general' => $this->calcularProgresoGeneral($carta),
            'actividades_completadas' => $this->calcularActividadesCompletadas($carta),
            'productividad' => $this->calcularProductividad($carta),
            'actividades_riesgo' => $this->calcularActividadesEnRiesgo($carta),
            'sobrepresupuestos' => $this->calcularSobrepresupuestos($carta),
            'actividades_atrasadas' => $this->calcularActividadesAtrasadas($carta),
            default => $this->calcularPersonalizado(),
        };

        // Determinar tendencia
        $tendencia = null;
        $porcentajeCambio = null;
        if ($valorAnterior !== null && $valorAnterior != 0) {
            $cambio = $valor - $valorAnterior;
            $porcentajeCambio = round(($cambio / $valorAnterior) * 100, 2);

            if (abs($porcentajeCambio) < 1) {
                $tendencia = 'estable';
            } elseif ($cambio > 0) {
                $tendencia = 'subiendo';
            } else {
                $tendencia = 'bajando';
            }
        }

        // Verificar alertas
        $enAlerta = false;
        $tipoAlerta = 'normal';

        if ($this->tipo_umbral === 'mayor_mejor' && $this->umbral_min !== null && $valor < $this->umbral_min) {
            $enAlerta = true;
            $tipoAlerta = $valor < ($this->umbral_min * 0.8) ? 'critica' : 'advertencia';
        } elseif ($this->tipo_umbral === 'menor_mejor' && $this->umbral_max !== null && $valor > $this->umbral_max) {
            $enAlerta = true;
            $tipoAlerta = $valor > ($this->umbral_max * 1.2) ? 'critica' : 'advertencia';
        } elseif ($this->tipo_umbral === 'rango') {
            if (($this->umbral_min !== null && $valor < $this->umbral_min) ||
                ($this->umbral_max !== null && $valor > $this->umbral_max)) {
                $enAlerta = true;
                $tipoAlerta = 'advertencia';
            }
        }

        return $this->valores()->create([
            'valor' => $valor,
            'valor_anterior' => $valorAnterior,
            'tendencia' => $tendencia,
            'porcentaje_cambio' => $porcentajeCambio,
            'en_alerta' => $enAlerta,
            'tipo_alerta' => $tipoAlerta,
            'calculado_por' => auth()->id(),
            'fecha_calculo' => now(),
        ]);
    }

    // KPIs Financieros
    private function calcularEjecucionPresupuestal($carta): float
    {
        $gastoTotal = $carta->productos->sum(function($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return $carta->monto_total > 0
            ? round(($gastoTotal / $carta->monto_total) * 100, 2)
            : 0;
    }

    private function calcularVariacionPresupuestal($carta): float
    {
        $presupuestoTotal = $carta->monto_total;
        $gastoTotal = $carta->productos->sum(function($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return $gastoTotal - $presupuestoTotal;
    }

    private function calcularBurnRate($carta): float
    {
        $diasTranscurridos = now()->diffInDays($carta->fecha_inicio);
        if ($diasTranscurridos == 0) return 0;

        $gastoTotal = $carta->productos->sum(function($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return round($gastoTotal / $diasTranscurridos, 2);
    }

    private function calcularCPI($carta): float
    {
        $valorGanado = $this->calcularProgresoGeneral($carta) * $carta->monto_total / 100;
        $gastoReal = $carta->productos->sum(function($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return $gastoReal > 0 ? round($valorGanado / $gastoReal, 2) : 0;
    }

    // KPIs de Tiempo
    private function calcularTiempoTranscurrido($carta): float
    {
        $duracionTotal = $carta->fecha_inicio->diffInDays($carta->fecha_fin);
        $diasTranscurridos = $carta->fecha_inicio->diffInDays(now());

        return $duracionTotal > 0
            ? round(($diasTranscurridos / $duracionTotal) * 100, 2)
            : 0;
    }

    private function calcularSPI($carta): float
    {
        $tiempoTranscurrido = $this->calcularTiempoTranscurrido($carta);
        $progresoReal = $this->calcularProgresoGeneral($carta);

        return $tiempoTranscurrido > 0
            ? round($progresoReal / $tiempoTranscurrido, 2)
            : 0;
    }

    private function calcularDiasRetraso($carta): int
    {
        $progresoEsperado = $this->calcularTiempoTranscurrido($carta);
        $progresoReal = $this->calcularProgresoGeneral($carta);

        if ($progresoReal >= $progresoEsperado) return 0;

        $duracionTotal = $carta->fecha_inicio->diffInDays($carta->fecha_fin);
        $diferenciaProgreso = $progresoEsperado - $progresoReal;

        return round(($diferenciaProgreso / 100) * $duracionTotal);
    }

    // KPIs de Progreso
    private function calcularProgresoGeneral($carta): float
    {
        $actividades = $carta->productos->flatMap->actividades;
        return $actividades->count() > 0
            ? round($actividades->avg('progreso'), 2)
            : 0;
    }

    private function calcularActividadesCompletadas($carta): int
    {
        return $carta->productos->flatMap->actividades->where('progreso', '>=', 100)->count();
    }

    private function calcularProductividad($carta): float
    {
        $diasTranscurridos = max(1, now()->diffInDays($carta->fecha_inicio));
        $progresoTotal = $this->calcularProgresoGeneral($carta);

        return round($progresoTotal / $diasTranscurridos, 2);
    }

    // KPIs de Riesgo
    private function calcularActividadesEnRiesgo($carta): int
    {
        return $carta->productos->flatMap->actividades
            ->filter(function($actividad) {
                return $actividad->esta_atrasado || $actividad->excede_presupuesto;
            })->count();
    }

    private function calcularSobrepresupuestos($carta): int
    {
        return $carta->productos->flatMap->actividades
            ->where('gasto_acumulado', '>', 'monto')
            ->count();
    }

    private function calcularActividadesAtrasadas($carta): int
    {
        return $carta->productos->flatMap->actividades
            ->filter(function($actividad) {
                return $actividad->fecha_fin < now() && $actividad->progreso < 100;
            })
            ->count();
    }

    private function calcularPersonalizado(): float
    {
        // Lógica para KPIs personalizados basada en $this->formula y $this->campos_calculo
        // Por implementar según necesidades específicas
        return 0;
    }
}
