<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EsAdmin
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
        // 1. Verificación de autenticación básica
        if (!Auth::check()) {
            return redirect('login');
        }

        // 2. Restricción rigurosa basada en el modelo User
        // Llama al método esAdminGeneral() que valida estrictamente el rol 'admin'
        if (Auth::user()->esAdminGeneral()) {
            return $next($request);
        }

        // 3. Denegación absoluta para administradores territoriales o digitadores
        abort(403, 'Acceso denegado: Este módulo es exclusivo del Súper Administrador del sistema.');
    }
}