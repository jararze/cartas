<?php
// app/Models/Proveedor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $guarded = [];

    protected $casts = [
        'especialidades' => 'array',
        'activo' => 'boolean'
    ];

    // Relación con Usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cartas(): HasMany
    {
        return $this->hasMany(Carta::class, 'proveedor_email', 'email');
    }

    public function getFormattedPhoneAttribute(): string
    {
        return $this->whatsapp ?: $this->telefono ?: 'No disponible';
    }
}
