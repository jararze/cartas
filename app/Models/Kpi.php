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
        'meta' => 'decimal:2',           // NUEVO
        'linea_base' => 'decimal:2',     // NUEVO
        'activo' => 'boolean',
        'mostrar_en_dashboard' => 'boolean',
        'proxima_medicion' => 'date',    // NUEVO,
        'ultima_medicion' => 'date',        // ← Agregar
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

        $valor = match ($this->codigo) {
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
        $gastoTotal = $carta->productos->sum(function ($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return $carta->monto_total > 0
            ? round(($gastoTotal / $carta->monto_total) * 100, 2)
            : 0;
    }

    private function calcularVariacionPresupuestal($carta): float
    {
        $presupuestoTotal = $carta->monto_total;
        $gastoTotal = $carta->productos->sum(function ($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return $gastoTotal - $presupuestoTotal;
    }

    private function calcularBurnRate($carta): float
    {
        $diasTranscurridos = now()->diffInDays($carta->fecha_inicio);
        if ($diasTranscurridos == 0) return 0;

        $gastoTotal = $carta->productos->sum(function ($producto) {
            return $producto->actividades->sum('gasto_acumulado');
        });

        return round($gastoTotal / $diasTranscurridos, 2);
    }

    private function calcularCPI($carta): float
    {
        $valorGanado = $this->calcularProgresoGeneral($carta) * $carta->monto_total / 100;
        $gastoReal = $carta->productos->sum(function ($producto) {
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
            ->filter(function ($actividad) {
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
            ->filter(function ($actividad) {
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

    // Relación con Producto (opcional)
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

// Relación con Actividad (opcional)
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    // Obtener días según frecuencia
    public function getDiasFrecuencia(): ?int
    {
        return match($this->frecuencia) {
            'diario' => 1,
            'semanal' => 7,
            'quincenal' => 15,
            'mensual' => 30,
            'trimestral' => 90,
            'semestral' => 180,
            'anual' => 365,
            'unico' => null,
            default => 30,
        };
    }

// Calcular próxima medición después de registrar valor
    public function actualizarProximaMedicion(): void
    {
        $dias = $this->getDiasFrecuencia();

        if ($dias) {
            $this->update([
                'proxima_medicion' => now()->addDays($dias)
            ]);
        }
    }

// Verificar si requiere medición
    public function getRequiereMedicionAttribute(): bool
    {
        if ($this->frecuencia === 'unico') {
            return $this->valores()->count() === 0;
        }

        return $this->proxima_medicion && $this->proxima_medicion->isPast();
    }

// Días hasta próxima medición (negativo si vencido)
    public function getDiasParaMedicionAttribute(): ?int
    {
        if (!$this->proxima_medicion) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->proxima_medicion, false);
    }

// Porcentaje de avance hacia la meta
    public function getPorcentajeMetaAttribute(): ?float
    {
        if (!$this->meta || $this->meta == 0) {
            return null;
        }

        $valorActual = $this->ultimoValor?->valor ?? $this->linea_base ?? 0;
        return round(($valorActual / $this->meta) * 100, 1);
    }

// Etiqueta de categoría formateada
    public function getCategoriaNombreAttribute(): string
    {
        return match($this->categoria) {
            'social' => 'Social',
            'productivo' => 'Productivo',
            'ambiental' => 'Ambiental',
            'economico' => 'Económico',
            'infraestructura' => 'Infraestructura',
            'capacitacion' => 'Capacitación',
            'calidad' => 'Calidad',
            'otro' => 'Otro',
            default => 'Sin categoría',
        };
    }

// Etiqueta de frecuencia formateada
    public function getFrecuenciaNombreAttribute(): string
    {
        return match($this->frecuencia) {
            'unico' => 'Única vez',
            'diario' => 'Diario',
            'semanal' => 'Semanal',
            'quincenal' => 'Quincenal',
            'mensual' => 'Mensual',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
            default => 'No definida',
        };
    }

// Nombre de asociación (Carta/Producto/Actividad)
    public function getAsociadoAAttribute(): string
    {
        if ($this->actividad_id) {
            return 'Actividad: ' . $this->actividad?->nombre;
        }

        if ($this->producto_id) {
            return 'Producto: ' . $this->producto?->nombre;
        }

        return 'Carta general';
    }
}
