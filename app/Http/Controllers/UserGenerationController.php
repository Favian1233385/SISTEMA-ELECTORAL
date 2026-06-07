<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class UserGenerationController extends Controller
{
    /**
     * Genera usuarios digitadores basados en la división territorial y dignidad.
     */
    public function generarDigitadores(Request $request)
    {
        Log::info("Iniciando generación de digitadores en cascada", $request->all());

        $tipo = $request->input('tipo'); 
        $territorioId = $request->input('id');
        $dignidad = $request->input('dignidad');
        
        // 1. Capturamos el dato que viene del select de la vista
        $procesoRequest = $request->input('proceso_eleccion', 'generales'); 

        if (!$dignidad) {
            Log::warning("Generación abortada: No se recibió la dignidad.");
            return back()->with('error', 'Debe seleccionar una dignidad.');
        }

        // 2. Mapeo estricto conforme a la Base de Datos
        $procesoMesa = ($procesoRequest === 'primarias') ? 'primarias' : 'generales';
        $procesoUser = ($procesoRequest === 'primarias') ? 'primaria' : 'general';

        // Buscamos las mesas usando el campo de la tabla 'mesas' con todas sus relaciones
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
                if (!$mesa->recinto || !$mesa->recinto->parroquia || !$mesa->recinto->parroquia->canton) {
                    continue; 
                }

                $recinto = $mesa->recinto;
                $parroquia = $recinto->parroquia;
                $canton = $parroquia->canton;

                $dignidadSlug = Str::slug($dignidad);
                $generoLetra = strtolower(substr($mesa->genero, 0, 1));
                
                // CORRECCIÓN CRÍTICA: Añadimos IDs geográficos en cascada al email para garantizar unicidad provincial
                // Ejemplo: c1_p3_r14_m1-f-alcalde@sistema.com
                $suffix = ($procesoUser === 'primaria') ? '-primaria' : '';
                $username = "c" . $canton->id . "_p" . $parroquia->id . "_r" . $recinto->id . "_m" . $mesa->numero . "-" . $generoLetra . "-" . $dignidadSlug . $suffix;
                $email = $username . "@sistema.com";

                $tagProceso = ($procesoUser === 'primaria') ? ' (Primarias)' : '';

                // CORRECCIÓN CRÍTICA 2: Concatenamos el nombre del recinto en el campo 'name' para que se lea en las listas e impresiones
                $nombreRecintoCorto = Str::limit($recinto->nombre, 25, '...');
                $nombreDigitador = "Digitador " . ucfirst(strtolower($dignidad)) . " - " . $nombreRecintoCorto . " - M: " . $mesa->numero . " (" . substr($mesa->genero, 0, 3) . ")" . $tagProceso;

                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name'              => $nombreDigitador,
                        'password'          => Hash::make('voto2026'), 
                        'role'              => 'digitador',
                        'proceso_eleccion'  => $procesoMesa,
                        'tipo_proceso'      => $procesoUser,
                        'mesa_id'           => $mesa->id,
                        'dignidad_asignada' => $dignidad,
                        'provincia_id'      => $canton->provincia_id ?? $territorioId, 
                        'canton_id'         => $canton->id,
                        'parroquia_id'      => $parroquia->id,
                        'recinto_id'        => $recinto->id,
                    ]
                );
                $creados++;
            }

            DB::commit();
            return back()->with('success', "Se han generado exitosamente $creados usuarios digitadores para el territorio de tipo [$tipo] en el proceso de elecciones " . ($procesoUser === 'primaria' ? 'primarias.' : 'generales.'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en generación de usuarios", [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);
            return back()->with('error', 'Error en asignación masiva: ' . $e->getMessage());
        }
    }

    /**
     * Vista para listar e informar el aislamiento total de credenciales.
     */
    public function verDigitadores(Request $request)
    {
        $tipo = $request->query('tipo');
        $id = $request->query('id');
        $dignidad = $request->query('dignidad');
        
        // Recibimos de forma estricta el proceso desde el botón de la vista
        // Si por alguna razón no viene (alguien escribe la URL a mano), cae a 'generales' por defecto
        $procesoEleccion = $request->query('proceso_eleccion', 'generales');

        // Construimos la consulta base
        $query = User::where('role', 'digitador')
                    ->where('proceso_eleccion', $procesoEleccion) // Filtro matador por proceso
                    ->with(['mesa.recinto.parroquia.canton.provincia']);

        if ($tipo && $id) {
            $query->where($tipo . '_id', $id);
        }

        if ($dignidad) {
            $query->where('dignidad_asignada', $dignidad);
        }

        $digitadores = $query->orderBy('name', 'asc')->get();

        if ($request->has('pdf')) {
            return $this->exportarPDF($digitadores, $tipo, $id, $dignidad, $procesoEleccion);
        }

        return view('admin.digitadores.index', compact('digitadores', 'tipo', 'id', 'dignidad', 'procesoEleccion'));
    }

    private function exportarPDF($digitadores, $tipo, $id, $dignidad, $procesoEleccion)
    {
        if ($digitadores->isEmpty()) {
            return back()->with('error', 'No hay datos disponibles para generar el reporte PDF.');
        }

        $tituloReporte = "CREDENCIALES DE ACCESO - DIGITADORES (" . strtoupper($procesoEleccion) . ")";
        $subtitulo = "Territorio: " . ucfirst($tipo) . " (ID: $id) | Dignidad: " . ($dignidad ?? 'TODAS');

        $pdf = Pdf::loadView('admin.digitadores.pdf', compact('digitadores', 'tituloReporte', 'subtitulo'))
                  ->setPaper('a4', 'portrait')
                  ->setWarnings(false);

        $nombreArchivo = "credenciales_digitadores_" . $procesoEleccion . "_" . $tipo . ".pdf";
        return $pdf->stream($nombreArchivo);
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