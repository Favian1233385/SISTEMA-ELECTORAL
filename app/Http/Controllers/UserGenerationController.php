<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserGenerationController extends Controller
{
    /**
     * Genera usuarios digitadores basados en la división territorial y dignidad.
     */
    public function generarDigitadores(Request $request)
    {
        Log::info("Iniciando generación de digitadores", $request->all());

        $tipo = $request->input('tipo'); 
        $territorioId = $request->input('id');
        $dignidad = $request->input('dignidad');
        
        // 1. Capturamos el dato que viene del select de la vista
        $procesoRequest = $request->input('proceso_eleccion', 'generales'); 

        if (!$dignidad) {
            Log::warning("Generación abortada: No se recibió la dignidad.");
            return back()->with('error', 'Debe seleccionar una dignidad.');
        }

        // 2. Mapeo estricto: Las MESAS usan 'proceso_eleccion' ('generales'/'primarias')
        // Los USUARIOS usan 'tipo_proceso' ('general'/'primaria') según tu MySQL Workbench
        $procesoMesa = ($procesoRequest === 'primarias') ? 'primarias' : 'generales';
        $procesoUser = ($procesoRequest === 'primarias') ? 'primaria' : 'general';

        // Buscamos las mesas usando el campo de la tabla 'mesas'
        $query = Mesa::where('proceso_eleccion', $procesoMesa)->with(['recinto.parroquia.canton.provincia']);

        if ($tipo == 'provincia') {
            $query->whereHas('recinto.parroquia.canton', function($q) use ($territorioId) {
                $q->where('provincia_id', $territorioId);
            });
        } elseif ($tipo == 'canton') {
            $query->whereHas('recinto.parroquia', function($q) use ($territorioId) {
                $q->where('canton_id', $territorioId);
            });
        } elseif ($tipo == 'parroquia') {
            $query->whereHas('recinto', function($q) use ($territorioId) {
                $q->where('parroquia_id', $territorioId);
            });
        }

        $mesas = $query->get();

        Log::info("Búsqueda de mesas completada para proceso [{$procesoMesa}]", [
            'tipo' => $tipo,
            'id' => $territorioId,
            'cantidad_mesas_encontradas' => $mesas->count()
        ]);

        if ($mesas->isEmpty()) {
            return back()->with('error', "No se encontraron mesas de tipo [{$procesoMesa}] para este territorio.");
        }

        DB::beginTransaction();
        try {
            $creados = 0;
            foreach ($mesas as $mesa) {
                if (!$mesa->recinto || !$mesa->recinto->parroquia) {
                    continue; 
                }

                $dignidadSlug = Str::slug($dignidad);
                $generoLetra = strtolower(substr($mesa->genero, 0, 1));
                
                // Si es primaria, el email será: m001-m-alcalde-primaria@sistema.com
                $suffix = ($procesoUser === 'primaria') ? '-primaria' : '';
                $username = "m" . $mesa->numero . "-" . $generoLetra . "-" . $dignidadSlug . $suffix;
                $email = $username . "@sistema.com";

                $tagProceso = ($procesoUser === 'primaria') ? ' (Primarias)' : '';

                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'              => "Digitador " . ucfirst(strtolower($dignidad)) . " - Mesa " . $mesa->numero . " (" . $mesa->genero . ")" . $tagProceso,
                        'password'          => Hash::make('voto2026'), 
                        'role'              => 'digitador',
                        'tipo_proceso'      => $procesoUser, // Usa exactamente el campo de tu fillable y BD
                        'mesa_id'           => $mesa->id,
                        'dignidad_asignada' => $dignidad,
                        'provincia_id'      => $mesa->recinto->parroquia->canton->provincia_id ?? null,
                        'canton_id'         => $mesa->recinto->parroquia->canton_id ?? null,
                        'parroquia_id'      => $mesa->recinto->parroquia_id ?? null,
                        'recinto_id'        => $mesa->recinto_id,
                    ]
                );
                $creados++;
            }

            DB::commit();
            return back()->with('success', "Se han generado $creados usuarios digitadores para el proceso de elecciones " . ($procesoUser === 'primaria' ? 'primarias.' : 'generales.'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en generación de usuarios", [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);
            return back()->with('error', 'Error en asignación: ' . $e->getMessage());
        }
    }

    /**
     * Vista para listar e imprimir las credenciales.
     */
    public function verDigitadores(Request $request)
    {
        $tipo = $request->query('tipo');
        $id = $request->query('id');
        $dignidad = $request->query('dignidad');
        $procesoEleccion = $request->query('proceso_eleccion'); 

        $query = User::where('role', 'digitador')->with(['mesa.recinto.parroquia.canton']);

        if ($tipo && $id) {
            $query->where($tipo . '_id', $id);
        }

        if ($dignidad) {
            $query->where('dignidad_asignada', $dignidad);
        }

        if ($procesoEleccion) {
            // Se adapta dinámicamente si el filtro busca en la tabla users
            $procesoUser = ($procesoEleccion === 'primarias') ? 'primaria' : 'general';
            $query->where('tipo_proceso', $procesoUser);
        }

        $digitadores = $query->orderBy('name', 'asc')->get();

        if ($request->has('pdf')) {
            return $this->exportarPDF($digitadores, $tipo, $id, $dignidad);
        }

        return view('admin.digitadores.index', compact('digitadores', 'tipo', 'id', 'dignidad'));
    }

    private function exportarPDF($digitadores, $tipo, $id, $dignidad)
    {
        return "Generando PDF de " . ($dignidad ?? 'Todas las Dignidades') . " para el territorio $tipo ($id).";
    }

    public function limpiarDigitadores()
    {
        try {
            $total = User::where('role', 'digitador')->count();
            User::where('role', 'digitador')->delete();
            return back()->with('success', "Se han eliminado $total usuarios digitadores del sistema.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al intentar vaciar la tabla: ' . $e->getMessage());
        }
    }
}