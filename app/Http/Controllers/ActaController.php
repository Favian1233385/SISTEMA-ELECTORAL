<?php

namespace App\Http\Controllers;

use App\Models\{Acta, Candidato, Provincia, Canton, Parroquia, Recinto, Mesa, Voto};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Acta::with(['mesa.recinto.parroquia', 'usuario']);

        // SEGURIDAD: El digitador solo ve las actas de SU mesa asignada
        if ($user->esDigitador()) {
            $query->where('mesa_id', $user->mesa_id);
        } 
        // SEGURIDAD: El administrador provincial solo ve las actas de SU provincia
        elseif (!$user->esAdminGeneral() && $user->provincia_id) {
            $query->whereHas('mesa.recinto.parroquia.canton', function($q) use ($user) {
                $q->where('provincia_id', $user->provincia_id);
            });
        }

        $actas = $query->orderBy('created_at', 'desc')->get();
        return view('actas.index', compact('actas'));
    }

    public function create()
    {
        $user = auth()->user();
        
        // -----------------------------------------------------------------------------------------
        // BLINDAJE DE SEGURIDAD INTERNA: Bloqueo de acceso al panel para Digitadores con acta existente
        // -----------------------------------------------------------------------------------------
        if ($user->esDigitador() && $user->mesa_id) {
            $dignidadAsignada = $user->dignidad_asignada;
            
            $yaExisteActa = Acta::where('mesa_id', $user->mesa_id)
                ->where('dignidad', $dignidadAsignada)
                ->exists();

            if ($yaExisteActa) {
                return redirect()->route('dashboard')->with('error', 'Acceso denegado: Su acta asignada ya fue guardada con éxito en el sistema. El panel de digitación se encuentra bloqueado de forma definitiva.');
            }
        }
        // -----------------------------------------------------------------------------------------

        $provincias = $user->esAdminGeneral() 
            ? Provincia::orderBy('nombre')->get() 
            : Provincia::where('id', $user->provincia_id)->get();
        
        // CORRECCIÓN: Si es digitador, cargamos su mesa asignada con el total de electores
        $mesaAsignada = null;
        if ($user->esDigitador() && $user->mesa_id) {
            $mesaAsignada = Mesa::with('recinto.parroquia.canton.provincia')->find($user->mesa_id);
        }
        
        // Estructura de jurisdicción optimizada e inyección directa para automatización
        $jurisdiccion = [
            'esDigitador'       => $user->esDigitador(),
            'esAdminGeneral'    => $user->esAdminGeneral(),
            'esAdminProvincial' => !$user->esAdminGeneral() && !$user->esDigitador() && $user->provincia_id && !$user->canton_id,
            'provincia_id'      => ($user->esDigitador() && $mesaAsignada) ? $mesaAsignada->recinto->parroquia->canton->provincia_id : $user->provincia_id,
            'canton_id'         => ($user->esDigitador() && $mesaAsignada) ? $mesaAsignada->recinto->parroquia->canton_id : $user->canton_id,
            'parroquia_id'      => ($user->esDigitador() && $mesaAsignada) ? $mesaAsignada->recinto->parroquia_id : $user->parroquia_id,
            'recinto_id'        => ($user->esDigitador() && $mesaAsignada) ? $mesaAsignada->recinto_id : null,
            'mesa_id'           => $user->mesa_id, 
            'dignidad_asignada' => $user->dignidad_asignada 
        ];

        return view('actas.create', compact('provincias', 'user', 'jurisdiccion', 'mesaAsignada'));
    }

    public function show(Acta $acta)
    {
        $acta->load(['mesa.recinto.parroquia', 'candidatos.partido', 'usuario']);
        return view('actas.show', compact('acta'));
    }

    /**
     * MÉTODOS AJAX TOTALMENTE BLINDADOS
     */
    public function getCantones($provincia_id) {
        $user = auth()->user();
        
        // Si NO es administrador general, ignoramos lo que venga por la URL y forzamos su provincia
        if (!$user->esAdminGeneral()) {
            $provincia_id = $user->provincia_id;
        }

        if (!$provincia_id) {
            return response()->json([]);
        }

        $cantones = Canton::where('provincia_id', $provincia_id)
            ->orderBy('nombre')
            ->get();

        return response()->json($cantones);
    }

    public function getParroquias($canton_id) {
        $user = auth()->user();

        // SEGURIDAD: Validar que el cantón consultado pertenezca a la provincia del Admin Provincial
        if (!$user->esAdminGeneral()) {
            $cantonPertenece = Canton::where('id', $canton_id)
                ->where('provincia_id', $user->provincia_id)
                ->exists();
                
            if (!$cantonPertenece) {
                return response()->json([]); 
            }
        }

        return response()->json(Parroquia::where('canton_id', $canton_id)->orderBy('nombre')->get());
    }

    public function getRecintos($parroquia_id) {
        return response()->json(Recinto::where('parroquia_id', $parroquia_id)->orderBy('nombre')->get());
    }

    public function getMesas(Request $request, $recinto_id) {
        $user = auth()->user();
        $dignidad = $user->dignidad_asignada ?? $request->query('dignidad');
        $tipoProceso = $request->query('tipo_proceso', 'general');

        $mesas = Mesa::select('id', 'numero', 'genero', 'num_electores', 'recinto_id')
            ->where('recinto_id', $recinto_id)
            ->where('tipo_proceso', $tipoProceso) 
            ->withExists(['actas as completada' => function ($query) use ($dignidad) {
                $query->where('dignidad', $dignidad);
            }])
            ->orderBy('numero', 'asc')
            ->get();

        return response()->json($mesas);
    }

    public function getCandidatosFiltrados(Request $request)
    {
        try {
            $user = auth()->user();
            
            // LOG DE DIAGNÓSTICO: Guardamos qué datos están llegando desde el JavaScript
            Log::info('Iniciando carga de candidatos asignados', [
                'usuario_id' => $user->id,
                'dignidad_usuario' => $user->dignidad_asignada,
                'provincia_recibida' => $request->query('provincia_id'),
                'canton_recibido' => $request->query('canton_id'),
                'parroquia_recibida' => $request->query('parroquia_id')
            ]);

            // 1. CAPTURA Y LIMPIEZA DE DIGNIDAD
            $dignidadRaw = $user->dignidad_asignada ?? $request->query('dignidad', '');
            $dignidad = strtolower(strtr(utf8_decode($dignidadRaw), utf8_decode('áéíóúÁÉÍÓÚ'), 'aeiouAEIOU'));

            if (empty($dignidad)) {
                Log::warning('Se canceló la búsqueda: La dignidad del usuario está vacía.');
                return response()->json([]);
            }

            $provincia_id = $request->query('provincia_id');
            $canton_id    = $request->query('canton_id');
            $parroquia_id = $request->query('parroquia_id');

            $query = Candidato::with(['partido']);

            // 2. VERIFICACIÓN DEL PROCESO ELECTORAL ACTIVO
            $procesoActivo = \App\Models\ProcesoElectoral::where('estado', 'activo')->first();

            if (!$procesoActivo) {
                // Escribir la alerta en el log para el desarrollador
                Log::error('ERROR CRÍTICO: No se encontró ningún Proceso Electoral con estado = "activo" en la base de datos.');
                return response()->json(['error' => 'No hay proceso electoral activo.'], 500);
            }

            $query->where('proceso_electoral_id', $procesoActivo->id); 

            // 3. CONDICIONALES GEOGRÁFICAS SEGÚN ÁMBITO
            if (str_contains($dignidad, 'prefecto')) { 
                if (!$provincia_id) {
                    Log::warning('Falta provincia_id para dignidad provincial (Prefecto)');
                    return response()->json([]);
                }
                $query->where('provincia_id', '=', $provincia_id);
            } 
            elseif (str_contains($dignidad, 'alcalde') || str_contains($dignidad, 'concejal')) {
                if (!$canton_id) {
                    Log::warning('Falta canton_id para dignidad cantonal');
                    return response()->json([]);
                }
                $query->where('canton_id', '=', $canton_id);
            } 
            elseif (str_contains($dignidad, 'junta')) {
                if (!$parroquia_id) {
                    Log::warning('Falta parroquia_id para dignidad parroquial');
                    return response()->json([]);
                }
                $query->where('parroquia_id', '=', $parroquia_id);
            } else {
                Log::warning('La dignidad no coincide con ningún ámbito geográfico conocido: ' . $dignidadRaw);
                return response()->json([]);
            }
            
            // 4. ORDENAMIENTO NORMALIZADO (PRIMARIAS VS GENERALES)
            if ($procesoActivo->tipo === 'primarias') {
                // Orden alfabético por el nombre de la lista/partido utilizando una subconsulta limpia
                $query->orderBy(function($q) {
                    $q->select('nombre')
                      ->from('partidos')
                      ->whereColumn('partidos.id', 'candidatos.partido_id');
                }, 'asc');
            } else {
                // Orden numérico real convirtiendo el campo texto a entero para las generales
                $query->orderByRaw('(SELECT CAST(numero AS UNSIGNED) FROM partidos WHERE partidos.id = candidatos.partido_id) ASC');
            }

            $candidatos = $query->get();

            // LOG DE ÉXITO: Guardamos cuántos registros encontró la consulta SQL
            Log::info('Consulta ejecutada con éxito', ['cantidad_candidatos' => $candidatos->count()]);

            return response()->json($candidatos);

        } catch (\Exception $e) {
            // CAPTURA DE ERRORES DE SINTAXIS, CONEXIÓN O CAMPOS INEXISTENTES
            Log::error('ERROR GRAVE EN getCandidatosFiltrados: ' . $e->getMessage(), [
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Excepción interna del servidor.'], 500);
        }
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // 1. Flexibilidad de validación según el rol del usuario
        $isDigitadorConMesa = $user->esDigitador() && $user->mesa_id;

        $request->validate([
            'mesa_id'          => $isDigitadorConMesa ? 'nullable' : 'required|exists:mesas,id',
            'dignidad'         => $isDigitadorConMesa ? 'nullable' : 'required',
            'votos_blancos'    => 'required|integer|min:0',
            'votos_nulos'      => 'required|integer|min:0',
            'votos_candidatos' => 'required|array'
        ]);

        // 2. Obtener el proceso electoral activo
        $procesoActivo = \App\Models\ProcesoElectoral::where('estado', 'activo')->first();
        if (!$procesoActivo) {
            return back()->withErrors(['error' => 'No existe un proceso electoral activo en el sistema.']);
        }

        // 3. Resolver ID de la mesa
        $mesa_id_final = $isDigitadorConMesa ? $user->mesa_id : $request->mesa_id;
        $mesa = Mesa::find($mesa_id_final);
        
        if (!$mesa) {
            return back()->withErrors(['error' => 'La mesa seleccionada no es válida o no existe.']);
        }

        // 4. Resolver Dignidad
        $dignidad_final = $user->esDigitador() ? $user->dignidad_asignada : $request->dignidad;

        // 5. BLINDAJE DE SEGURIDAD EN BACKEND: Evitar duplicación enviando a la vista index con mensaje de error
        if (Acta::where('mesa_id', $mesa_id_final)->where('dignidad', $dignidad_final)->exists()) {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado: Su acta asignada ya fue guardada con éxito en el sistema. El panel de digitación se encuentra bloqueado de forma definitiva.');
        }

        // 6. Semáforo de validación amoldado a las opciones estrictas de tu ENUM
        $suma_votos_candidatos = array_sum($request->votos_candidatos);
        $total_votos_acta = $suma_votos_candidatos + $request->votos_blancos + $request->votos_nulos;
        $limite_electores = $mesa->num_electores;

        // Ajuste directo basado en: enum('ingresada','verificada','con_novedad')
        if ($total_votos_acta <= $limite_electores) {
            $estado_acta = 'ingresada'; // Cuadradas normales o con ausentismo entran limpias
        } else {
            $estado_acta = 'con_novedad'; // Supera el padrón (Inconsistente)
        }

        // 7. Persistencia atómica mediante Transacción SQL
        try {
            DB::transaction(function () use ($request, $user, $mesa_id_final, $dignidad_final, $estado_acta, $procesoActivo) {
                
                // Inserción mapeada uno a uno con los campos obligatorios (NO NULL) de tu esquema
                $acta = Acta::create([
                    'proceso_electoral_id' => $procesoActivo->id,
                    'mesa_id'              => $mesa_id_final,
                    'user_id'              => $user->id,
                    'dignidad'             => $dignidad_final,
                    'tipo_proceso'         => $procesoActivo->tipo ?? 'generales', // Toma el valor dinámico o hereda el default de la BD
                    'votos_blancos'        => $request->votos_blancos,
                    'votos_nulos'          => $request->votos_nulos,
                    'estado'               => $estado_acta,
                    'foto_path'            => null // Queda preparado si manejas subida de imágenes en el futuro
                ]);

                // Sincronización de votos en la tabla asociativa 'acta_candidato'
                $votosData = [];
                foreach ($request->votos_candidatos as $candidatoId => $votos) {
                    if ($votos !== null && $votos !== '') {
                        $votosData[$candidatoId] = [
                            'votos' => (int) $votos
                        ];
                    }
                }

                if (!empty($votosData)) {
                    $acta->candidatos()->attach($votosData);
                }
            });

            // Forzar limpieza de caché para actualizar reportes
            \Illuminate\Support\Facades\Artisan::call('cache:clear');

            return redirect()->route('actas.index')->with('success', "Acta guardada exitosamente en el sistema.");

        } catch (\Exception $e) {
            Log::error('Fallo estructural en guardado de acta: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error de consistencia SQL: ' . $e->getMessage()]);
        }
    }
}