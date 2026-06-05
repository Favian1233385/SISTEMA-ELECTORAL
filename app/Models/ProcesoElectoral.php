<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProcesoElectoral extends Model
{
    use HasFactory;

    protected $table = 'procesos_electorales';

    protected $fillable = [
        'nombre',
        'anio',
        'tipo',
        'estado',
    ];

    /**
     * Obtener los partidos políticos asignados a este proceso anual.
     */
    public function partidos()
    {
        return $this->hasMany(Partido::class, 'proceso_electoral_id');
    }

    /**
     * Obtener las actas procesadas en este proceso anual.
     */
    public function actas()
    {
        return $this->hasMany(Acta::class, 'proceso_electoral_id');
    }
}