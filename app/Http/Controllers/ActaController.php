<?php

namespace App\Http\Controllers;

use App\Models\{Acta, Candidato, Provincia, Canton, Parroquia, Recinto, Mesa, Voto};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Acta::with(['mesa.recinto.parroquia', 'usuario']);

        // SEGURIDAD: El digitador solo ve lo que él mismo ha ingresado
        if ($user->esDigitador()) {
            $query->where('user_id', $user->id);
        }

        $actas = $query->orderBy('created_at', 'desc')->get();
        return view('actas.index', compact('actas'));
    }

    public function create()
    {
        $user = auth()->user();
        $provincias = Provincia::orderBy('nombre')->get();
        
        $jurisdiccion = [
            'esDigitador' => $user->esDigitador(),
            'provincia_id' => $user->provincia_id,
            'canton_id'    => $user->canton_id,
            'parroquia_id' => $user->parroquia_id,
            'recinto_id'   => $user->recinto_id,
            'mesa_id'      => $user->mesa_id,
            'dignidad_asignada' => $user->dignidad_asignada // Nuevo: Para bloquear la vista
        ];

        return view('actas.create', compact('provincias', 'user', 'jurisdiccion'));
    }

    public function show(Acta $acta)
    {
        $acta->load(['mesa.recinto.parroquia', 'candidatos.partido', 'usuario']);
        return view('actas.show', compact('acta'));
    }

    /**
     * MÉTODOS AJAX PARA DINAMISMO TERRITORIAL
     */
    public function getCantones($provincia_id) {
        return response()->json(Canton::where('provincia_id', $provincia_id)->orderBy('nombre')->get());
    }

    public function getParroquias($canton_id) {
        return response()->json(Parroquia::where('canton_id', $canton_id)->orderBy('nombre')->get());
    }

    public function getRecintos($parroquia_id) {
        return response()->json(Recinto::where('parroquia_id', $parroquia_id)->orderBy('nombre')->get());
    }

    public function getMesas(Request $request, $recinto_id) {
        $user = auth()->user();
        // Si el usuario tiene dignidad fija, esa es la que manda para marcar "completada"
        $dignidad = $user->dignidad_asignada ?? $request->query('dignidad');

        $mesas = Mesa::where('recinto_id', $recinto_id)->get();
        $mesas->map(function ($mesa) use ($dignidad) {
            $mesa->completada = Acta::where('mesa_id', $mesa->id)
                                    ->where('dignidad', $dignidad)
                                    ->exists();
            return $mesa;
        });

        return response()->json($mesas);
    }

    public function getCandidatosFiltrados(Request $request)
    {
        $user = auth()->user();
        // SEGURIDAD: Solo permitimos pedir candidatos de la dignidad asignada al usuario
        $dignidad = $user->esAdminGeneral() ? $request->query('dignidad') : $user->dignidad_asignada;
        $canton_id = $request->query('canton_id');
        $parroquia_id = $request->query('parroquia_id');

        if (!$dignidad) return response()->json([]);

        $query = Candidato::with('partido')->where('dignidad', $dignidad);

        if (in_array($dignidad, ['alcalde', 'concejal'])) {
            $query->where('canton_id', $canton_id);
        } elseif ($dignidad == 'junta_parroquial') {
            $query->where('parroquia_id', $parroquia_id);
        }

        return response()->json($query->get());
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

        // --- VALIDACIONES DE SEGURIDAD ---
        // 1. Bloqueo por Dignidad
        if (!$user->esAdminGeneral() && $user->dignidad_asignada !== $request->dignidad) {
            return back()->withErrors(['error' => 'No autorizado para esta dignidad.']);
        }

        // 2. Bloqueo por Territorio
        if ($user->esDigitador()) {
            $mesa = Mesa::find($request->mesa_id);
            if ($user->mesa_id && $mesa->id != $user->mesa_id) return back()->withErrors(['error' => 'Mesa incorrecta.']);
            if ($user->recinto_id && $mesa->recinto_id != $user->recinto_id) return back()->withErrors(['error' => 'Recinto ajeno.']);
        }

        // 3. Duplicados
        if (Acta::where('mesa_id', $request->mesa_id)->where('dignidad', $request->dignidad)->exists()) {
            return back()->withErrors(['error' => 'Acta ya ingresada anteriormente.']);
        }

        DB::transaction(function () use ($request, $user) {
            $acta = Acta::create([
                'mesa_id'       => $request->mesa_id,
                'dignidad'      => $request->dignidad,
                'votos_blancos' => $request->votos_blancos,
                'votos_nulos'   => $request->votos_nulos,
                'user_id'       => $user->id, 
                'estado'        => 'ingresada'
            ]);

            foreach ($request->votos_candidatos as $id => $votos) {
                if ($votos !== null) {
                    $acta->candidatos()->attach($id, ['votos' => $votos]);
                }
            }
        });

        return redirect()->route('actas.index')->with('success', 'Acta guardada correctamente.');
    }
}