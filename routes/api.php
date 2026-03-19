<?php

use App\Models\Canton;
use App\Models\Parroquia;
use App\Models\Recinto;
use App\Models\Mesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Ruta para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * RUTAS PARA SELECTORES DINÁMICOS
 * Estas rutas alimentan el flujo: Provincia -> Cantón -> Parroquia -> Recinto -> Mesa
 */

// 1. Obtener Cantones por Provincia
Route::get('/cantones/{provincia_id}', function ($provincia_id) {
    return Canton::where('provincia_id', $provincia_id)
                ->select('id', 'nombre')
                ->orderBy('nombre', 'asc')
                ->get();
});

// 2. Obtener Parroquias por Cantón
Route::get('/parroquias/{canton_id}', function ($canton_id) {
    return Parroquia::where('canton_id', $canton_id)
                    ->select('id', 'nombre')
                    ->orderBy('nombre', 'asc')
                    ->get();
});

// 3. Obtener Recintos por Parroquia
Route::get('/recintos/{parroquia_id}', function ($parroquia_id) {
    return Recinto::where('parroquia_id', $parroquia_id)
                    ->select('id', 'nombre')
                    ->orderBy('nombre', 'asc')
                    ->get();
});

// 4. Obtener Mesas por Recinto con validación de Acta
Route::get('/mesas/{recinto_id}', function (Request $request, $recinto_id) {
    $dignidad = $request->query('dignidad'); 

    $mesas = Mesa::where('recinto_id', $recinto_id)
                ->select('id', 'numero', 'genero')
                ->orderBy('numero', 'asc')
                ->get();

    return $mesas->map(function($mesa) use ($dignidad) {
        // Solo verificamos si realmente recibimos una dignidad
        $existe = false;
        if ($dignidad) {
            $existe = \App\Models\Acta::where('mesa_id', $mesa->id)
                                    ->where('dignidad', $dignidad)
                                    ->exists();
        }
        
        return [
            'id' => $mesa->id,
            'numero' => $mesa->numero,
            'genero' => $mesa->genero,
            'completada' => $existe 
        ];
    });
});

// 5. Obtener Candidatos dinámicamente por Dignidad
// Esta ruta permitirá cargar fotos y nombres automáticamente al cambiar el select de dignidad
Route::get('/candidatos/{dignidad}', [App\Http\Controllers\CandidatoController::class, 'getByDignidad']);