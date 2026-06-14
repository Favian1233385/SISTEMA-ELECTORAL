<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Acta;
use App\Models\JurisdiccionConfig;
use App\Models\Canton; 
use App\Models\Parroquia;
use App\Models\ProcesoElectoral; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request; 
use Barryvdh\DomPDF\Facade\Pdf;

class ResultadoController extends Controller
{
    public function index(Request $request) 
    {
        $user = Auth::user();
        
        $procesoId = $request->get('proceso_id') ?? $request->get('proceso');
        
        if ($procesoId) {
            $procesoActual = ProcesoElectoral::find($procesoId);
        } else {
            $procesoActual = ProcesoElectoral::where('estado', 'activo')->first();
        }

        if (!$procesoActual) {
            abort(500, 'No se ha inicializado ningún proceso electoral en el sistema.');
        }

        // 1. Obtener pestañas según Rol y Configuración
        $config = JurisdiccionConfig::where('canton_id', $user->canton_id)->first();
        $pestanasVisibles = $this->obtenerPestanasVisibles($user, $config);

        // 2. Garantizar valor para la dignidad
        $dignidadSeleccionada = $request->get('dignidad');
        if (!$dignidadSeleccionada || !in_array($dignidadSeleccionada, $pestanasVisibles)) {
            $valorPorDefecto = !empty($pestanasVisibles) ? reset($pestanasVisibles) : 'Alcalde';
            $dignidadSeleccionada = is_array($valorPorDefecto) ? 'Alcalde' : $valorPorDefecto;
        }

        // 3. Definir Jurisdicción de consulta
        $cantonFiltro = $request->get('canton_id');
        $parroquiaFiltro = $request->get('parroquia_id');
        $recintoFiltro = $request->get('recinto_id'); 
        $mesaFiltro = $request->get('mesa_id');       

        // SEGURIDAD: Validación territorial estricta
        if ($user->esAdminGeneral()) {
            $finalCantonId = $cantonFiltro;
        } elseif ($user->role === 'admin_provincial') {
            if ($cantonFiltro) {
                $cantonValido = Canton::where('id', $cantonFiltro)->where('provincia_id', $user->provincia_id)->exists();
                $finalCantonId = $cantonValido ? $cantonFiltro : null;
            } else {
                $finalCantonId = null;
            }
        } else {
            $finalCantonId = $user->canton_id;
        }
        
        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        // 4. Consulta de Candidatos (Solo se computan votos de actas limpias 'ingresada')
        $queryResultados = Candidato::with(['partido'])
            ->where('proceso_electoral_id', '=', $procesoActual->id) 
            ->where('dignidad', '=', (string) $dignidadSeleccionada);

        if ($user->role === 'admin_provincial' && $dignidadSeleccionada === 'Prefecto') {
            $queryResultados->where('provincia_id', $user->provincia_id);
        }

        $queryResultados->when($finalCantonId, function($q) use ($finalCantonId, $dignidadSeleccionada) {
            if (in_array($dignidadSeleccionada, ['Alcalde', 'Concejales'])) {
                return $q->where('canton_id', $finalCantonId);
            }
            return $q;
        })
        ->when($finalParroquiaId, function($q) use ($finalParroquiaId, $dignidadSeleccionada) {
            if ($dignidadSeleccionada === 'Junta Parroquial') {
                return $q->where('parroquia_id', $finalParroquiaId);
            }
            return $q;
        });

        // Escrutinio de candidatos
        $resultados = $queryResultados->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId, $recintoFiltro, $mesaFiltro, $user, $procesoActual) {
                    $query->where('actas.proceso_electoral_id', '=', $procesoActual->id) 
                        ->where('actas.estado', '=', 'ingresada')
                        ->when($user->role === 'admin_provincial', function($q) use ($user) {
                            $q->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('cantones.provincia_id', $user->provincia_id));
                        })
                        ->when($finalCantonId, function($q) use ($finalCantonId) {
                            $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
                        })
                        ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                            $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
                        })
                        ->when($recintoFiltro, function($q) use ($recintoFiltro) {
                            $q->whereHas('mesa', fn($m) => $m->where('recinto_id', $recintoFiltro));
                        })
                        ->when($mesaFiltro, function($q) use ($mesaFiltro) {
                            $q->where('actas.mesa_id', $mesaFiltro);
                        });
                }], 'acta_candidato.votos')
                ->get()
                ->map(function($candidato) {
                    $candidato->total_votos = (int)($candidato->total_votos ?? 0);
                    return $candidato;
                })
                ->sortByDesc('total_votos')
                ->values();
            
            // =========================================================================
            // DEFINICIÓN DE LA QUERY BASE CORREGIDA (Filtros acumulativos e independientes)
            // =========================================================================
            $baseActasQuery = Acta::query()
                ->where('actas.proceso_electoral_id', $procesoActual->id)
                ->where('actas.dignidad', (string) $dignidadSeleccionada);

            // 1. Restricción obligatoria para administradores provinciales
            if ($user->role === 'admin_provincial') {
                $baseActasQuery->whereHas('mesa.recinto.parroquia.canton', function($q) use ($user) {
                    $q->where('cantones.provincia_id', $user->provincia_id);
                });
            }

            // 2. Filtros territoriales estructurales (Siempre aplican según el contexto geográfico)
            if ($finalCantonId) {
                $baseActasQuery->whereHas('mesa.recinto.parroquia', function($q) use ($finalCantonId) {
                    $q->where('canton_id', $finalCantonId);
                });
            }
            
            if ($finalParroquiaId) {
                $baseActasQuery->whereHas('mesa.recinto', function($q) use ($finalParroquiaId) {
                    $q->where('parroquia_id', $finalParroquiaId);
                });
            }

            // 3. Filtros específicos de la búsqueda del usuario (Selectores dinámicos)
            if ($recintoFiltro) {
                $baseActasQuery->whereHas('mesa', function($q) use ($recintoFiltro) {
                    $q->where('recinto_id', $recintoFiltro);
                });
            }

            if ($mesaFiltro) {
                $baseActasQuery->where('actas.mesa_id', $mesaFiltro);
            }

        // 5. Cálculo de Totales y ESCRUTINIO REAL (CORREGIDO PARA CUADRE PERFECTO)
        // =========================================================================
        $totalActasRecibidas = (clone $baseActasQuery)->count();

        // Creamos un clon limpio y específico para las métricas de las actas ingresadas
        $queryActasIngresadas = (clone $baseActasQuery)->where('estado', '=', 'ingresada');

        // Extraemos los totales de forma directa
        $votosBlancos        = (int) $queryActasIngresadas->sum('votos_blancos');
        $votosNulos          = (int) $queryActasIngresadas->sum('votos_nulos');
        $ausentismoAcumulado = (int) $queryActasIngresadas->sum('ausentismo');

        // Calcular censo o padrón electoral de las mesas escrutadas limpias
        $padronEscrutadoLimpio = DB::table('mesas')
            ->join('actas', 'mesas.id', '=', 'actas.mesa_id')
            ->where('actas.proceso_electoral_id', '=', $procesoActual->id)
            ->where('actas.dignidad', '=', (string) $dignidadSeleccionada)
            ->where('actas.estado', '=', 'ingresada')
            ->when($mesaFiltro, fn($q) => $q->where('mesas.id', $mesaFiltro))
            ->when($recintoFiltro, fn($q) => $q->where('mesas.recinto_id', $recintoFiltro))
            ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                $q->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
                  ->where('recintos.parroquia_id', $finalParroquiaId);
            })
            ->when($finalCantonId && !$finalParroquiaId, function($q) use ($finalCantonId) {
                $q->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
                  ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
                  ->where('parroquias.canton_id', $finalCantonId);
            })
            ->sum('mesas.num_electores');

        // Mesas totales de la jurisdicción mapeada
        $totalMesasJurisdiccion = DB::table('mesas')
            ->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
            ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
            ->join('cantones', 'parroquias.canton_id', '=', 'cantones.id')
            ->when($user->role === 'admin_provincial', fn($q) => $q->where('cantones.provincia_id', $user->provincia_id))
            ->when($finalCantonId, fn($q) => $q->where('parroquias.canton_id', $finalCantonId))
            ->when($finalParroquiaId, fn($q) => $q->where('parroquias.id', $finalParroquiaId))
            ->when($recintoFiltro, fn($q) => $q->where('mesas.recinto_id', $recintoFiltro))
            ->when($mesaFiltro, fn($q) => $q->where('mesas.id', $mesaFiltro))
            ->count();

        $porcentajeEscrutinio = $totalMesasJurisdiccion > 0 
            ? ($totalActasRecibidas / $totalMesasJurisdiccion) * 100 
            : 0;

        // --- CÁLCULOS ARITMÉTICOS DE CONTROL ---
        $sumaVotosCandidatos   = $resultados->sum('total_votos'); // Votos Válidos
        $totalSufragantes       = $sumaVotosCandidatos + $votosBlancos + $votosNulos; // Papeletas físicas en urna
        $granTotalEscrutado     = $totalSufragantes + $ausentismoAcumulado; // Debe aproximarse al Padrón Escrutado

        // Cálculo porcentual del Comportamiento del Electorado
        $porcentajeParticipacion = $padronEscrutadoLimpio > 0
            ? ($totalSufragantes / $padronEscrutadoLimpio) * 100
            : 0;

        $porcentajeAusentismo = $padronEscrutadoLimpio > 0
            ? ($ausentismoAcumulado / $padronEscrutadoLimpio) * 100
            : 0;

        // 6. FILTRADO PARA EL SELECTOR DE CANTONES Y PARROQUIAS
        if ($user->esAdminGeneral()) {
            $cantonesVisibles = Canton::orderBy('nombre')->get();
        } elseif ($user->role === 'admin_provincial') {
            $cantonesVisibles = Canton::where('provincia_id', $user->provincia_id)->orderBy('nombre')->get();
        } else {
            $cantonesVisibles = [];
        }

        if ($finalCantonId) {
            $parroquias = Parroquia::where('canton_id', $finalCantonId)->orderBy('nombre')->get();
        } elseif ($user->role === 'admin_provincial') {
            $parroquias = Parroquia::whereHas('canton', function($q) use ($user) {
                $q->where('provincia_id', $user->provincia_id);
            })->orderBy('nombre')->get();
        } else {
            $parroquias = [];
        }
        
        $recintosVisibles = [];
        if ($finalParroquiaId) {
            $recintosVisibles = \App\Models\Recinto::where('parroquia_id', $finalParroquiaId)
                ->orderBy('nombre')
                ->get();
        }

        $mesasVisibles = [];
        if ($recintoFiltro) {
            $mesasVisibles = \App\Models\Mesa::where('recinto_id', $recintoFiltro)
                ->orderBy('numero')
                ->orderBy('genero')
                ->get();
        }
        
        return view('resultados', [
        'proceso'                 => $procesoActual, 
        'resultados'              => $resultados,
        'totalVotosValidos'       => $sumaVotosCandidatos, 
        'totalVotosBlancos'       => $votosBlancos,
        'totalVotosNulos'         => $votosNulos,
        'totalSufragantes'        => $totalSufragantes,    
        'totalActas'              => $totalActasRecibidas,
        'porcentajeEscrutinio'    => number_format($porcentajeEscrutinio, 1),
        'granTotalVotos'          => $granTotalEscrutado,  
        
        // SOLUCCIÓN: Enviar el dato bajo todos los nombres posibles que use la vista
        'ausentismo'              => $ausentismoAcumulado,
        'totalAusentismo'         => $ausentismoAcumulado,
        'total_ausentismo'        => $ausentismoAcumulado,

        'padronEscrutado'         => $padronEscrutadoLimpio,
        'porcentajeParticipacion' => number_format($porcentajeParticipacion, 1),
        'porcentajeAusentismo'    => number_format($porcentajeAusentismo, 1),
        'dignidadSeleccionada'    => $dignidadSeleccionada,
        'pestanasVisibles'        => $pestanasVisibles,
        'cantones'                => $cantonesVisibles,
        'parroquias'              => $parroquias,
        'recintos'                => $recintosVisibles,
        'mesas'                   => $mesasVisibles,
        'recintoSeleccionado'     => $recintoFiltro,
        'mesaSeleccionada'        => $mesaFiltro,
        'historicos'              => ProcesoElectoral::orderBy('anio', 'desc')->get() 
    ]);
    }

    public function detalle(Request $request, Candidato $candidato)
    {
        $user = Auth::user();
        $cantonId = $request->get('canton_id');
        $parroquiaId = $request->get('parroquia_id');
        
        $procesoId = $request->get('proceso_id');
        $procesoActual = $procesoId ? ProcesoElectoral::find($procesoId) : ProcesoElectoral::where('estado', 'activo')->first();

        if (!$procesoActual) abort(500, 'Entorno electoral no definido.');

        if ($user->role === 'admin_provincial' && $cantonId) {
            $pertenece = Canton::where('id', $cantonId)->where('provincia_id', $user->provincia_id)->exists();
            if (!$pertenece) abort(403, 'Acceso geográfico no autorizado.');
        }

        $detalles = DB::table('acta_candidato')
            ->join('actas', 'acta_candidato.acta_id', '=', 'actas.id')
            ->join('mesas', 'actas.mesa_id', '=', 'mesas.id')
            ->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
            ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
            ->join('cantones', 'parroquias.canton_id', '=', 'cantones.id')
            ->where('acta_candidato.candidato_id', $candidato->id)
            ->where('actas.proceso_electoral_id', '=', $procesoActual->id) 
            ->where('actas.estado', '=', 'ingresada')
            ->when($user->role === 'admin_provincial', fn($q) => $q->where('cantones.provincia_id', $user->provincia_id))
            ->when($cantonId, fn($q) => $q->where('parroquias.canton_id', $cantonId))
            ->when($parroquiaId, fn($q) => $q->where('parroquias.id', $parroquiaId))
            ->select(
                'parroquias.nombre as parroquia',
                'recintos.nombre as recinto',
                'mesas.numero as mesa',
                'mesas.genero',
                'acta_candidato.votos'
            )
            ->orderBy('parroquias.nombre')->orderBy('recintos.nombre')->orderBy('mesas.numero')
            ->get();

        return view('resultados_detalle', [
            'candidato'   => $candidato,
            'detalles'    => $detalles,
            'cantonId'    => $cantonId,
            'parroquiaId' => $parroquiaId,
            'proceso'     => $procesoActual
        ]);
    }

    public function generarPDF(Request $request)
    {
        $user = Auth::user();

        $procesoId = $request->get('proceso_id');
        $procesoActual = $procesoId ? ProcesoElectoral::find($procesoId) : ProcesoElectoral::where('estado', 'activo')->first();

        if (!$procesoActual) abort(500, 'Entorno electoral no definido.');
        
        // CORRECCIÓN PREVIA: Uso del namespace directo para evitar el error de Class not found
        $config = \App\Models\JurisdiccionConfig::where('canton_id', $user->canton_id)->first();
        $pestanasVisibles = $this->obtenerPestanasVisibles($user, $config);
        $dignidadSeleccionada = $request->get('dignidad');

        if (!$dignidadSeleccionada || !in_array($dignidadSeleccionada, $pestanasVisibles)) {
            $valorPorDefecto = !empty($pestanasVisibles) ? reset($pestanasVisibles) : 'Alcalde';
            $dignidadSeleccionada = is_array($valorPorDefecto) ? 'Alcalde' : $valorPorDefecto;
        }

        $cantonFiltro = $request->get('canton_id');
        $parroquiaFiltro = $request->get('parroquia_id');
        
        $finalCantonId = ($user->esAdminGeneral() || $user->role === 'admin_provincial') ? $cantonFiltro : $user->canton_id;
        if ($user->role === 'admin_provincial' && $cantonFiltro) {
            $cantonValido = Canton::where('id', $cantonFiltro)->where('provincia_id', $user->provincia_id)->exists();
            $finalCantonId = $cantonValido ? $cantonFiltro : null;
        }

        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        // Query Resultados Candidatos para PDF
        $queryResultados = Candidato::with(['partido'])
            ->where('proceso_electoral_id', '=', $procesoActual->id) 
            ->where('dignidad', '=', (string) $dignidadSeleccionada);

        if ($user->role === 'admin_provincial' && $dignidadSeleccionada === 'Prefecto') {
            $queryResultados->where('provincia_id', $user->provincia_id);
        }

        $queryResultados->when($finalCantonId, function($q) use ($finalCantonId, $dignidadSeleccionada) {
            if (in_array($dignidadSeleccionada, ['Alcalde', 'Concejales'])) {
                return $q->where('canton_id', $finalCantonId);
            }
            return $q;
        })
        ->when($finalParroquiaId, function($q) use ($finalParroquiaId, $dignidadSeleccionada) {
            if ($dignidadSeleccionada === 'Junta Parroquial') {
                return $q->where('parroquia_id', $finalParroquiaId);
            }
            return $q;
        });

        $resultados = $queryResultados->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId, $user, $procesoActual) {
                $query->where('actas.proceso_electoral_id', '=', $procesoActual->id)
                    ->where('actas.estado', '=', 'ingresada') 
                    ->when($user->role === 'admin_provincial', function($q) use ($user) {
                        $q->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('cantones.provincia_id', $user->provincia_id));
                    })
                    ->when($finalCantonId, function($q) use ($finalCantonId) {
                        $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
                    })
                    ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                        $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
                    });
            }], 'acta_candidato.votos')
            ->get()
            ->map(function($candidato) {
                $candidato->total_votos = (int)($candidato->total_votos ?? 0);
                return $candidato;
            })
            ->sortByDesc('total_votos')
            ->values();

        // =========================================================================
        // Query Base con Filtros Acumulativos Estrictos
        // =========================================================================
        $baseTotalesPdf = Acta::where('proceso_electoral_id', '=', $procesoActual->id)
            ->where('dignidad', '=', (string) $dignidadSeleccionada);

        if ($user->role === 'admin_provincial') {
            $baseTotalesPdf->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('cantones.provincia_id', $user->provincia_id));
        }
        if ($finalCantonId) {
            $baseTotalesPdf->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
        }
        if ($finalParroquiaId) {
            $baseTotalesPdf->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
        }

        $totalActasPdf = (clone $baseTotalesPdf)->count();
        
        $totalesLimpiosPdf = (clone $baseTotalesPdf)->where('estado', '=', 'ingresada')
            ->select(
                DB::raw('SUM(votos_blancos) as blancos'), 
                DB::raw('SUM(votos_nulos) as nulos'),
                DB::raw('SUM(ausentismo) as ausentismo')
            )
            ->first();

        // Re-calcular el padrón del PDF para sacar porcentajes exactos
        $padronEscrutadoPdf = DB::table('mesas')
            ->join('actas', 'mesas.id', '=', 'actas.mesa_id')
            ->where('actas.proceso_electoral_id', '=', $procesoActual->id)
            ->where('actas.dignidad', '=', (string) $dignidadSeleccionada)
            ->where('actas.estado', '=', 'ingresada')
            ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                $q->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
                  ->where('recintos.parroquia_id', $finalParroquiaId);
            })
            ->when($finalCantonId && !$finalParroquiaId, function($q) use ($finalCantonId) {
                $q->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
                  ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
                  ->where('parroquias.canton_id', $finalCantonId);
            })
            ->sum('mesas.num_electores');

        $nombreLugar = 'PROVINCIAL';
        if ($finalParroquiaId) {
            $nombreLugar = 'PARROQUIA: ' . Parroquia::find($finalParroquiaId)->nombre;
        } elseif ($finalCantonId) {
            $nombreLugar = 'CANTÓN: ' . Canton::find($finalCantonId)->nombre;
        } elseif ($user->role === 'admin_provincial') {
            $nombreLugar = 'PROVINCIA DE MONITOREO';
        }

        $votosBlancos = (int)($totalesLimpiosPdf->blancos ?? 0);
        $votosNulos = (int)($totalesLimpiosPdf->nulos ?? 0);
        $ausentismo = (int)($totalesLimpiosPdf->ausentismo ?? 0);
        
        $sumaVotosCandidatosPdf = $resultados->sum('total_votos');
        
        // MODIFICACIÓN DE LOGICA ELECTORAL:
        $totalSufragantes = $sumaVotosCandidatosPdf + $votosBlancos + $votosNulos; // Esto da 179
        $totalPadronReal = $totalSufragantes + $ausentismo;                       // Esto da 229

        $porcentajeParticipacionPdf = $padronEscrutadoPdf > 0 ? ($totalSufragantes / $padronEscrutadoPdf) * 100 : 0;
        $porcentajeAusentismoPdf = $padronEscrutadoPdf > 0 ? ($ausentismo / $padronEscrutadoPdf) * 100 : 0;

        $data = [
            'proceso'                 => $procesoActual,
            'resultados'              => $resultados,
            'totalVotosValidos'       => $sumaVotosCandidatosPdf, // Agregado explícito para la tabla inferior
            'totalVotosBlancos'       => $votosBlancos,
            'totalVotosNulos'         => $votosNulos,
            'totalActas'              => $totalActasPdf,
            'totalSufragantes'        => $totalSufragantes,       // Enviado como 179
            'ausentismo'              => $ausentismo,             // Mapeo normal
            'totalAusentismo'         => $ausentismo,             // Mapeo preventivo para la vista
            'totalPadron'             => $totalPadronReal,        // Enviado como 229
            'dignidadSeleccionada'    => $dignidadSeleccionada,
            'fecha'                   => now()->format('d/m/Y H:i'),
            'usuario'                 => $user->name,
            'lugar'                   => $nombreLugar,
            'porcentajeParticipacion' => number_format($porcentajeParticipacionPdf, 1),
            'porcentajeAusentismo'    => number_format($porcentajeAusentismoPdf, 1)
        ];

        $pdf = Pdf::loadView('pdf.reporte-resultados', $data);
        return $pdf->stream('Reporte_Resultados_'.str_replace(' ', '_', $dignidadSeleccionada).'.pdf');
    }
    private function obtenerPestanasVisibles($user, $config)
    {
        if ($user->esAdminGeneral()) {
            return ['Prefecto', 'Alcalde', 'Concejales', 'Junta Parroquial'];
        }

        $pestanas = [];
        if ($user->role === 'admin_provincial') {
            $pestanas[] = 'Prefecto';
            $pestanas[] = 'Alcalde';
        }

        if ($user->esAdminCantonal()) {
            $pestanas[] = 'Alcalde';
            $pestanas[] = 'Concejales';
            if ($config && $config->ver_parroquias) $pestanas[] = 'Junta Parroquial';
        }

        if ($user->esAdminParroquial()) {
            $pestanas[] = 'Junta Parroquial';
            if ($config && $config->ver_alcalde) $pestanas[] = 'Alcalde';
        }

        if ($user->role !== 'admin_provincial') {
            if (($config && $config->ver_provincia) || $user->ver_prefectos) {
                if (!in_array('Prefecto', $pestanas)) array_unshift($pestanas, 'Prefecto');
            }
        }

        return array_unique($pestanas);
    }
}