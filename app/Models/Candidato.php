<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Candidato extends Model
{
    use HasFactory;

    protected $table = 'candidatos';

    protected $fillable = [
        'partido_id',
        'dignidad',
        'nombre',
        'tipo_proceso',
        'foto', // Campo real en tu Base de Datos
        'provincia_id',
        'canton_id',
        'parroquia_id',
        'proceso_electoral_id',
    ];

    /**
     * ACCESOR: Permite que el JavaScript lea de forma transparente 'imagen_url'.
     * Si el candidato tiene una foto guardada en el storage la retorna, 
     * de lo contrario devuelve el avatar por defecto.
     */
    public function getImagenUrlAttribute()
    {
        if ($this->foto && (str_starts_with($this->foto, 'http://') || str_starts_with($this->foto, 'https://'))) {
            return $this->foto;
        }

        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return Storage::url($this->foto);
        }

        return asset('img/default-avatar.png');
    }

    /**
     * Asegura que el atributo dinámico se incluya siempre que el controlador
     * transforme este modelo a arreglos o respuestas JSON para AJAX.
     */
    protected $appends = ['imagen_url'];


    // Relación con el Partido (Utilizado con .with('partido') en tu controlador)
    public function partido()
    {
        return $this->belongsTo(Partido::class, 'partido_id');
    }

    // Relación con Provincia
    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    // Relación con Cantón
    public function canton()
    {
        return $this->belongsTo(Canton::class, 'canton_id');
    }

    // Relación con Parroquia (Esencial para el filtro de vocales parroquiales de tu controlador)
    public function parroquia()
    {
        return $this->belongsTo(Parroquia::class, 'parroquia_id');
    }

    // Relación inversa: Un candidato aparece en muchas actas (Tabla intermedia acta_candidato)
    public function actas()
    {
        return $this->belongsToMany(Acta::class, 'acta_candidato')
                    ->withPivot('votos')
                    ->withTimestamps();
    }

    // Relación con el Periodo Electoral al que pertenece la postulación
    public function procesoElectoral()
    {
        return $this->belongsTo(ProcesoElectoral::class, 'proceso_electoral_id');
    }
}