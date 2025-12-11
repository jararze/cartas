<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionSeguimiento extends Model
{
    use HasFactory;

    protected $table = 'revisiones_seguimiento';

    protected $guarded = [];

    protected $casts = [
        'fecha_respuesta' => 'datetime',
    ];

    // Relaciones
    public function seguimiento(): BelongsTo
    {
        return $this->belongsTo(SeguimientoActividad::class, 'seguimiento_actividad_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por');
    }

    // Accessors
    public function getTipoColorAttribute(): string
    {
        return match($this->tipo) {
            'observacion' => 'blue',
            'solicitud' => 'yellow',
            'correccion' => 'orange',
            'aprobacion' => 'green',
            'rechazo' => 'red',
            default => 'gray'
        };
    }

    public function getTipoIconoAttribute(): string
    {
        return match($this->tipo) {
            'observacion' => '💬',
            'solicitud' => '📋',
            'correccion' => '✏️',
            'aprobacion' => '✅',
            'rechazo' => '❌',
            default => '📝'
        };
    }

    public function getTipoTextoAttribute(): string
    {
        return match($this->tipo) {
            'observacion' => 'Observación',
            'solicitud' => 'Solicitud',
            'correccion' => 'Corrección',
            'aprobacion' => 'Aprobación',
            'rechazo' => 'Rechazo',
            default => 'Revisión'
        };
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'pendiente' => 'bg-yellow-100 text-yellow-800',
            'atendido' => 'bg-green-100 text-green-800',
            'cerrado' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Métodos
    public function marcarAtendido(?string $respuesta = null, ?int $userId = null): void
    {
        $this->update([
            'estado' => 'atendido',
            'respuesta_proveedor' => $respuesta,
            'respondido_por' => $userId ?? auth()->id(),
            'fecha_respuesta' => now(),
        ]);
    }

    public function cerrar(): void
    {
        $this->update(['estado' => 'cerrado']);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
