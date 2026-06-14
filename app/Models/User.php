<?php

namespace App\Models;

use App\Models\Provincia;
use App\Models\Canton;
use App\Models\Parroquia;
use App\Models\Recinto;
use App\Models\Mesa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash; // <-- Esencial para la encriptación

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users'; // Forzamos explícitamente el nombre de la tabla

    protected $fillable = [
        'name',
        'email',
        'password',
        'password_plain',
        'role',              
        'proceso_eleccion',     
        'dignidad_asignada', 
        'provincia_id',    // <-- Sincronizado con tu BD real
        'canton_id',       // <-- Sincronizado con tu BD real
        'parroquia_id',    // <-- Sincronizado con tu BD real
        'recinto_id',      // <-- Sincronizado con tu BD real
        'mesa_id',         // <-- Sincronizado con tu BD real
        'ver_prefectos', 
        'ver_nivel_superior', // <-- Agregado (tinyint en tu BD)
        'ver_nivel_inferior', // <-- Agregado (tinyint en tu BD)
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'ver_prefectos' => 'boolean',
        'ver_nivel_superior' => 'boolean', // Mapeado como booleano para Laravel
        'ver_nivel_inferior' => 'boolean', // Mapeado como booleano para Laravel
        'password_plain' => 'string',
    ];

    /**
     * MUTADOR AUTOMÁTICO DE CONTRASEÑA
     * Encripta el texto plano del PDF de manera transparente antes de guardarlo.
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            // Si ya viene encriptado (por ejemplo, empieza con $2y$), lo guarda directo. Si no, genera el hash.
            $this->attributes['password'] = str_starts_with($value, '$2y$') ? $value : Hash::make($value);
        }
    }

    // --- MÉTODOS DE LÓGICA DE ROLES ---

    public function esAdmin()
    {
        return in_array($this->role, ['admin', 'admin_provincial', 'admin_cantonal', 'admin_parroquial']);
    }

    public function esAdminGeneral()
    {
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
        if ($this->esAdminGeneral()) return true;
        return $this->dignidad_asignada === $dignidad;
    }

    /**
     * Retorna el nombre del lugar que administra para mostrar en el perfil electoral
     */
    public function ubicacionAsignada()
    {
        if ($this->mesa_id) return "Mesa #" . ($this->mesa?->numero ?? 'S/N');
        if ($this->recinto_id) return $this->recinto?->nombre ?? 'Recinto';
        if ($this->parroquia_id) return $this->parroquia?->nombre ?? 'Parroquia';
        if ($this->canton_id) return $this->canton?->nombre ?? 'Cantón';
        if ($this->provincia_id) return $this->provincia?->nombre ?? 'Provincia';
        return 'Acceso Total';
    }

    // --- RELACIONES TERRITORIALES CORREGIDAS ---
    public function provincia() { return $this->belongsTo(Provincia::class, 'provincia_id'); }
    public function canton() { return $this->belongsTo(Canton::class, 'canton_id'); }
    public function parroquia() { return $this->belongsTo(Parroquia::class, 'parroquia_id'); }
    public function recinto() { return $this->belongsTo(Recinto::class, 'recinto_id'); }
    public function mesa() { return $this->belongsTo(Mesa::class, 'mesa_id'); }
}