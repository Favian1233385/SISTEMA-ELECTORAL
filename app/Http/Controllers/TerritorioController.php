<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Acta;
use App\Models\Provincia; // Añadir
use App\Models\Canton;    // Añadir
use App\Models\Parroquia; // Añadir
use App\Models\Recinto;   // Añadir
use App\Models\Mesa;      // Añadir
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request; // Añadir para los formularios

class TerritorioController extends Controller
{
   public function dashboard()
    {
        $auth = auth()->user();

        // 1. Definir el query base de Actas según el rango del usuario
        $actasQuery = Acta::query();

        // --- LÓGICA DE FILTRADO TERRITORIAL (SaaS) ---
        if ($auth->esAdminCantonal() && !$auth->esAdminGeneral()) {
            // Filtramos las actas para que solo cuente las de SU cantón
            $actasQuery->whereHas('mesa.recinto.parroquia', function($q) use ($auth) {
                $q->where('canton_id', $auth->canton_id);
            });
        }

        // 2. Obtener IDs de actas filtradas para los resultados de candidatos
        $actasIds = $actasQuery->pluck('id');

        // 3. Obtener candidatos con la suma de votos SOLO de las actas permitidas
        $resultados = Candidato::with('partido')
            ->withSum(['actas as total_votos' => function($query) use ($actasIds) {
                $query->whereIn('acta_id', $actasIds);
            }], 'acta_candidato.votos')
            ->orderBy('total_votos', 'desc')
            ->get();

        // --- LÓGICA DE PREFECTOS ---
        // Si no tiene permiso de ver prefectos, filtramos los candidatos de esa dignidad
        if (!$auth->ver_prefectos && !$auth->esAdminGeneral()) {
            $resultados = $resultados->filter(function($candidato) {
                return strtolower($candidato->dignidad) !== 'prefecto';
            });
        }

        // 4. Totales generales filtrados para las tarjetas (KPIs)
        $totalActas = $actasQuery->count();
        $totalVotosBlancos = $actasQuery->sum('votos_blancos');
        $totalVotosNulos = $actasQuery->sum('votos_nulos');
        
        $sumaVotosCandidatos = $resultados->sum('total_votos');
        $granTotalVotos = $sumaVotosCandidatos + $totalVotosBlancos + $totalVotosNulos;

        return view('dashboard', compact(
            'resultados', 
            'totalActas', 
            'totalVotosBlancos', 
            'totalVotosNulos', 
            'granTotalVotos'
        ));
    }

    public function resultadosPublicos()
    {
        // Los resultados públicos suelen mostrar la tendencia PROVINCIAL total.
        // Pero si prefieres que también se filtren por la ubicación del usuario logueado,
        // puedes copiar la lógica del dashboard aquí. 
        // Por ahora, lo mantenemos como la "Gran Verdad Provincial":
        
        $resultados = Candidato::with('partido')
            ->withSum('actas as total_votos', 'acta_candidato.votos')
            ->orderBy('total_votos', 'desc')
            ->get();

        $totalActas = Acta::count();
        $totalVotosBlancos = Acta::sum('votos_blancos');
        $totalVotosNulos = Acta::sum('votos_nulos');
        
        $sumaVotosCandidatos = $resultados->sum('total_votos');
        $granTotalVotos = $sumaVotosCandidatos + $totalVotosBlancos + $totalVotosNulos;

        return view('resultados', compact('resultados', 'totalActas', 'totalVotosBlancos', 'totalVotosNulos', 'granTotalVotos'));
    }

    public function gestionarDivision(Request $request)
    {
        // 1. PRIORIDAD MÁXIMA: Si seleccionó un RECINTO, mostramos sus MESAS
        if ($request->has('recinto')) {
            $recinto = \App\Models\Recinto::with('parroquia.canton.provincia')->findOrFail($request->recinto);
            $mesas = \App\Models\Mesa::where('recinto_id', $recinto->id)->get();
            return view('territorios.mesas', compact('recinto', 'mesas'));
        }

        // 2. Si seleccionó una PARROQUIA, mostramos sus RECINTOS
        if ($request->has('parroquia')) {
            $parroquia = \App\Models\Parroquia::with('canton.provincia')->findOrFail($request->parroquia);
            $recintos = \App\Models\Recinto::where('parroquia_id', $parroquia->id)->withCount('mesas')->get();
            return view('territorios.recintos', compact('parroquia', 'recintos'));
        }

        // 3. Si seleccionó un CANTÓN, mostramos sus PARROQUIAS
        if ($request->has('canton')) {
            $canton = \App\Models\Canton::findOrFail($request->canton);
            $parroquias = \App\Models\Parroquia::where('canton_id', $canton->id)->withCount('recintos')->get();
            return view('territorios.parroquias', compact('canton', 'parroquias'));
        }

        // 4. Si seleccionó una PROVINCIA, mostramos sus CANTONES
        if ($request->has('provincia')) {
            $provincia = \App\Models\Provincia::findOrFail($request->provincia);
            $cantones = \App\Models\Canton::where('provincia_id', $provincia->id)->withCount('parroquias')->get();
            return view('territorios.cantones', compact('provincia', 'cantones'));
        }

        // 5. POR DEFECTO: Mostramos PROVINCIAS
        $provincias = \App\Models\Provincia::withCount('cantones')->get();
        return view('territorios.index', compact('provincias'));
    }

    public function storeParroquia(Request $request) 
    {
        $request->validate(['nombre' => 'required', 'canton_id' => 'required']);
        \App\Models\Parroquia::create($request->all());
        return back()->with('success', 'Parroquia creada');
    }

    public function storeRecinto(Request $request) {
        $request->validate(['nombre' => 'required', 'parroquia_id' => 'required']);
        \App\Models\Recinto::create($request->all());
        return back()->with('success', 'Recinto creado');
    }

   public function storeMesa(Request $request) 
   {
        // 1. Validamos estrictamente los datos que vienen del formulario
        $request->validate([
            'numero' => 'required',
            'genero' => 'required',
            'recinto_id' => 'required|exists:recintos,id', // Validamos que el recinto exista
            'num_electores' => 'required|integer|min:0'    // No puede haber electores negativos
        ]);

        // 2. Usamos una transacción para mayor seguridad (Opcional pero recomendado)
        DB::transaction(function () use ($request) {
            \App\Models\Mesa::create($request->all());
        });

        // 3. Regresamos a la vista de mesas con un mensaje de éxito
        return back()->with('success', 'La mesa se ha guardado correctamente.');
    }
    // EDITAR RECINTO
    public function updateRecinto(Request $request, $id) {
        $recinto = \App\Models\Recinto::findOrFail($id);
        $recinto->update($request->all());
        return back()->with('success', 'Recinto actualizado');
    }

    // ELIMINAR RECINTO (Solo si no tiene actas asociadas)
    public function destroyRecinto($id) {
        $recinto = \App\Models\Recinto::findOrFail($id);
        if($recinto->mesas()->count() > 0) return back()->with('error', 'No se puede eliminar: tiene mesas registradas');
        $recinto->delete();
        return back()->with('success', 'Recinto eliminado');
    }

    // EDITAR MESA
    public function updateMesa(Request $request, $id) {
        $mesa = \App\Models\Mesa::findOrFail($id);

        if ($request->has('toggle_status')) {
            // Lógica booleana clara
            $mesa->estado = ($mesa->estado === 'Habilitada') ? 'Deshabilitada' : 'Habilitada';
            $mesa->save();
            return back()->with('success', 'Estado cambiado a ' . $mesa->estado);
        }

        $mesa->update($request->all());
        return back()->with('success', 'Mesa actualizada');
    }

    // ELIMINAR MESA
    public function destroyMesa($id) {
        $mesa = \App\Models\Mesa::findOrFail($id);
        
        // Verificamos si existe CUALQUIER acta vinculada a esta mesa
        if(\App\Models\Acta::where('mesa_id', $id)->exists()) {
            return back()->with('error', 'No se puede eliminar: ya tiene actas de escrutinio registradas');
        }
        
        $mesa->delete();
        return back()->with('success', 'Mesa eliminada');
    }
}