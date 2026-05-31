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

        // SEGURIDAD: Validación territorial estricta para administradores de provincia
        if ($user->esAdminGeneral()) {
            $finalCantonId = $cantonFiltro;
        } elseif ($user->role === 'admin_provincial') {
            // Si el admin provincial intenta enviar un cantón por formulario, validamos que pertenezca a SU provincia
            if ($cantonFiltro) {
                $cantonValido = Canton::where('id', $cantonFiltro)->where('provincia_id', $user->provincia_id)->exists();
                $finalCantonId = $cantonValido ? $cantonFiltro : null;
            } else {
                $finalCantonId = null; // Carga global de su provincia si no filtra cantón
            }
        } else {
            $finalCantonId = $user->canton_id;
        }
        
        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        // 4. Consulta de Candidatos FILTRADA POR JURISDICCIÓN
        $queryResultados = Candidato::with(['partido'])
            ->where('dignidad', $dignidadSeleccionada);

        // Si es administrador provincial y se está consultando la dignidad de 'Prefecto'
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

        $resultados = $queryResultados->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId, $user) {
                $query->when($user->role === 'admin_provincial', function($q) use ($user) {
                    $q->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('provincia_id', $user->provincia_id));
                })
                ->when($finalCantonId, function($q) use ($finalCantonId) {
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
            ->when($user->role === 'admin_provincial', function($q) use ($user) {
                $q->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('provincia_id', $user->provincia_id));
            })
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
            ->join('cantones', 'parroquias.canton_id', '=', 'cantones.id')
            ->when($user->role === 'admin_provincial', fn($q) => $q->where('cantones.provincia_id', $user->provincia_id))
            ->when($finalCantonId, fn($q) => $q->where('parroquias.canton_id', $finalCantonId))
            ->when($finalParroquiaId, fn($q) => $q->where('parroquias.id', $finalParroquiaId))
            ->count();

        $porcentajeEscrutinio = $totalMesasJurisdiccion > 0 
            ? ($totales->total_actas / $totalMesasJurisdiccion) * 100 
            : 0;

        $votosBlancos = $totales->blancos ?? 0;
        $votosNulos = $totales->nulos ?? 0;
        $sumaVotosCandidatos = $resultados->sum('total_votos');

        // 6. FILTRADO CORREGIDO PARA EL SELECTOR DE CANTONES EN LA VISTA
        if ($user->esAdminGeneral()) {
            $cantonesVisibles = Canton::orderBy('nombre')->get();
        } elseif ($user->role === 'admin_provincial') {
            // Si es Admin Provincial, el selector cargará únicamente los cantones de SU provincia
            $cantonesVisibles = Canton::where('provincia_id', $user->provincia_id)->orderBy('nombre')->get();
        } else {
            $cantonesVisibles = [];
        }

        return view('resultados', [
            'resultados'           => $resultados,
            'totalVotosBlancos'    => $votosBlancos,
            'totalVotosNulos'      => $votosNulos,
            'totalActas'           => $totales->total_actas ?? 0,
            'porcentajeEscrutinio' => number_format($porcentajeEscrutinio, 1),
            'granTotalVotos'       => $sumaVotosCandidatos + $votosBlancos + $votosNulos,
            'dignidadSeleccionada' => $dignidadSeleccionada,
            'pestanasVisibles'     => $pestanasVisibles,
            'cantones'             => $cantonesVisibles,
            'parroquias'           => ($finalCantonId) 
                                      ? Parroquia::where('canton_id', $finalCantonId)->orderBy('nombre')->get() : []
        ]);
    }

    public function detalle(Request $request, Candidato $candidato)
    {
        $user = Auth::user();
        $cantonId = $request->get('canton_id');
        $parroquiaId = $request->get('parroquia_id');

        // Seguridad perimetral en la vista de auditoría detallada
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
        
        // Bloqueo territorial homólogo al index para evitar inyecciones en el PDF
        $finalCantonId = ($user->esAdminGeneral() || $user->role === 'admin_provincial') ? $cantonFiltro : $user->canton_id;
        if ($user->role === 'admin_provincial' && $cantonFiltro) {
            $cantonValido = Canton::where('id', $cantonFiltro)->where('provincia_id', $user->provincia_id)->exists();
            $finalCantonId = $cantonValido ? $cantonFiltro : null;
        }

        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        $queryResultados = Candidato::with(['partido'])
            ->where('dignidad', $dignidadSeleccionada);

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

        $resultados = $queryResultados->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId, $user) {
                $query->when($user->role === 'admin_provincial', function($q) use ($user) {
                    $q->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('provincia_id', $user->provincia_id));
                })
                ->when($finalCantonId, function($q) use ($finalCantonId) {
                    $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
                })
                ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                    $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
                });
            }], 'acta_candidato.votos')
            ->orderByDesc('total_votos')
            ->get();

        $totales = Acta::where('dignidad', $dignidadSeleccionada)
            ->when($user->role === 'admin_provincial', function($q) use ($user) {
                $q->whereHas('mesa.recinto.parroquia.canton', fn($c) => $c->where('provincia_id', $user->provincia_id));
            })
            ->when($finalCantonId, function($q) use ($finalCantonId) {
                $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
            })
            ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
            })
            ->selectRaw('SUM(votos_blancos) as blancos, SUM(votos_nulos) as nulos, COUNT(id) as total_actas')
            ->first();

        $nombreLugar = 'PROVINCIAL';
        if ($finalParroquiaId) {
            $nombreLugar = 'PARROQUIA: ' . Parroquia::find($finalParroquiaId)->nombre;
        } elseif ($finalCantonId) {
            $nombreLugar = 'CANTÓN: ' . Canton::find($finalCantonId)->nombre;
        } elseif ($user->role === 'admin_provincial') {
            $nombreLugar = 'PROVINCIA DE MONITOREO';
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