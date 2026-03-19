<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acta extends Model
{
    use HasFactory;

    protected $fillable = [
        'mesa_id',
        'dignidad',
        'votos_blancos', // Sincronizado con la migración (con S)
        'votos_nulos',
        'user_id',       // Sincronizado con la migración (user_id, no usuario_id)
        'foto_path',
        'estado'
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
}