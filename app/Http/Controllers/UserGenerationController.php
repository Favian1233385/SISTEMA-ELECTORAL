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
        
        // CORRECCIÓN: Estandarizamos a MAYÚSCULAS desde el inicio para evitar fallos de coincidencia
        $dignidad = $request->input('dignidad') ? strtoupper(trim($request->input('dignidad'))) : null;
        $procesoRequest = $request->input('proceso_eleccion', 'generales'); 

        if (!$dignidad) {
            Log::warning("Generación abortada: No se recibió la dignidad.");
            return back()->with('error', 'Debe seleccionar una dignidad.');
        }

        $procesoMesa = ($procesoRequest === 'primarias') ? 'primarias' : 'generales';
        $procesoUser = ($procesoRequest === 'primarias') ? 'primaria' : 'general';

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
                
                $suffix = ($procesoUser === 'primaria') ? '-primaria' : '';
                $username = "c" . $canton->id . "_p" . $parroquia->id . "_r" . $recinto->id . "_m" . $mesa->numero . "-" . $generoLetra . "-" . $dignidadSlug . $suffix;
                $email = $username . "@sistema.com";

                $tagProceso = ($procesoUser === 'primaria') ? ' (Primarias)' : '';
                $nombreRecintoCorto = Str::limit($recinto->nombre, 25, '...');
                
                // Formateamos el nombre estéticamente pero la columna conserva el valor en Mayúsculas puras
                $nombreDigitador = "Digitador " . ucfirst(strtolower($dignidad)) . " - " . $nombreRecintoCorto . " - M: " . $mesa->numero . " (" . substr($mesa->genero, 0, 3) . ")" . $tagProceso;

                // AJUSTE QUIRÚRGICO: Garantizar asignación correcta de jerarquía territorial sin importar la dignidad elegida
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
                        // Se extrae la provincia directamente de la estructura real de la mesa procesada
                        'provincia_id'      => $canton->provincia_id, 
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
     * REEMPLAZAR ESTE MÉTODO COMPLETO
     */
    public function verDigitadores(Request $request)

    {
        $tipo = $request->query('tipo');

        $id = $request->query('id');

        $procesoEleccion = $request->query('proceso_eleccion', 'generales');
        // Estandarizamos la captura en MAYÚSCULAS

        $dignidad = $request->query('dignidad') ? strtoupper(trim($request->query('dignidad'))) : null;

        $query = User::where('role', 'digitador')

                    ->where('proceso_eleccion', $procesoEleccion)

                    ->with(['mesa.recinto.parroquia.canton.provincia']);
        // LOGICA DE FILTRADO INTELIGENTE
        if ($tipo && $id) {
            if ($tipo === 'parroquia') {
                // Busca de ambas formas para asegurar que encuentre los 2 digitadores sin importar la estructura
                $query->where(function($q) use ($id) {
                    $q->where('parroquia_id', $id)
                      ->orWhereHas('mesa.recinto', function($subQ) use ($id) {
                          $subQ->where('parroquia_id', $id);
                      });
                });
            } else {
                // Para cantón y provincia el mapeo directo sigue siendo limpio y funcional
                $query->where($tipo . '_id', $id);
            }
        }
        if ($dignidad) {
            $query->where('dignidad_asignada', $dignidad);
        }
        // Auditar que el cruce de territorio y dignidad funcione
        Log::info("Consulta adaptativa de digitadores ejecutada", [
            'alcance_territorio' => $tipo,
            'territorio_id'      => $id,
            'dignidad_filtrada'  => $dignidad ?? 'TODAS',
            'proceso'            => $procesoEleccion
        ]);

        $digitadores = $query->orderBy('name', 'asc')->get();
        if ($request->has('pdf')) {
            return $this->exportarPDF($digitadores, $tipo, $id, $dignidad, $procesoEleccion);
        }
        return view('admin.digitadores.index', compact('digitadores', 'tipo', 'id', 'dignidad', 'procesoEleccion'));
    }

    public function limpiarDigitadores(Request $request)
    {
        Log::info("Iniciando depuración quirúrgica de digitadores", $request->all());

        $tipo = $request->input('tipo'); 
        $territorioId = $request->input('id');
        // Forzamos conversión a mayúsculas para que coincida con el valor guardado
        $dignidad = $request->input('dignidad') ? strtoupper($request->input('dignidad')) : null;
        $procesoRequest = $request->input('proceso_eleccion', 'generales');

        $procesoMesa = ($procesoRequest === 'primarias') ? 'primarias' : 'generales';

        try {
            $query = User::where('role', 'digitador')
                         ->where('proceso_eleccion', $procesoMesa);

            if ($tipo && $territorioId) {
                $query->where($tipo . '_id', $territorioId);
            }

            // CORRECCIÓN: Filtro limpio por dignidad en mayúsculas
            if ($dignidad) {
                $query->where('dignidad_asignada', $dignidad);
            }

            $totalAEliminar = $query->count();

            if ($totalAEliminar === 0) {
                return back()->with('error', "No se encontraron usuarios digitadores registrados para los filtros seleccionados.");
            }

            $query->delete();

            Log::info("Depuración completada exitosamente.", [
                'proceso' => $procesoMesa,
                'territorio_tipo' => $tipo,
                'territorio_id' => $territorioId,
                'dignidad' => $dignidad ?? 'TODAS',
                'cantidad_eliminados' => $totalAEliminar
            ]);

            $mensajeExito = "Se han eliminado con éxito $totalAEliminar usuarios digitadores asignados a la dignidad de [" . ($dignidad ?? 'TODAS') . "] en el territorio seleccionado para el proceso de elecciones " . ($procesoMesa === 'primarias' ? 'primarias.' : 'generales.');

            return back()->with('success', $mensajeExito);

        } catch (\Exception $e) {
            Log::error("Error crítico en la limpieza de digitadores", [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);
            return back()->with('error', 'Error al intentar depurar los usuarios: ' . $e->getMessage());
        }
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
}
