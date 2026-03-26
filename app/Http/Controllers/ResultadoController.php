<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Acta;
use App\Models\JurisdiccionConfig;
use App\Models\Canton; 
use App\Models\Parroquia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request; 
use Barryvdh\DomPDF\Facade\Pdf;

class ResultadoController extends Controller
{
    public function index(Request $request) 
    {
        $user = Auth::user();
        
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

        if ($user->esAdminGeneral() || $user->role === 'admin_provincial') {
            $finalCantonId = $cantonFiltro;
        } else {
            $finalCantonId = $user->canton_id;
        }
        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        // 4. Consulta de Candidatos FILTRADA POR JURISDICCIÓN
        $queryResultados = Candidato::with(['partido'])
            ->where('dignidad', $dignidadSeleccionada);

        // Aplicar filtro de jurisdicción al candidato para que no salgan candidatos de otros cantones
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

        $resultados = $queryResultados->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId) {
                $query->when($finalCantonId, function($q) use ($finalCantonId) {
                    $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
                })
                ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                    $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
                });
            }], 'acta_candidato.votos')
            ->orderByDesc('total_votos')
            ->get();
            
        // 5. Cálculo de Totales y ESCRUTINIO REAL
        $totales = Acta::where('dignidad', $dignidadSeleccionada)
            ->when($finalCantonId, function($q) use ($finalCantonId) {
                $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
            })
            ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
            })
            ->selectRaw('SUM(votos_blancos) as blancos, SUM(votos_nulos) as nulos, COUNT(id) as total_actas')
            ->first();

        // Calcular cuántas mesas existen en esta zona específica para el porcentaje global
        $totalMesasJurisdiccion = DB::table('mesas')
            ->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
            ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
            ->when($finalCantonId, fn($q) => $q->where('parroquias.canton_id', $finalCantonId))
            ->when($finalParroquiaId, fn($q) => $q->where('parroquias.id', $finalParroquiaId))
            ->count();

        $porcentajeEscrutinio = $totalMesasJurisdiccion > 0 
            ? ($totales->total_actas / $totalMesasJurisdiccion) * 100 
            : 0;

        $votosBlancos = $totales->blancos ?? 0;
        $votosNulos = $totales->nulos ?? 0;
        $sumaVotosCandidatos = $resultados->sum('total_votos');

        return view('resultados', [
            'resultados'           => $resultados,
            'totalVotosBlancos'    => $votosBlancos,
            'totalVotosNulos'      => $votosNulos,
            'totalActas'           => $totales->total_actas ?? 0,
            'porcentajeEscrutinio' => number_format($porcentajeEscrutinio, 1),
            'granTotalVotos'       => $sumaVotosCandidatos + $votosBlancos + $votosNulos,
            'dignidadSeleccionada' => $dignidadSeleccionada,
            'pestanasVisibles'     => $pestanasVisibles,
            'cantones'             => ($user->esAdminGeneral() || $user->role === 'admin_provincial') 
                                      ? Canton::orderBy('nombre')->get() : [],
            'parroquias'           => ($finalCantonId) 
                                      ? Parroquia::where('canton_id', $finalCantonId)->orderBy('nombre')->get() : []
        ]);
    }

    public function detalle(Request $request, Candidato $candidato)
    {
        $cantonId = $request->get('canton_id');
        $parroquiaId = $request->get('parroquia_id');

        $detalles = DB::table('acta_candidato')
            ->join('actas', 'acta_candidato.acta_id', '=', 'actas.id')
            ->join('mesas', 'actas.mesa_id', '=', 'mesas.id')
            ->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
            ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
            ->where('acta_candidato.candidato_id', $candidato->id)
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
            'candidato' => $candidato,
            'detalles'  => $detalles,
            'cantonId'  => $cantonId,
            'parroquiaId' => $parroquiaId
        ]);
    }

    public function generarPDF(Request $request)
    {
        $user = Auth::user();
        $config = JurisdiccionConfig::where('canton_id', $user->canton_id)->first();
        $pestanasVisibles = $this->obtenerPestanasVisibles($user, $config);
        $dignidadSeleccionada = $request->get('dignidad');

        if (!$dignidadSeleccionada || !in_array($dignidadSeleccionada, $pestanasVisibles)) {
            $valorPorDefecto = !empty($pestanasVisibles) ? reset($pestanasVisibles) : 'Alcalde';
            $dignidadSeleccionada = is_array($valorPorDefecto) ? 'Alcalde' : $valorPorDefecto;
        }

        $cantonFiltro = $request->get('canton_id');
        $parroquiaFiltro = $request->get('parroquia_id');
        $finalCantonId = ($user->esAdminGeneral() || $user->role === 'admin_provincial') ? $cantonFiltro : $user->canton_id;
        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        // CONSULTA DE RESULTADOS FILTRADA PARA EL PDF (Igual al index)
        $queryResultados = Candidato::with(['partido'])
            ->where('dignidad', $dignidadSeleccionada);

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

        $resultados = $queryResultados->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId) {
                $query->when($finalCantonId, function($q) use ($finalCantonId) {
                    $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
                })
                ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                    $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
                });
            }], 'acta_candidato.votos')
            ->orderByDesc('total_votos')
            ->get();

        $totales = Acta::where('dignidad', $dignidadSeleccionada)
            ->when($finalCantonId, function($q) use ($finalCantonId) {
                $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
            })
            ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
            })
            ->selectRaw('SUM(votos_blancos) as blancos, SUM(votos_nulos) as nulos, COUNT(id) as total_actas')
            ->first();

        // Determinar nombre del lugar para el reporte
        $nombreLugar = 'PROVINCIAL';
        if ($finalParroquiaId) {
            $nombreLugar = 'PARROQUIA: ' . Parroquia::find($finalParroquiaId)->nombre;
        } elseif ($finalCantonId) {
            $nombreLugar = 'CANTÓN: ' . Canton::find($finalCantonId)->nombre;
        }

        $data = [
            'resultados' => $resultados,
            'totalVotosBlancos' => $totales->blancos ?? 0,
            'totalVotosNulos' => $totales->nulos ?? 0,
            'totalActas' => $totales->total_actas ?? 0,
            'granTotalVotos' => $resultados->sum('total_votos') + ($totales->blancos ?? 0) + ($totales->nulos ?? 0),
            'dignidadSeleccionada' => $dignidadSeleccionada,
            'fecha' => now()->format('d/m/Y H:i'),
            'usuario' => $user->name,
            'lugar' => $nombreLugar
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