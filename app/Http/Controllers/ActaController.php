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
        
        // Si es admin nacional ve todas, si es provincial u otro, solo la suya
        $provincias = $user->esAdminGeneral() ? Provincia::orderBy('nombre')->get() : Provincia::where('id', $user->provincia_id)->get();
        
        $jurisdiccion = [
            'esDigitador'       => $user->esDigitador(),
            'esAdminGeneral'    => $user->esAdminGeneral(),
            'esAdminProvincial' => !$user->esAdminGeneral() && !$user->esDigitador() && $user->provincia_id && !$user->canton_id,
            'provincia_id'      => $user->provincia_id,
            'canton_id'         => $user->canton_id,
            'parroquia_id'      => $user->parroquia_id,
            'mesa_id'           => $user->mesa_id, 
            'dignidad_asignada' => $user->dignidad_asignada 
        ];

        return view('actas.create', compact('provincias', 'user', 'jurisdiccion'));
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
        $user = \App\Models\User::find(auth()->id());
        if (!$user) return response()->json([]);

        $dignidadRaw = $user->esAdminGeneral() ? $request->query('dignidad') : $user->dignidad_asignada;
        if (!$dignidadRaw) return response()->json([]);
        $dignidad = strtoupper(trim($dignidadRaw));

        $query = Candidato::with('partido')->where('dignidad', $dignidad);

        // DETERMINACIÓN DE VARIABLES TERRITORIALES (Dinámicas para Admin, Fijas para Digitador)
        $provincia_id = $user->esAdminGeneral() ? $request->query('provincia_id') : $user->provincia_id;
        $canton_id = ($user->esAdminGeneral() || !$user->canton_id) ? $request->query('canton_id') : $user->canton_id;
        $parroquia_id = ($user->esAdminGeneral() || !$user->parroquia_id) ? $request->query('parroquia_id') : $user->parroquia_id;

        if (str_contains($dignidad, 'PREFECTO')) {
            $query->where('provincia_id', '=', $provincia_id);
        } 
        elseif (str_contains($dignidad, 'ALCALDE') || str_contains($dignidad, 'CONCEJAL')) {
            if ($canton_id) {
                $query->where('canton_id', '=', $canton_id);
            } else {
                return response()->json([]);
            }
        } 
        elseif (str_contains($dignidad, 'JUNTA')) {
            if ($parroquia_id) {
                $query->where('parroquia_id', '=', $parroquia_id);
            } else {
                return response()->json([]);
            }
        }

        $candidatos = $query->orderBy('nombre', 'asc')->get();
        return response()->json($candidatos);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'mesa_id' => 'required|exists:mesas,id',
            'dignidad' => 'required',
            'votos_blancos' => 'required|integer|min:0',
            'votos_nulos' => 'required|integer|min:0',
            'votos_candidatos' => 'required|array'
        ]);

        $mesa_id_final = ($user->esDigitador() && $user->mesa_id) ? $user->mesa_id : $request->mesa_id;
        $mesa = Mesa::find($mesa_id_final);
        
        if (!$mesa) {
            return back()->withErrors(['error' => 'La mesa seleccionada no es válida o no existe.']);
        }

        $dignidad_final = $user->esAdminGeneral() ? $request->dignidad : ($user->dignidad_asignada ?? $request->dignidad);

        if (Acta::where('mesa_id', $mesa_id_final)->where('dignidad', $dignidad_final)->exists()) {
            return back()->withErrors(['error' => 'Esta acta ya ha sido ingresada anteriormente para esta mesa y dignidad.']);
        }

        // --- CÁLCULO MATEMÁTICO DE CUADRE (SEMÁFORO BACKEND) ---
        $suma_votos_candidatos = array_sum($request->votos_candidatos);
        $total_votos_acta = $suma_votos_candidatos + $request->votos_blancos + $request->votos_nulos;
        $limite_electores = $mesa->num_electores;

        // Reglas de negocio para asignación automática del estado
        if ($total_votos_acta == $limite_electores) {
            $estado_acta = 'cuadrada';
        } elseif ($total_votos_acta < $limite_electores) {
            $estado_acta = 'cuadrada_con_ausentismo';
        } else {
            $estado_acta = 'inconsistente'; // Semáforo Rojo automático
        }

        DB::transaction(function () use ($request, $user, $mesa_id_final, $dignidad_final, $estado_acta) {
            $acta = Acta::create([
                'mesa_id'       => $mesa_id_final,
                'dignidad'      => $dignidad_final,
                'votos_blancos' => $request->votos_blancos,
                'votos_nulos'   => $request->votos_nulos,
                'user_id'       => $user->id, 
                'estado'        => $estado_acta // Almacenamos el estado real del cuadre
            ]);

            $votosData = [];
            foreach ($request->votos_candidatos as $id => $votos) {
                if ($votos !== null) {
                    $votosData[$id] = ['votos' => $votos];
                }
            }

            $acta->candidatos()->attach($votosData);
        });

        return redirect()->route('actas.index')->with('success', "Acta procesada correctamente. Estado: Up-Stream ({$estado_acta})");
    }
}