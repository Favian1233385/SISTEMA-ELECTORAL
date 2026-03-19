<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Partido;
use App\Models\Provincia;
use App\Models\Canton;    // <--- AÑADE ESTA LÍNEA
use App\Models\Parroquia; // <--- AÑADE ESTA LÍNEA
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth; // <--- ESTA ES LA LÍNEA QUE FALTA

class CandidatoController extends Controller
{
    /**
     * Muestra la lista de candidatos inscritos.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Candidato::with(['partido', 'provincia', 'canton', 'parroquia']);

        // --- FILTROS DE VISUALIZACIÓN POR ROL ---
        if ($user->esAdminGeneral()) {
            // Ve absolutamente todo
        } elseif ($user->esAdminProvincial()) {
            // Solo ve candidatos de su provincia
            $query->where('provincia_id', $user->provincia_id);
        } elseif ($user->esAdminCantonal()) {
            // Solo ve candidatos de su cantón (Alcaldes y Concejales)
            $query->where('canton_id', $user->canton_id);
        } elseif ($user->esAdminParroquial()) {
            // Solo ve candidatos de su parroquia (Vocales)
            $query->where('parroquia_id', $user->parroquia_id);
        }

        $candidatos = $query->orderBy('dignidad')->get();
        return view('candidatos.index', compact('candidatos'));
    }

    /**
     * Formulario de inscripción con restricciones de Dignidad y Territorio.
     */
    public function create()
    {
        $user = Auth::user();
        $partidos = Partido::all();
        
        // 1. Definir qué dignidades puede crear según su rango
        $dignidadesDisponibles = [];
        if ($user->esAdminGeneral() || $user->esAdminProvincial()) {
            $dignidadesDisponibles = ['Prefecto' => 'Prefecto', 'Alcalde' => 'Alcalde', 'Concejal Urbano' => 'Concejal Urbano', 'Concejal Rural' => 'Concejal Rural', 'Vocal Parroquial' => 'Vocal Parroquial'];
        } elseif ($user->esAdminCantonal()) {
            $dignidadesDisponibles = ['Alcalde' => 'Alcalde', 'Concejal Urbano' => 'Concejal Urbano', 'Concejal Rural' => 'Concejal Rural'];
        } elseif ($user->esAdminParroquial()) {
            $dignidadesDisponibles = ['Vocal Parroquial' => 'Vocal Parroquial'];
        }

        // 2. Filtrar Provincias/Cantones/Parroquias para el formulario
        $provincias = $user->esAdminGeneral() ? Provincia::all() : Provincia::where('id', $user->provincia_id)->get();
        $cantones = collect();
        $parroquias = collect();

        if ($user->esAdminCantonal()) {
            $cantones = Canton::where('id', $user->canton_id)->get();
        } elseif ($user->esAdminParroquial()) {
            $cantones = Canton::where('id', $user->canton_id)->get();
            $parroquias = Parroquia::where('id', $user->parroquia_id)->get();
        }

        return view('candidatos.create', compact('partidos', 'provincias', 'dignidadesDisponibles', 'cantones', 'parroquias'));
    }

    /**
     * Almacena el candidato validando que el usuario no exceda sus permisos.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // --- MEJORA: Si no es admin general, inyectamos su territorio al request 
        // para que la validación no falle si el campo llega vacío desde la vista. ---
        if (!$user->esAdminGeneral()) {
            $request->merge([
                'provincia_id' => $user->provincia_id,
                'canton_id'    => $user->canton_id ?? $request->canton_id,
                'parroquia_id' => $user->parroquia_id ?? $request->parroquia_id,
            ]);
        }

        // Ahora la validación siempre pasará porque los IDs ya están en el $request
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

        // --- SEGURIDAD: Forzar territorio según el usuario ---
        if (!$user->esAdminGeneral()) {
        // Si el usuario no tiene provincia_id, lanzamos un error claro antes de intentar guardar
        if (!$user->provincia_id) {
            return back()->withErrors('Tu usuario no tiene una provincia asignada. Contacta al SuperAdmin.');
        }

        $datos['provincia_id'] = $user->provincia_id;
            
            if ($user->esAdminCantonal()) {
                $datos['canton_id'] = $user->canton_id;
                // Impedir que un cantonal cree prefectos
                if ($datos['dignidad'] === 'Prefecto') return back()->withErrors('No tienes permiso para crear Prefectos.');
            }
            
            if ($user->esAdminParroquial()) {
                $datos['canton_id'] = $user->canton_id;
                $datos['parroquia_id'] = $user->parroquia_id;
                // Solo puede crear vocales
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
        return redirect()->route('candidatos.index')->with('success', 'Candidato inscrito correctamente.');
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(Candidato $candidato)
    {
        $partidos = Partido::all();
        $provincias = Provincia::all();
        return view('candidatos.edit', compact('candidato', 'partidos', 'provincias'));
    }

    /**
     * Actualiza los datos del candidato y gestiona la foto antigua.
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

        // --- LÓGICA DE LIMPIEZA DE JURISDICCIÓN ---
        if ($datos['dignidad'] === 'Prefecto') {
            $datos['canton_id'] = null;
            $datos['parroquia_id'] = null;
        } elseif (in_array($datos['dignidad'], ['Alcalde', 'Concejal Urbano', 'Concejal Rural'])) {
            $datos['parroquia_id'] = null;
        }

        if ($request->hasFile('foto')) {
            // Eliminar foto antigua del disco para ahorrar espacio
            if ($candidato->foto) {
                $oldPath = str_replace('/storage/', 'public/', $candidato->foto);
                Storage::delete($oldPath);
            }
            // Guardar la nueva foto
            $path = $request->file('foto')->store('public/candidatos');
            $datos['foto'] = Storage::url($path);
        }

        $candidato->update($datos);

        return redirect()->route('candidatos.index')->with('success', 'Datos del candidato actualizados.');
    }

    /**
     * Elimina al candidato y su archivo de imagen del servidor.
     */
    public function destroy(Candidato $candidato)
    {
        if ($candidato->foto) {
            $path = str_replace('/storage/', 'public/', $candidato->foto);
            Storage::delete($path);
        }

        $candidato->delete();

        return redirect()->route('candidatos.index')->with('success', 'Inscripción eliminada correctamente.');
    }
    /**
     * Obtiene los candidatos filtrados por dignidad y jurisdicción para el formulario de actas.
     */
    public function getByDignidad(Request $request, $dignidad)
    {
        $user = auth()->user();
        $query = Candidato::with('partido')->where('dignidad', $dignidad);

        // Seguridad: Si no es admin, forzamos que solo busque en SU territorio
        if (!$user->esAdmin()) {
            if ($user->parroquia_id) {
                $query->where('parroquia_id', $user->parroquia_id);
            } elseif ($user->canton_id) {
                $query->where('canton_id', $user->canton_id);
            }
        } else {
            // Si es admin, usa los filtros que vengan por Request (el comportamiento original)
            if ($request->has('provincia_id')) $query->where('provincia_id', $request->provincia_id);
            if ($request->has('canton_id')) $query->where('canton_id', $request->canton_id);
            if ($request->has('parroquia_id')) $query->where('parroquia_id', $request->parroquia_id);
        }

        return response()->json($query->get());
    }
}