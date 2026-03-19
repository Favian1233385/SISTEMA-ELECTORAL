<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parroquia extends Model
{
    protected $table = 'parroquias';
    protected $fillable = ['nombre', 'canton_id'];

    public function canton()
    {
        return $this->belongsTo(Canton::class);
    }

    // Una parroquia tiene muchos recintos
    public function recintos()
    {
        return $this->hasMany(Recinto::class);
    }
}