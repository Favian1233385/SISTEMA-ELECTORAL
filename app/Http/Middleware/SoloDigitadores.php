<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SoloDigitadores
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario NO está logueado o NO tiene el rol de digitador
        if (!auth()->check() || !auth()->user()->esDigitador()) {
            // Abortamos con un error 403 (Prohibido) por ética y seguridad
            abort(403, 'Acceso denegado: Esta función es exclusiva para digitadores acreditados.');
        }

        return $next($request);
    }
}
