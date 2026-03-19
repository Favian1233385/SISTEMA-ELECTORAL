<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurisdiccionConfig extends Model
{
    use HasFactory;

    // Nombre de la tabla (asegúrate de que coincida con tu migración)
    protected $table = 'jurisdiccion_configs';

    /**
     * Los atributos que se pueden asignar masivamente.
     * Esto soluciona el error MassAssignmentException.
     */
    protected $fillable = [
        'canton_id',
        'ver_provincia',
        'ver_parroquias',
    ];

    /**
     * Relación inversa: Una configuración pertenece a un Cantón.
     */
    public function canton()
    {
        return $this->belongsTo(Canton::class, 'canton_id');
    }
}