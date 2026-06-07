<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acta extends Model
{
    use HasFactory;

    protected $fillable = [
        'mesa_id',
        'user_id',
        'dignidad',
        'votos_blancos',
        'votos_nulos',
        'foto_path',
        'estado',
        'tipo_proceso',
        'proceso_electoral_id',
    ];

    // Relación: Un acta pertenece a una Mesa
    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    // Relación con el Usuario/Digitador
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Por esto (Relación muchos a muchos):
    public function candidatos()
    {
        return $this->belongsToMany(Candidato::class, 'acta_candidato')
                    ->withPivot('votos') // Para poder sacar la cantidad de votos de la tabla intermedia
                    ->withTimestamps();
    }
    /**
     * Acceso indirecto a la ubicación (Ya que la tabla actas no tiene los IDs)
     * Esto permite hacer: $acta->recinto
     */
    public function recinto()
    {
        return $this->hasOneThrough(
            Recinto::class, // Modelo Destino
            Mesa::class,    // Modelo Intermedio
            'id',           // Llave foránea en la tabla 'mesas' que apunta a 'actas' (mesa_id)
            'id',           // Llave foránea en la tabla 'recintos' que apunta a 'mesas' (recinto_id)
            'mesa_id',      // Llave local en la tabla 'actas'
            'recinto_id'    // Llave local en la tabla 'mesas'
        );
    }
    public function procesoElectoral()
    {
        return $this->belongsTo(ProcesoElectoral::class, 'proceso_electoral_id');
    }
}