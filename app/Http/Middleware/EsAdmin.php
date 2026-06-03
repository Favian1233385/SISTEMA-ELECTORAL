<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificamos si el usuario está logueado
        if (!Auth::check()) {
            return redirect('login');
        }

        $role = Auth::user()->role;

        // RESTICCIÓN RIGUROSA: Solo el Súper Administrador general pasa a este bloque
        if ($role === 'admin' || $role === 'admin_general') {
            return $next($request);
        }

        // Si es provincial, cantonal, parroquial o digitador, se le deniega el acceso
        abort(403, 'Acceso denegado: Este módulo es exclusivo del Súper Administrador del sistema.');
    }
}