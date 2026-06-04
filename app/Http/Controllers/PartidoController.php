<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; // REQUERIDO: Para la validación condicional única

class PartidoController extends Controller
{
    /**
     * Muestra la lista de movimientos políticos filtrados por proceso.
     */
    public function index(Request $request)
    {
        // Determinamos el proceso actual. Por defecto: generales
        $proceso = $request->query('proceso', 'generales');

        // Validamos que el proceso sea estrictamente uno de los dos permitidos
        if (!in_array($proceso, ['generales', 'primarias'])) {
            abort(404);
        }

        // Filtramos la consulta directamente en la base de datos
        $partidos = Partido::where('proceso_eleccion', $proceso)->get();

        return view('partidos.index', compact('partidos', 'proceso'));
    }
       /**
     * Muestra el formulario para crear un nuevo partido.
     */
    public function create(Request $request)
    {
        // 1. Capturamos el parámetro 'proceso' de la URL (?proceso=primarias). Si no existe, por defecto será 'generales'.
        $proceso = $request->query('proceso', 'generales');

        // 2. Validamos que el proceso sea estrictamente uno de los dos permitidos para evitar manipulaciones.
        if (!in_array($proceso, ['generales', 'primarias'])) {
            $proceso = 'generales';
        }

        // 3. Pasamos la variable $proceso a la vista partidos.create
        return view('partidos.create', compact('proceso'));
    }

    /**
     * Almacena un partido recién creado en la base de datos.
     */
    public function store(Request $request)
    {
        // 1. Validar datos con rigor aislando el contexto por proceso_eleccion
        $request->validate([
            'proceso_eleccion' => 'required|in:generales,primarias',
            'nombre' => [
                'required',
                'max:150',
                // El nombre solo es único dentro del mismo tipo de proceso electoral
                Rule::unique('partidos')->where(function ($query) use ($request) {
                    return $query->where('proceso_eleccion', $request->proceso_eleccion);
                })
            ],
            'lista'  => 'required|max:50',
            'logo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $datos = $request->all();

        // 2. Gestión de la imagen
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/logos');
            $datos['logo'] = Storage::url($path);
        }

        // 3. Crear registro
        Partido::create($datos);

        return redirect()->route('partidos.index', ['proceso' => $request->proceso_eleccion])
            ->with('success', 'Registro completado correctamente.');
    }

    /**
     * Muestra los detalles de un partido específico (opcional).
     */
    public function show(Partido $partido)
    {
        return view('partidos.show', compact('partido'));
    }

    /**
     * Muestra el formulario para editar un partido existente.
     */
    /**
     * Muestra el formulario para editar un partido existente.
     */
    public function edit(Partido $partido)
    {
        // Extraemos el proceso real que tiene grabado el partido en la base de datos
        $proceso = $partido->proceso_eleccion;

        // Pasamos tanto el partido como el proceso a la vista de edición
        return view('partidos.edit', compact('partido', 'proceso'));
    }

    /**
     * Actualiza el partido en la base de datos.
     */
    public function update(Request $request, Partido $partido)
    {
        $request->validate([
            'proceso_eleccion' => 'required|in:generales,primarias',
            'nombre' => [
                'required',
                'max:150',
                // Ignora el ID actual pero valida que no choque con otro del mismo proceso
                Rule::unique('partidos')->where(function ($query) use ($request) {
                    return $query->where('proceso_eleccion', $request->proceso_eleccion);
                })->ignore($partido->id)
            ],
            'lista'  => 'required|max:50',
            'logo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $datos = $request->all();

        if ($request->hasFile('logo')) {
            if ($partido->logo) {
                $oldPath = str_replace('/storage/', 'public/', $partido->logo);
                Storage::delete($oldPath);
            }
            
            $path = $request->file('logo')->store('public/logos');
            $datos['logo'] = Storage::url($path);
        }

        $partido->update($datos);

        return redirect()->route('partidos.index', ['proceso' => $partido->proceso_eleccion])
            ->with('success', 'Registro actualizado con éxito.');
    }

    /**
     * Elimina un partido y su logo del servidor.
     */
    public function destroy(Partido $partido)
    {
        $procesoActual = $partido->proceso_eleccion;

        if ($partido->logo) {
            $path = str_replace('/storage/', 'public/', $partido->logo);
            Storage::delete($path);
        }

        $partido->delete();

        return redirect()->route('partidos.index', ['proceso' => $procesoActual])
            ->with('success', 'Registro eliminado satisfactoriamente.');
    }
}