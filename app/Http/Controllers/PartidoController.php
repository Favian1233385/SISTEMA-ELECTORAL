<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartidoController extends Controller
{
    /**
     * Muestra la lista de todos los movimientos políticos.
     */
    public function index()
    {
        $partidos = Partido::all();
        return view('partidos.index', compact('partidos'));
    }

    /**
     * Muestra el formulario para crear un nuevo partido.
     */
    public function create()
    {
        return view('partidos.create');
    }

    /**
     * Almacena un partido recién creado en la base de datos.
     */
    public function store(Request $request)
    {
        // 1. Validar datos con rigor
        $request->validate([
            'nombre' => 'required|unique:partidos,nombre|max:150',
            'lista'  => 'required|max:50',
            'logo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Soporte para webp
        ]);

        $datos = $request->all();

        // 2. Gestión de la imagen
        if ($request->hasFile('logo')) {
            // Guardar en storage/app/public/logos
            $path = $request->file('logo')->store('public/logos');
            // Guardar la URL amigable en la BD
            $datos['logo'] = Storage::url($path);
        }

        // 3. Crear registro
        Partido::create($datos);

        return redirect()->route('partidos.index')
            ->with('success', 'Movimiento Político registrado correctamente.');
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
    public function edit(Partido $partido)
    {
        return view('partidos.edit', compact('partido'));
    }

    /**
     * Actualiza el partido en la base de datos.
     */
    public function update(Request $request, Partido $partido)
    {
        $request->validate([
            'nombre' => 'required|max:150|unique:partidos,nombre,' . $partido->id,
            'lista'  => 'required|max:50',
            'logo'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $datos = $request->all();

        if ($request->hasFile('logo')) {
            // Eliminar el logo anterior si existe para no llenar el servidor de basura
            if ($partido->logo) {
                $oldPath = str_replace('/storage/', 'public/', $partido->logo);
                Storage::delete($oldPath);
            }
            
            $path = $request->file('logo')->store('public/logos');
            $datos['logo'] = Storage::url($path);
        }

        $partido->update($datos);

        return redirect()->route('partidos.index')
            ->with('success', 'Movimiento Político actualizado con éxito.');
    }

    /**
     * Elimina un partido y su logo del servidor.
     */
    public function destroy(Partido $partido)
    {
        // 1. Eliminar archivo físico
        if ($partido->logo) {
            $path = str_replace('/storage/', 'public/', $partido->logo);
            Storage::delete($path);
        }

        // 2. Eliminar de la BD
        $partido->delete();

        return redirect()->route('partidos.index')
            ->with('success', 'Movimiento Político eliminado satisfactoriamente.');
    }
}