<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Partido;
use App\Models\Provincia;
use App\Models\Canton;    
use App\Models\Parroquia; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; 

class CandidatoController extends Controller
{
    /**
     * Muestra la lista de candidatos inscritos filtrados por proceso electoral.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Capturar el proceso actual (por defecto generales)
        $proceso = $request->query('proceso', 'generales');
        if (!in_array($proceso, ['generales', 'primarias'])) {
            abort(404);
        }

        // Relación restrictiva: Solo candidatos cuyo partido político pertenezca al proceso seleccionado
        $query = Candidato::with(['partido', 'provincia', 'canton', 'parroquia'])
            ->whereHas('partido', function ($q) use ($proceso) {
                $q->where('proceso_eleccion', $proceso);
            });

        // --- FILTROS DE VISUALIZACIÓN POR ROL ---
        if ($user->esAdminGeneral()) {
            // Ve absolutamente todo dentro del proceso seleccionado
        } elseif ($user->esAdminProvincial()) {
            $query->where('provincia_id', $user->provincia_id);
        } elseif ($user->esAdminCantonal()) {
            $query->where('canton_id', $user->canton_id);
        } elseif ($user->esAdminParroquial()) {
            $query->where('parroquia_id', $user->parroquia_id);
        }

        $candidatos = $query->orderBy('dignidad')->get();
        
        // Enviamos la variable $proceso a la vista para renderizar los Badges e interfaz dinámicamente
        return view('candidatos.index', compact('candidatos', 'proceso'));
    }

    /**
     * Formulario de inscripción con aislamiento de partidos por proceso.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Capturar proceso para saber qué partidos y contexto mostrar
        $proceso = $request->query('proceso', 'generales');
        if (!in_array($proceso, ['generales', 'primarias'])) {
            abort(404);
        }

        // FILTRO CRÍTICO: Solo partidos políticos que corresponden al proceso actual
        $partidos = Partido::where('proceso_eleccion', $proceso)->get();
        
        // Definir qué dignidades puede crear según su rango
        $dignidadesDisponibles = [];
        if ($user->esAdminGeneral() || $user->esAdminProvincial()) {
            $dignidadesDisponibles = ['Prefecto' => 'Prefecto', 'Alcalde' => 'Alcalde', 'Concejal Urbano' => 'Concejal Urbano', 'Concejal Rural' => 'Concejal Rural', 'Vocal Parroquial' => 'Vocal Parroquial'];
        } elseif ($user->esAdminCantonal()) {
            $dignidadesDisponibles = ['Alcalde' => 'Alcalde', 'Concejal Urbano' => 'Concejal Urbano', 'Concejal Rural' => 'Concejal Rural'];
        } elseif ($user->esAdminParroquial()) {
            $dignidadesDisponibles = ['Vocal Parroquial' => 'Vocal Parroquial'];
        }

        // Filtrar Provincias/Cantones/Parroquias para el formulario
        $provincias = $user->esAdminGeneral() ? Provincia::all() : Provincia::where('id', $user->provincia_id)->get();
        $cantones = collect();
        $parroquias = collect();

        if ($user->esAdminCantonal()) {
            $cantones = Canton::where('id', $user->canton_id)->get();
        } elseif ($user->esAdminParroquial()) {
            $cantones = Canton::where('id', $user->canton_id)->get();
            $parroquias = Parroquia::where('id', $user->parroquia_id)->get();
        }

        return view('candidatos.create', compact('partidos', 'provincias', 'dignidadesDisponibles', 'cantones', 'parroquias', 'proceso'));
    }

    /**
     * Almacena el candidato resguardando la integridad del proceso.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->esAdminGeneral()) {
            $request->merge([
                'provincia_id' => $user->provincia_id,
                'canton_id'    => $user->canton_id ?? $request->canton_id,
                'parroquia_id' => $user->parroquia_id ?? $request->parroquia_id,
            ]);
        }

        $request->validate([
            'nombre'       => 'required|max:150',
            'partido_id'   => 'required|exists:partidos,id',
            'dignidad'     => 'required',
            'provincia_id' => 'required|exists:provincias,id',
            'canton_id'    => 'nullable|exists:cantones,id',
            'parroquia_id' => 'nullable|exists:parroquias,id',
            'foto'         => 'nullable|image|max:2048'
        ]);

        $datos = $request->all();

        // Obtener el partido para heredar automáticamente el flujo de redirección correcto
        $partido = Partido::findOrFail($request->partido_id);
        // Agregar el proceso electoral al array de datos para mantener la integridad referencial
        $datos['tipo_proceso'] = $partido->proceso_eleccion;

        // SEGURIDAD: Forzar territorio según el usuario
        if (!$user->esAdminGeneral()) {
            if (!$user->provincia_id) {
                return back()->withErrors('Tu usuario no tiene una provincia asignada. Contacta al SuperAdmin.');
            }

            $datos['provincia_id'] = $user->provincia_id;
            
            if ($user->esAdminCantonal()) {
                $datos['canton_id'] = $user->canton_id;
                if ($datos['dignidad'] === 'Prefecto') return back()->withErrors('No tienes permiso para crear Prefectos.');
            }
            
            if ($user->esAdminParroquial()) {
                $datos['canton_id'] = $user->canton_id;
                $datos['parroquia_id'] = $user->parroquia_id;
                if ($datos['dignidad'] !== 'Vocal Parroquial') return back()->withErrors('Solo puedes crear Vocales Parroquiales.');
            }
        }

        // Limpieza de jurisdicción según dignidad
        if ($datos['dignidad'] === 'Prefecto') {
            $datos['canton_id'] = null; $datos['parroquia_id'] = null;
        } elseif (in_array($datos['dignidad'], ['Alcalde', 'Concejal Urbano', 'Concejal Rural'])) {
            $datos['parroquia_id'] = null;
        }

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('public/candidatos');
            $datos['foto'] = Storage::url($path);
        }

        Candidato::create($datos);
        
        // Redirección indexada al proceso del candidato guardado
        return redirect()->route('candidatos.index', ['proceso' => $partido->proceso_eleccion])
            ->with('success', 'Inscripción completada correctamente.');
    }

    /**
     * Muestra el formulario de edición aislando el proceso correspondiente.
     */
    public function edit(Candidato $candidato)
    {
        // Cargamos la relación para identificar el contexto del proceso
        $candidato->load('partido');
        $proceso = $candidato->partido->proceso_eleccion;

        // Solo permitimos editar asociando partidos del mismo tipo de proceso
        $partidos = Partido::where('proceso_eleccion', $proceso)->get();
        $provincias = Provincia::all();
        
        return view('candidatos.edit', compact('candidato', 'partidos', 'provincias', 'proceso'));
    }

    /**
     * Actualiza los datos del candidato.
     */
    public function update(Request $request, Candidato $candidato)
    {
        $request->validate([
            'nombre'       => 'required|max:150',
            'partido_id'   => 'required|exists:partidos,id',
            'dignidad'     => 'required|in:Prefecto,Alcalde,Concejal Urbano,Concejal Rural,Vocal Parroquial',
            'provincia_id' => 'required|exists:provincias,id',
            'canton_id'    => 'nullable|exists:cantones,id',
            'parroquia_id' => 'nullable|exists:parroquias,id',
            'foto'         => 'nullable|image|max:2048'
        ]);

        $datos = $request->all();

        // Limpieza de jurisdicción según dignidad
        if ($datos['dignidad'] === 'Prefecto') {
            $datos['canton_id'] = null;
            $datos['parroquia_id'] = null;
        } elseif (in_array($datos['dignidad'], ['Alcalde', 'Concejal Urbano', 'Concejal Rural'])) {
            $datos['parroquia_id'] = null;
        }

        // Procesamiento de la fotografía
        if ($request->hasFile('foto')) {
            if ($candidato->foto) {
                $oldPath = str_replace('/storage/', 'public/', $candidato->foto);
                Storage::delete($oldPath);
            }
            $path = $request->file('foto')->store('public/candidatos');
            $datos['foto'] = Storage::url($path);
        }

        // OBTENEMOS EL PARTIDO PARA ASIGNAR EL PROCESO CORRECTO
        $partido = Partido::findOrFail($request->partido_id);
        $datos['tipo_proceso'] = $partido->proceso_eleccion;

        // ACTUALIZAMOS EL CANDIDATO (Una sola vez, con todos los datos listos)
        $candidato->update($datos);
        
        return redirect()->route('candidatos.index', ['proceso' => $partido->proceso_eleccion])
            ->with('success', 'Datos del candidato actualizados.');
    }

    /**
     * Elimina al candidato de la base de datos.
     */
    public function destroy(Candidato $candidato)
    {
        $candidato->load('partido');
        $procesoActual = $candidato->partido->proceso_eleccion;

        if ($candidato->foto) {
            $path = str_replace('/storage/', 'public/', $candidato->foto);
            Storage::delete($path);
        }

        $candidato->delete();

        return redirect()->route('candidatos.index', ['proceso' => $procesoActual])
            ->with('success', 'Inscripción eliminada correctamente.');
    }

    /**
     * Obtiene los candidatos filtrados por dignidad, jurisdicción y proceso para las actas.
     */
    public function getByDignidad(Request $request, $dignidad)
    {
        $user = auth()->user();
        
        // FILTRO CRÍTICO AJAX: Asegura que el acta consuma datos del proceso correspondiente (vía query string)
        $proceso = $request->query('proceso', 'generales');

        $query = Candidato::with('partido')
            ->where('dignidad', $dignidad)
            ->whereHas('partido', function ($q) use ($proceso) {
                $q->where('proceso_eleccion', $proceso);
            });

        // Seguridad territorial de la consulta
        if (!$user->esAdmin()) {
            if ($user->parroquia_id) {
                $query->where('parroquia_id', $user->parroquia_id);
            } elseif ($user->canton_id) {
                $query->where('canton_id', $user->canton_id);
            }
        } else {
            if ($request->has('provincia_id')) $query->where('provincia_id', $request->provincia_id);
            if ($request->has('canton_id')) $query->where('canton_id', $request->canton_id);
            if ($request->has('parroquia_id')) $query->where('parroquia_id', $request->parroquia_id);
        }

        return response()->json($query->get());
    }
}