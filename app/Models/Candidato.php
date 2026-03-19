<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidato extends Model
{
    use HasFactory;

    protected $fillable = [
        'partido_id',
        'dignidad',
        'nombre',
        'foto',
        'provincia_id',
        'canton_id',
        'parroquia_id'
    ];

    // Relación con el Partido (Ya la tenías, es correcta)
    public function partido()
    {
        return $this->belongsTo(Partido::class);
    }

    // ADICIÓN: Relación con Provincia
    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    // ADICIÓN: Relación con Cantón
    public function canton()
    {
        return $this->belongsTo(Canton::class);
    }

    // ADICIÓN: Relación con Parroquia (Útil para vocales parroquiales)
    public function parroquia()
    {
        return $this->belongsTo(Parroquia::class);
    }
    // Relación inversa: Un candidato aparece en muchas actas
    public function actas()
    {
        return $this->belongsToMany(Acta::class, 'acta_candidato')
                    ->withPivot('votos')
                    ->withTimestamps();
    }
}