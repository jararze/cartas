<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ColaboradorCarta extends Model
{
    use HasFactory;

    protected $table = 'colaborador_cartas';

    protected $fillable = [
        'carta_id', 'email', 'telefono', 'nombre', 'rol', 'estado',
        'mensaje_invitacion', 'token_invitacion', 'invitado_en',
        'respondido_en', 'invitado_por', 'permisos'
    ];

    protected $casts = [
        'invitado_en' => 'datetime',
        'respondido_en' => 'datetime',
        'permisos' => 'array',
    ];

    // Relaciones
    public function carta(): BelongsTo
    {
        return $this->belongsTo(Carta::class);
    }

    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    // Métodos
    public static function boot()
    {
        parent::boot();

        static::creating(function ($colaborador) {
            if (!$colaborador->token_invitacion) {
                $colaborador->token_invitacion = Str::random(32);
            }
            if (!$colaborador->invitado_en) {
                $colaborador->invitado_en = now();
            }
        });
    }

    public function generarNuevoToken(): string
    {
        $this->token_invitacion = Str::random(32);
        $this->save();
        return $this->token_invitacion;
    }

    public function aceptarInvitacion(string $nombre): void
    {
        $this->update([
            'nombre' => $nombre,
            'estado' => 'aceptado',
            'respondido_en' => now(),
        ]);
    }

    public function rechazarInvitacion(): void
    {
        $this->update([
            'estado' => 'rechazado',
            'respondido_en' => now(),
        ]);
    }

    // Scopes
    public function scopeAceptados($query)
    {
        return $query->where('estado', 'aceptado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'invitado');
    }

    public function scopePorRol($query, $rol)
    {
        return $query->where('rol', $rol);
    }
}
