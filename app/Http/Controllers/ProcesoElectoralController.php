<?php

namespace App\Http\Controllers;

use App\Models\ProcesoElectoral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcesoElectoralController extends Controller
{
    /**
     * Muestra la lista de procesos electorales (Histórico).
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Validación de seguridad para el Administrador
        if (!auth()->user()->esAdminGeneral()) {
            abort(403, 'Acción no autorizada.');
        }

        // Ordenamos por año y por ID de forma descendente para mejor lectura visual
        $procesos = ProcesoElectoral::orderBy('anio', 'desc')
                                    ->orderBy('id', 'desc')
                                    ->get();
                                    
        return view('procesos.index', compact('procesos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Almacena un nuevo proceso electoral en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->esAdminGeneral()) {
            abort(403, 'Acción no autorizada.');
        }

        // Validación estricta incluyendo las reglas del Enum para el Tipo
        $request->validate([
            'nombre' => 'required|string|max:150|unique:procesos_electorales,nombre',
            'anio'   => 'required|integer|digits:4|min:2020|max:2050',
            'tipo'   => 'required|in:generales,primarias', // Rigor técnico
        ]);

        // Envoltura transaccional atómica para asegurar la consistencia del entorno
        DB::transaction(function () use ($request) {
            // Archivar únicamente el proceso dominante actual
            ProcesoElectoral::where('estado', 'activo')->update(['estado' => 'archivado']);

            // Crear el nuevo periodo con su tipo correspondiente
            ProcesoElectoral::create([
                'nombre' => $request->nombre,
                'anio'   => $request->anio,
                'tipo'   => $request->tipo,
                'estado' => 'activo'
            ]);
        });

        return redirect()->route('procesos.index')
            ->with('success', 'Nuevo proceso electoral inicializado y establecido como entorno activo.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProcesoElectoral  $procesoElectoral
     * @return \Illuminate\Http\Response
     */
    public function show(ProcesoElectoral $procesoElectoral)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProcesoElectoral  $procesoElectoral
     * @return \Illuminate\Http\Response
     */
    public function edit(ProcesoElectoral $procesoElectoral)
    {
        abort(404);
    }

    /**
     * Actualiza el estado del proceso para alternar entre históricos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProcesoElectoral  $proceso
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProcesoElectoral $proceso)
    {
        if (!auth()->user()->esAdminGeneral()) {
            abort(403, 'Acción no autorizada.');
        }

        // Si el proceso ya es el activo, evitamos consultas innecesarias
        if ($proceso->estado === 'activo') {
            return redirect()->route('procesos.index');
        }

        DB::transaction(function () use ($proceso) {
            // Consulta quirúrgica: Solo archivamos el que esté activo actualmente
            ProcesoElectoral::where('estado', 'activo')->update(['estado' => 'archivado']);

            // Activar el proceso específico seleccionado por el usuario
            $proceso->update(['estado' => 'activo']);
        });

        return redirect()->route('procesos.index')
            ->with('success', "El sistema se ha configurado en el periodo: {$proceso->nombre} (" . ucfirst($proceso->tipo) . ").");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProcesoElectoral  $procesoElectoral
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProcesoElectoral $procesoElectoral)
    {
        abort(403, 'El histórico electoral no puede ser eliminado.');
    }
}