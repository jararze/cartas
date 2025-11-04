<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected  $guarded = [];

    protected $casts = ['filtros' => 'array', 'configuracion' => 'array', 'publico' => 'boolean', 'ultima_generacion' => 'datetime'];

    public function creador() {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function generaciones() {
        return $this->hasMany(ReporteGenerado::class);
    }
}
