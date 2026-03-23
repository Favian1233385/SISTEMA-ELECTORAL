<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Acta;
use App\Models\JurisdiccionConfig;
use App\Models\Canton; 
use App\Models\Parroquia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\DB; // Añadido para consultas complejas
use Illuminate\Http\Request; 

class ResultadoController extends Controller
{
    public function index(Request $request) 
    {
        $user = Auth::user();
        
        // 1. Obtener pestañas según Rol y Configuración
        $config = JurisdiccionConfig::where('canton_id', $user->canton_id)->first();
        $pestanasVisibles = $this->obtenerPestanasVisibles($user, $config);

       // 2. Garantizar valor para la dignidad (Rescate automático)
        $dignidadSeleccionada = $request->get('dignidad');

        if (!$dignidadSeleccionada || !in_array($dignidadSeleccionada, $pestanasVisibles)) {
            // Forzamos la obtención del primer elemento como string
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

        // 4. Consulta de Candidatos y Votos con Sumatoria Agregada
        $resultados = Candidato::with(['partido'])
            ->where('dignidad', $dignidadSeleccionada)
            ->withSum(['actas as total_votos' => function($query) use ($finalCantonId, $finalParroquiaId) {
                $query->when($finalCantonId, function($q) use ($finalCantonId) {
                    $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
                })
                ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                    $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
                });
            }], 'acta_candidato.votos')
            ->orderByDesc('total_votos')
            ->get();

        // 5. Cálculo de Totales (Blancos, Nulos, Cantidad de Actas)
        $totales = Acta::where('dignidad', $dignidadSeleccionada)
            ->when($finalCantonId, function($q) use ($finalCantonId) {
                $q->whereHas('mesa.recinto.parroquia', fn($p) => $p->where('canton_id', $finalCantonId));
            })
            ->when($finalParroquiaId, function($q) use ($finalParroquiaId) {
                $q->whereHas('mesa.recinto', fn($r) => $r->where('parroquia_id', $finalParroquiaId));
            })
            ->selectRaw('SUM(votos_blancos) as blancos, SUM(votos_nulos) as nulos, COUNT(id) as total_actas')
            ->first();

        $votosBlancos = $totales->blancos ?? 0;
        $votosNulos = $totales->nulos ?? 0;
        $sumaVotosCandidatos = $resultados->sum('total_votos');

        // 6. Retorno consolidado a la Vista
        return view('resultados', [
            'resultados'           => $resultados,
            'totalVotosBlancos'    => $votosBlancos,
            'totalVotosNulos'      => $votosNulos,
            'totalActas'           => $totales->total_actas ?? 0,
            'granTotalVotos'       => $sumaVotosCandidatos + $votosBlancos + $votosNulos,
            'dignidadSeleccionada' => $dignidadSeleccionada,
            'pestanasVisibles'     => $pestanasVisibles,
            'cantones'             => ($user->esAdminGeneral() || $user->role === 'admin_provincial') 
                                      ? Canton::orderBy('nombre')->get() : [],
            'parroquias'           => ($finalCantonId) 
                                      ? Parroquia::where('canton_id', $finalCantonId)->orderBy('nombre')->get() : []
        ]);
    }

    /**
     * Muestra el desglose de votos por Recinto y Mesa para un candidato.
     */
    public function detalle(Candidato $candidato)
    {
        // Consultamos los votos detallados usando joins
        $detalles = DB::table('acta_candidato')
            ->join('actas', 'acta_candidato.acta_id', '=', 'actas.id')
            ->join('mesas', 'actas.mesa_id', '=', 'mesas.id')
            ->join('recintos', 'mesas.recinto_id', '=', 'recintos.id')
            ->join('parroquias', 'recintos.parroquia_id', '=', 'parroquias.id')
            ->where('acta_candidato.candidato_id', $candidato->id)
            ->select(
                'parroquias.nombre as parroquia',
                'recintos.nombre as recinto',
                'mesas.numero as mesa',
                'mesas.genero',
                'acta_candidato.votos'
            )
            ->orderBy('parroquias.nombre')
            ->orderBy('recintos.nombre')
            ->orderBy('mesas.numero')
            ->get();

        return view('resultados_detalle', [
            'candidato' => $candidato,
            'detalles'  => $detalles
        ]);
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