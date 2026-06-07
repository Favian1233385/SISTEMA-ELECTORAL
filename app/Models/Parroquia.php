<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parroquia extends Model
{
    protected $table = 'parroquias';
    
    // CORRECCIÓN: Se agrega 'proceso_electoral_id' para permitir su inserción en masa
    protected $fillable = ['nombre', 'canton_id', 'proceso_electoral_id'];

    public function canton()
    {
        return $this->belongsTo(Canton::class);
    }

    // Una parroquia tiene muchos recintos
    public function recintos()
    {
        return $this->hasMany(Recinto::class);
    }

    // NUEVA RELACIÓN: Una parroquia pertenece a un proceso electoral
    public function procesoElectoral()
    {
        return $this->belongsTo(ProcesoElectoral::class, 'proceso_electoral_id');
    }
}