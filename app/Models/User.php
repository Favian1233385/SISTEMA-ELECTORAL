<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',              // admin, admin_provincial, admin_cantonal, admin_parroquial, digitador
        'dignidad_asignada', // prefecto, alcalde, concejal, junta_parroquial
        'provincia_id',
        'canton_id',
        'parroquia_id',
        'recinto_id',    
        'mesa_id',       
        'ver_prefectos', 
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'ver_prefectos' => 'boolean',
    ];

    // --- MÉTODOS DE LÓGICA DE ROLES CORREGIDOS ---

    public function esAdmin()
    {
        return in_array($this->role, ['admin', 'admin_provincial', 'admin_cantonal', 'admin_parroquial']);
    }

    public function esAdminGeneral()
    {
        // El SuperAdmin es admin y punto, no importa su ubicación
        return $this->role === 'admin';
    }

    public function esAdminProvincial()
    {
        return $this->role === 'admin_provincial';
    }

    public function esAdminCantonal()
    {
        return $this->role === 'admin_cantonal';
    }

    public function esAdminParroquial()
    {
        return $this->role === 'admin_parroquial';
    }

    public function esDigitador()
    {
        return $this->role === 'digitador';
    }

    // --- MÉTODOS DE FILTRADO POR DIGNIDAD ---

    public function puedeDigitar($dignidad)
    {
        // Un admin general puede todo, un digitador solo su dignidad asignada
        if ($this->esAdminGeneral()) return true;
        return $this->dignidad_asignada === $dignidad;
    }

    /**
     * Retorna el nombre del lugar que administra para mostrar en el perfil
     */
    public function ubicacionAsignada()
    {
        if ($this->mesa_id) return "Mesa #" . ($this->mesa->numero ?? 'S/N');
        if ($this->recinto_id) return $this->recinto->nombre ?? 'Recinto';
        if ($this->parroquia_id) return $this->parroquia->nombre ?? 'Parroquia';
        if ($this->canton_id) return $this->canton->nombre ?? 'Cantón';
        if ($this->provincia_id) return $this->provincia->nombre ?? 'Provincia';
        return 'Acceso Total';
    }

    // --- RELACIONES TERRITORIALES ---
    public function provincia() { return $this->belongsTo(Provincia::class); }
    public function canton() { return $this->belongsTo(Canton::class); }
    public function parroquia() { return $this->belongsTo(Parroquia::class); }
    public function recinto() { return $this->belongsTo(Recinto::class); }
    public function mesa() { return $this->belongsTo(Mesa::class); }
}