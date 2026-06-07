<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recinto extends Model
{
    use HasFactory;

    protected $table = 'recintos'; // Forzamos nombre en español

    // CORRECCIÓN: Se agrega 'proceso_electoral_id' al fillable para permitir la carga masiva
    protected $fillable = [
        'nombre',
        'parroquia_id',
        'direccion',
        'proceso_electoral_id' 
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

    // NUEVA RELACIÓN: Un recinto pertenece a un proceso electoral específico
    public function procesoElectoral()
    {
        return $this->belongsTo(ProcesoElectoral::class, 'proceso_electoral_id');
    }
}