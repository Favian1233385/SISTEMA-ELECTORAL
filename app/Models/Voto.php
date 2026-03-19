<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// Heredar de Pivot es más correcto para tablas intermedias
class Voto extends Pivot 
{
    // 1. El nombre real de tu tabla migrada
    protected $table = 'acta_candidato'; 

    // 2. Los campos reales de tu migración
    protected $fillable = [
        'acta_id', 
        'candidato_id', 
        'votos' // Cambiado de conteo_votos a votos
    ];

    public function acta()
    {
        return $this->belongsTo(Acta::class);
    }

    public function candidato()
    {
        return $this->belongsTo(Candidato::class);
    }
}