<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acta extends Model
{
    use HasFactory;

    protected $fillable = [
        'mesa_id',      // Existe en DB
        'user_id',      // Existe en DB
        'dignidad',     // Existe en DB
        'votos_blancos',// Existe en DB
        'votos_nulos',  // Existe en DB
        'foto_path',    // Existe en DB
        'estado'        // Existe en DB
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
        // El acta pertenece a una mesa, y la mesa pertenece a un recinto
        return $this->mesa->recinto(); 
    }
}