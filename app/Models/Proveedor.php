<?php
// app/Models/Proveedor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'whatsapp',
        'empresa',
        'contacto_principal',
        'cargo',
        'especialidades',
        'notas',
        'activo'
    ];

    protected $casts = [
        'especialidades' => 'array',
        'activo' => 'boolean'
    ];

    public function cartas(): HasMany
    {
        return $this->hasMany(Carta::class, 'proveedor_email', 'email');
    }

    public function getFormattedPhoneAttribute(): string
    {
        return $this->whatsapp ?: $this->telefono ?: 'No disponible';
    }
}
