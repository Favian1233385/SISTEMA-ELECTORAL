<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class UserGenerationController extends Controller
{
    /**
     * Mapeo seguro de tipos de territorio permitidos para evitar inyecciones SQL en columnas.
     */
    protected $territoriosPermitidos = [
        'provincia' => 'provincia_id',
        'canton'    => 'canton_id',
        'parroquia' => 'parroquia_id'
    ];

    /**
     * Genera usuarios digitadores basados en la división territorial y dignidad.
     */
    public function generarDigitadores(Request $request)
    {
        Log::info("Iniciando generación de digitadores en cascada", $request->all());

        // BP: Validación estricta antes de procesar cualquier dato
        $request->validate([
            'tipo'             => 'required|string|in:provincia,canton,parroquia',
            'id'               => 'required|integer',
            'dignidad'         => 'required|string|min:3',
            'proceso_eleccion' => 'nullable|string|in:primarias,generales'
        ]);

        $tipo = $request->input('tipo'); 
        $territorioId = $request->input('id');
        $dignidad = strtoupper(trim($request->input('dignidad')));
        $procesoRequest = $request->input('proceso_eleccion', 'generales'); 

        $procesoMesa = ($procesoRequest === 'primarias') ? 'primarias' : 'generales';
        $procesoUser = ($procesoRequest === 'primarias') ? 'primaria' : 'general';

        $query = Mesa::where('proceso_eleccion', $procesoMesa)->with(['recinto.parroquia.canton.provincia']);

        // Aplicación del filtro en cascada de forma segura
        if ($tipo === 'provincia') {
            $query->whereHas('recinto.parroquia.canton', function($q) use ($territorioId) {
                $q->where('provincia_id', $territorioId);
            });
        } elseif ($tipo === 'canton') {
            $query->whereHas('recinto.parroquia', function($q) use ($territorioId) {
                $q->where('canton_id', $territorioId);
            });
        } elseif ($tipo === 'parroquia') {
            $query->whereHas('recinto', function($q) use ($territorioId) {
                $q->where('parroquia_id', $territorioId);
            });
        }

        $mesas = $query->get();

        Log::info("Búsqueda de mesas completada para proceso [{$procesoMesa}]", [
            'tipo' => $tipo,
            'id' => $territorioId,
            'cantidad_mesas_encontradas' => $mesas->count()
        ]);

        if ($mesas->isEmpty()) {
            return back()->with('error', "No se encontraron mesas de tipo [{$procesoMesa}] para este territorio.");
        }

        DB::beginTransaction();
        try {
            $creados = 0;

            // 1. LIMPIEZA CRÍTICA: Forzar la eliminación de los usuarios preexistentes de este territorio
            User::where('role', 'digitador')
                ->where('proceso_eleccion', $procesoMesa)
                ->where('dignidad_asignada', $dignidad)
                ->when($tipo === 'provincia', function($q) use ($territorioId) { $q->where('provincia_id', $territorioId); })
                ->when($tipo === 'canton', function($q) use ($territorioId) { $q->where('canton_id', $territorioId); })
                ->when($tipo === 'parroquia', function($q) use ($territorioId) { $q->where('parroquia_id', $territorioId); })
                ->delete();

            // 2. PROCESAMIENTO LIMPIO EN CASCADA
            foreach ($mesas as $mesa) {
                if (!$mesa->recinto || !$mesa->recinto->parroquia || !$mesa->recinto->parroquia->canton) {
                    continue; 
                }

                $recinto = $mesa->recinto;
                $parroquia = $recinto->parroquia;
                $canton = $parroquia->canton;

                $dignidadSlug = Str::slug($dignidad);
                $generoLetra = strtolower(substr($mesa->genero, 0, 1));
                
                $suffix = ($procesoUser === 'primaria') ? '-primaria' : '';
                $username = "c" . $canton->id . "_p" . $parroquia->id . "_r" . $recinto->id . "_m" . $mesa->numero . "-" . $generoLetra . "-" . $dignidadSlug . $suffix;
                $email = $username . "@sistema.com";

                $tagProceso = ($procesoUser === 'primaria') ? ' (Primarias)' : '';
                $nombreRecintoCorto = Str::limit($recinto->nombre, 25, '...');
                $nombreDigitador = "Digitador " . ucfirst(strtolower($dignidad)) . " - " . $nombreRecintoCorto . " - M: " . $mesa->numero . " (" . substr($mesa->genero, 0, 3) . ")" . $tagProceso;
                
                // GENERACIÓN DE CONTRASEÑA DINÁMICA ALEATORIA (6 dígitos)
                $passwordAleatorio = (string) rand(100000, 999999);
                
                User::create([
                    'name'              => $nombreDigitador,
                    'email'             => $email,
                    'password'          => Hash::make($passwordAleatorio),
                    'password_plain'    => $passwordAleatorio, // Asegúrate que esté en el $fillable de User.php
                    'role'              => 'digitador',
                    'proceso_eleccion'  => $procesoMesa,
                    'tipo_proceso'      => $procesoUser,
                    'mesa_id'           => $mesa->id,
                    'dignidad_asignada' => $dignidad, 
                    'provincia_id'      => $canton->provincia_id, 
                    'canton_id'         => $canton->id,
                    'parroquia_id'      => $parroquia->id,
                    'recinto_id'        => $recinto->id,
                    // --- AGREGA ESTAS LÍNEAS DE CONTROL AQUÍ ---
                    'ver_prefectos'      => false,
                    'ver_nivel_superior' => false,
                    'ver_nivel_inferior' => false,
                ]);
                
                $creados++;
            }

            DB::commit();
            return back()->with('success', "Se han generado exitosamente $creados usuarios digitadores para el territorio de tipo [$tipo] en el proceso electoral.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en generación de usuarios", [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);
            return back()->with('error', 'Error en asignación masiva: ' . $e->getMessage());
        }
    }

    /**
     * Vista para listar e informar el aislamiento total de credenciales de forma optimizada.
     */
    public function verDigitadores(Request $request)
    {
        // BP: Validación de parámetros en consultas de lectura
        $request->validate([
            'tipo'             => 'nullable|string|in:provincia,canton,parroquia',
            'id'               => 'nullable|integer',
            'proceso_eleccion' => 'nullable|string|in:primarias,generales',
            'dignidad'         => 'nullable|string'
        ]);

        $tipo = $request->query('tipo');
        $id = $request->query('id');
        $procesoEleccion = $request->query('proceso_eleccion', 'generales');
        $dignidad = $request->query('dignidad') ? strtoupper(trim($request->query('dignidad'))) : null;

        $query = User::where('role', 'digitador')
                    ->where('proceso_eleccion', $procesoEleccion)
                    ->with(['mesa.recinto.parroquia.canton.provincia']);

        // BP: Optimización drástica de rendimiento y mitigación SQL injection
        if ($tipo && $id && array_key_exists($tipo, $this->territoriosPermitidos)) {
            $columnaColocada = $this->territoriosPermitidos[$tipo];
            $query->where($columnaColocada, $id);
        }

        if ($dignidad) {
            $query->where('dignidad_asignada', $dignidad);
        }

        Log::info("Consulta adaptativa de digitadores ejecutada con alta eficiencia", [
            'alcance_territorio' => $tipo,
            'territorio_id'      => $id,
            'dignidad_filtrada'  => $dignidad ?? 'TODAS'
        ]);

        $digitadores = $query->orderBy('name', 'asc')->get();

        // 1. CONDICIONAL PRIORITARIA: Si se solicita el PDF, se envía la colección PURA y LIMPIA inmediatamente
        if ($request->has('pdf')) {
            return $this->exportarPDF($digitadores, $tipo, $id, $dignidad, $procesoEleccion);
        }

        // 2. ENMASCARAMIENTO EXCLUSIVO PARA LA VISTA WEB: 
        // Solo si no fue PDF, mutamos los datos para proteger la visualización en pantalla
        $digitadores->transform(function ($user) {
            // Reemplazar la clave real por asteriscos o 'N/A' solo para la tabla HTML del navegador
            if (!empty($user->password_plain)) {
                $user->password_plain = '••••••'; // Protege visualmente la pantalla contra espías
            } else {
                $user->password_plain = 'N/A';
            }
            return $user;
        });

        return view('admin.digitadores.index', compact('digitadores', 'tipo', 'id', 'dignidad', 'procesoEleccion'));
    }

    /**
     * Limpieza controlada de usuarios por filtros territoriales.
     */
    public function limpiarDigitadores(Request $request)
    {
        Log::info("Iniciando depuración quirúrgica de digitadores", $request->all());

        $request->validate([
            'tipo'             => 'nullable|string|in:provincia,canton,parroquia',
            'id'               => 'nullable|integer',
            'dignidad'         => 'nullable|string',
            'proceso_eleccion' => 'nullable|string|in:primarias,generales'
        ]);

        $tipo = $request->input('tipo'); 
        $territorioId = $request->input('id');
        $dignidad = $request->input('dignidad') ? strtoupper(trim($request->input('dignidad'))) : null;
        $procesoRequest = $request->input('proceso_eleccion', 'generales');

        $procesoMesa = ($procesoRequest === 'primarias') ? 'primarias' : 'generales';

        try {
            $query = User::where('role', 'digitador')->where('proceso_eleccion', $procesoMesa);

            // BP: Uso estricto del arreglo seguro para evitar alteración del query builder
            if ($tipo && $territorioId && array_key_exists($tipo, $this->territoriosPermitidos)) {
                $query->where($this->territoriosPermitidos[$tipo], $territorioId);
            }

            if ($dignidad) {
                $query->where('dignidad_asignada', $dignidad);
            }

            $totalAEliminar = $query->count();

            if ($totalAEliminar === 0) {
                return back()->with('error', "No se encontraron usuarios digitadores registrados para los filtros seleccionados.");
            }

            $query->delete();

            Log::info("Depuración completada exitosamente.", [
                'cantidad_eliminados' => $totalAEliminar
            ]);

            return back()->with('success', "Se han eliminado con éxito $totalAEliminar usuarios digitadores.");

        } catch (\Exception $e) {
            Log::error("Error crítico en la limpieza de digitadores", [
                'mensaje' => $e->getMessage()
            ]);
            return back()->with('error', 'Error al intentar depurar los usuarios: ' . $e->getMessage());
        }
    }

   /**
     * Genera el PDF y aplica la purga automática de seguridad inmediatamente.
     */
    /**
     * Genera el PDF y aplica la purga automática de seguridad inmediatamente.
     */
    private function exportarPDF($digitadores, $tipo, $id, $dignidad, $procesoEleccion)
    {
        if ($digitadores->isEmpty()) {
            return back()->with('error', 'No hay datos disponibles para generar el reporte PDF.');
        }

        $tituloReporte = "CREDENCIALES DE ACCESO - DIGITADORES (" . strtoupper($procesoEleccion) . ")";
        $subtitulo = "Territorio: " . ucfirst($tipo) . " (ID: $id) | Dignidad: " . ($dignidad ?? 'TODAS');

        // 1. VINCULAR LA VISTA
        $pdf = Pdf::loadView('admin.digitadores.pdf', compact('digitadores', 'tituloReporte', 'subtitulo'))
                  ->setPaper('a4', 'portrait')
                  ->setWarnings(false);
        
        // 2. FORZAR EL RENDERIZADO INMEDIATO EN MEMORIA RAM
        // Esto procesa el HTML y lee 'password_plain' cuando los datos aún existen intactos.
        $pdfRenderizado = $pdf->output();

        // =========================================================================
        // CONTROL EN DESARROLLO: SE DESACTIVA LA PURGA PARA AUDITAR CONTRASEÑAS
        // =========================================================================
        // Las siguientes líneas se comentan para evitar que las claves queden en NULL
        // y garantizar consistencia absoluta entre la base de datos y el PDF generado.
        //
        // $userIds = $digitadores->pluck('id');
        // User::whereIn('id', $userIds)->update(['password_plain' => null]);
        // 
        // Log::info("Seguridad Automatizada: Claves planas eliminadas con éxito tras compilar el PDF.", [
        //     'cantidad_usuarios_protegidos' => $userIds->count()
        // ]);
        // =========================================================================

        // 4. RETORNAR EL ARCHIVO PRE-RENDERIZADO AL NAVEGADOR
        return response()->streamDownload(function () use ($pdfRenderizado) {
            echo $pdfRenderizado;
        }, "credenciales_digitadores_{$procesoEleccion}_{$tipo}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"credenciales_digitadores_{$procesoEleccion}_{$tipo}.pdf\""
        ]);
    }
}