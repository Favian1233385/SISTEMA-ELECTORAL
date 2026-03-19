<?php

namespace App\Http\Controllers;

use App\Models\Canton;
use App\Models\JurisdiccionConfig;
use Illuminate\Http\Request;

class JurisdiccionConfigController extends Controller
{
    public function index()
    {
        // Traemos todos los cantones con su configuración (si existe)
        $cantones = Canton::with('configuracion')->get();
        return view('admin.config_jurisdicciones', compact('cantones'));
    }

    public function update(Request $request, $canton_id)
    {
        // Usamos updateOrCreate para asegurar que si no existe, lo cree, y si existe, lo actualice
        \App\Models\JurisdiccionConfig::updateOrCreate(
            ['canton_id' => $canton_id],
            [
                'ver_provincia' => $request->ver_provincia,
                'ver_parroquias' => $request->ver_parroquias,
            ]
        );

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}