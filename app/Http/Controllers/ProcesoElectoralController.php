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
    /**
     * Limpia quirúrgicamente los datos de simulación y pruebas del proceso seleccionado.
     * Solo aplicable si el proceso es el entorno activo del sistema.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ProcesoElectoral  $proceso
     * @return \Illuminate\Http\Response
     */
    public function limpiarPruebas(Request $request, ProcesoElectoral $proceso)
    {
        // 1. Validación estricta de rol basada en tu método nativo
        if (!auth()->user()->esAdminGeneral()) {
            abort(403, 'Acción no autorizada.');
        }

        // 2. Blindaje perimetral: Solo se permite limpiar el proceso que está activado
        if ($proceso->estado !== 'activo') {
            return back()->withErrors(['error' => 'No se pueden limpiar datos de un proceso electoral archivado o inactivo.']);
        }

        // 3. Validación de las credenciales y la frase de seguridad requerida
        $request->validate([
            'password' => 'required|string',
            'confirmacion' => 'required|string|in:BORRAR PRUEBAS',
        ], [
            'confirmacion.in' => 'Debe escribir exactamente "BORRAR PRUEBAS" para proceder con la limpieza.',
        ]);

        // 4. Verificar la contraseña del Súper Administrador en sesión
        if (!\Illuminate\Support\Facades\Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => 'La contraseña ingresada es incorrecta.']);
        }

        try {
            DB::beginTransaction();

            // 1. Obtener los IDs de todas las actas registradas en el proceso activo
            // Esto nos sirve de ancla para limpiar los desgloses de votos de manera segura
            $actasIds = DB::table('actas')
                ->where('proceso_electoral_id', $proceso->id)
                ->pluck('id');

            if ($actasIds->isNotEmpty()) {
                // 2. Limpieza de los desgloses de votos asociados a esas actas
                DB::table('votos')
                    ->whereIn('acta_id', $actasIds)
                    ->delete();

                // 3. Limpieza de las cabeceras de las actas del proceso activo
                DB::table('actas')
                    ->where('proceso_electoral_id', $proceso->id)
                    ->delete();
            }

            DB::commit();

            // Limpieza inmediata de la caché de la aplicación para actualizar dashboards en tiempo real
            \Illuminate\Support\Facades\Artisan::call('cache:clear');

            return redirect()->route('procesos.index')
                ->with('success', "El proceso \"{$proceso->nombre}\" ha sido restaurado a cero con éxito. Las actas y votos de prueba han sido eliminados.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error crítico durante el vaciado de datos: ' . $e->getMessage()]);
        }
    }
}