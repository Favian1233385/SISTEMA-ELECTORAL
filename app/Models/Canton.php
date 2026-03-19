<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Canton extends Model
{
    protected $table = 'cantones'; // Importante para evitar el error 'cantons'
    protected $fillable = ['nombre', 'provincia_id'];

    // Un cantón pertenece a una provincia
    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    // Un cantón tiene muchas parroquias
    public function parroquias()
    {
        return $this->hasMany(Parroquia::class);
    }

    public function configuracion()
    {
        // Esto conecta el Cantón con su configuración SaaS
        return $this->hasOne(JurisdiccionConfig::class, 'canton_id');
    }
}