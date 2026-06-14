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
        'ausentismo',
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
   
    public function recinto()
    {
        return $this->hasOneThrough(
            Recinto::class, // Modelo Destino
            Mesa::class,    // Modelo Intermedio
            'id',           // 3. Llave primaria en la tabla intermedia (mesas.id)
            'id',           // 4. Llave primaria en la tabla final (recintos.id)
            'mesa_id',      // 5. Llave foránea en la tabla local (actas.mesa_id)
            'recinto_id'    // 6. Llave foránea en la tabla intermedia (mesas.recinto_id)
        );
    }
    public function procesoElectoral()
    {
        return $this->belongsTo(ProcesoElectoral::class, 'proceso_electoral_id');
    }
}