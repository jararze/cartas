<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Desembolso extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_aprobado' => 'decimal:2',
        'desglose_lineas' => 'array',
        'fecha_solicitud' => 'datetime',
        'fecha_proceso' => 'datetime',
        'fecha_pago' => 'datetime',
    ];

    // Estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_PAGADO = 'pagado';
    const ESTADO_RECHAZADO = 'rechazado';

    public static function estados(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_EN_PROCESO => 'En Proceso',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_RECHAZADO => 'Rechazado',
        ];
    }

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function carta(): BelongsTo
    {
        return $this->belongsTo(Carta::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function procesador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procesado_por');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeEnProceso($query)
    {
        return $query->where('estado', self::ESTADO_EN_PROCESO);
    }

    public function scopePagados($query)
    {
        return $query->where('estado', self::ESTADO_PAGADO);
    }

    // Métodos
    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            self::ESTADO_PENDIENTE => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>',
            self::ESTADO_EN_PROCESO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">En Proceso</span>',
            self::ESTADO_PAGADO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Pagado</span>',
            self::ESTADO_RECHAZADO => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rechazado</span>',
            default => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Desconocido</span>',
        };
    }

    public function marcarEnProceso(int $userId): void
    {
        $this->update([
            'estado' => self::ESTADO_EN_PROCESO,
            'procesado_por' => $userId,
            'fecha_proceso' => now(),
        ]);
    }

    public function marcarPagado(array $datosPago): void
    {
        $this->update([
            'estado' => self::ESTADO_PAGADO,
            'monto_aprobado' => $datosPago['monto_aprobado'] ?? $this->monto_total,
            'numero_transferencia' => $datosPago['numero_transferencia'] ?? null,
            'banco' => $datosPago['banco'] ?? null,
            'cuenta_destino' => $datosPago['cuenta_destino'] ?? null,
            'comprobante_path' => $datosPago['comprobante_path'] ?? null,
            'observaciones' => $datosPago['observaciones'] ?? null,
            'fecha_pago' => now(),
        ]);

        // Actualizar estado del producto
        $this->producto->update(['estado' => 'desembolsado']);
    }

    public function rechazar(string $motivo, int $userId): void
    {
        $this->update([
            'estado' => self::ESTADO_RECHAZADO,
            'motivo_rechazo' => $motivo,
            'procesado_por' => $userId,
            'fecha_proceso' => now(),
        ]);
    }
}
