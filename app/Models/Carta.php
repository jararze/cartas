<?php
// app/Models/Carta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kpi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carta extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'productos_requeridos' => 'array',
        'archivos_adjuntos' => 'array',
        'servicios_requeridos' => 'array',
        'indicadores_kpi' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_envio' => 'datetime',
        'fecha_vista' => 'datetime',
        'fecha_respuesta' => 'datetime',
        'monto_total' => 'decimal:2',
    ];

    // Relaciones
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    // Relación con Proveedor
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    // Relación con Productos
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    // Relación con Colaboradores
    public function colaboradores(): HasMany
    {
        return $this->hasMany(ColaboradorCarta::class);
    }

    public function responsableFao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_fao_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Métodos calculados
    public function getPresupuestoCalculadoAttribute(): float
    {
        return $this->productos->sum('presupuesto_total');
    }

    public function getGastoTotalAttribute(): float
    {
        return $this->productos->sum('gasto_total');
    }


    public function getTotalActividadesAttribute(): int
    {
        return $this->productos->sum(function($producto) {
            return $producto->actividades->count();
        });
    }

    public function getActividadesCompletadasAttribute(): int
    {
        return $this->productos->sum(function($producto) {
            return $producto->actividades->where('progreso', 100)->count();
        });
    }

    public function getTieneAlertasAttribute(): bool
    {
        return $this->productos->some('tiene_actividades_atrasadas') ||
            $this->productos->some('tiene_exceso_presupuesto');
    }

    // Scopes
    public function scopeConTodo($query)
    {
        return $query->with([
            'productos.actividades.seguimientos',
            'colaboradores',
            'responsableFao'
        ]);
    }

    public function scopeEnProgreso($query)
    {
        return $query->where('estado', 'en_progreso');
    }

    public function scopeAtrasadas($query)
    {
        return $query->where('fecha_fin', '<', now())
            ->whereNotIn('estado', ['finalizada', 'cancelada']);
    }

    // Métodos helper
    public function getEstadoBadgeClassAttribute(): string
    {
        return match($this->estado) {
            'borrador' => 'bg-gray-100 text-gray-800',
            'enviada' => 'bg-blue-100 text-blue-800',
            'vista' => 'bg-yellow-100 text-yellow-800',
            'aceptada' => 'bg-green-100 text-green-800',
            'rechazada' => 'bg-red-100 text-red-800',
            'en_ejecucion' => 'bg-purple-100 text-purple-800',
            'finalizada' => 'bg-emerald-100 text-emerald-800',
            'cancelada' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'borrador' => 'Borrador',
            'enviada' => 'Enviada',
            'vista' => 'Vista',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            'en_ejecucion' => 'En Ejecución',
            'finalizada' => 'Finalizada',
            'cancelada' => 'Cancelada',
            default => 'Desconocido',
        };
    }

    public function getDuracionMesesAttribute(): int
    {
        return $this->fecha_inicio && $this->fecha_fin
            ? $this->fecha_inicio->diffInMonths($this->fecha_fin)
            : 0;
    }

    public static function generarCodigo(): string
    {
        $year = date('Y');
        $ultimaCarta = static::where('codigo', 'like', "CARTA-{$year}-%")
            ->orderBy('codigo', 'desc')
            ->first();

        if ($ultimaCarta) {
            $ultimoNumero = (int) substr($ultimaCarta->codigo, -3);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return sprintf('CARTA-%s-%03d', $year, $nuevoNumero);
    }

    // Scopes
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorUsuario($query, $userId)
    {
        return $query->where('creado_por', $userId);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor para obtener la URL pública del documento
    public function getDocumentUrlAttribute()
    {
        if ($this->document_path && file_exists($this->document_path)) {
            $filename = basename($this->document_path);
            return asset('storage/invitations/' . $filename);
        }
        return null;
    }

    // Método para verificar si tiene documento
    public function hasDocument()
    {
        return $this->document_path && file_exists($this->document_path);
    }

    /**
     * Obtener todas las actividades de la carta a través de productos
     */
    public function actividades()
    {
        return $this->hasManyThrough(Actividad::class, Producto::class);
    }

    /**
     * Calcular progreso general real
     */
    public function getProgresoGeneralAttribute(): int
    {
        $actividades = $this->actividades()->get();

        if ($actividades->isEmpty()) {
            return 0;
        }

        $totalProgreso = $actividades->sum('progreso');
        return (int) round($totalProgreso / $actividades->count());
    }

    /**
     * Contar actividades activas (en_curso)
     */
    public function getActividadesActivasAttribute(): int
    {
        return $this->actividades()
            ->where('estado', 'en_curso')
            ->count();
    }

    /**
     * Contar actividades atrasadas
     */
    public function getActividadesAtrasadasAttribute(): int
    {
        return $this->actividades()
            ->where(function($query) {
                $query->where('estado', 'atrasado')
                    ->orWhere(function($q) {
                        $q->where('fecha_fin', '<', now())
                            ->whereNotIn('estado', ['finalizado', 'cancelado'])
                            ->where('progreso', '<', 100);
                    });
            })
            ->count();
    }

    /**
     * Contar total de colaboradores aceptados
     */
    public function getTotalColaboradoresAttribute(): int
    {
        return $this->colaboradores()
            ->where('estado', 'aceptado')
            ->count();
    }

    /**
     * Obtener colaboradores para mostrar (máximo 3)
     */
    public function getColaboradoresParaMostrarAttribute()
    {
        return $this->colaboradores()
            ->where('estado', 'aceptado')
            ->take(3)
            ->get();
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(Kpi::class);
    }
}
