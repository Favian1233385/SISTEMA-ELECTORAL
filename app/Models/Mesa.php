<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    use HasFactory;

    protected $table = 'mesas'; // Evita que Laravel busque 'mesas' (aunque en este caso coincidiría, es mejor declararlo)

    protected $fillable = [
        'numero', // Ejemplo: "001", "002"
        'genero', // Masculino o Femenino (según el padrón electoral de Ecuador)
        'recinto_id',
        'num_electores', // Útil para validar que los votos no superen el total de la mesa
        'estado' // Estado de la mesa: Habilitada o Deshabilitada
    ];

    // Una mesa pertenece a un recinto
    public function recinto()
    {
        return $this->belongsTo(Recinto::class);
    }

    // Relación con los votos/actas (que crearemos en el siguiente módulo)
    public function votos()
    {
        return $this->hasMany(Voto::class);
    }
}