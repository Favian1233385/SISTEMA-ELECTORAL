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

        // PERMITIMOS PASAR a los tres niveles de mando
        if ($role === 'admin' || $role === 'admin_provincial' || $role === 'admin_cantonal') {
            return $next($request);
        }

        // Si es digitador u otro, le mandamos el error que viste en la captura
        abort(403, 'No tienes permiso para gestionar usuarios.');
    }
}