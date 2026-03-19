<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recinto extends Model
{
    use HasFactory;

    protected $table = 'recintos'; // Forzamos nombre en español

    protected $fillable = [
        'nombre',
        'parroquia_id',
        'direccion' // Opcional, pero recomendado para sistemas electorales reales
    ];

    // Relación inversa: Un recinto pertenece a una parroquia
    public function parroquia()
    {
        return $this->belongsTo(Parroquia::class);
    }

    // Un recinto contiene muchas mesas (Juntas Receptoras del Voto)
    public function mesas()
    {
        return $this->hasMany(Mesa::class);
    }
}