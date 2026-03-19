<?php

namespace App\Http\Controllers;

use App\Models\Candidato;
use App\Models\Acta;
use App\Models\JurisdiccionConfig;
use App\Models\Canton; 
use App\Models\Parroquia;
use Illuminate\Support\Facades\Auth;
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
            $dignidadSeleccionada = !empty($pestanasVisibles) ? $pestanasVisibles : 'Alcalde';
        }

        // 3. Definir Jurisdicción de consulta
        $cantonFiltro = $request->get('canton_id');
        $parroquiaFiltro = $request->get('parroquia_id');

        // Lógica de jerarquía de filtros por Rol: 
        // Si es Admin General o Provincial, el filtro manual manda. 
        if ($user->esAdminGeneral() || $user->role === 'admin_provincial') {
            $finalCantonId = $cantonFiltro;
        } else {
            $finalCantonId = $user->canton_id;
        }

        // El filtro de parroquia siempre es el del request, a menos que sea Admin Parroquial (fijo)
        $finalParroquiaId = $user->esAdminParroquial() ? $user->parroquia_id : $parroquiaFiltro;

        /**
         * AJUSTE PARA DESGLOSE (DRILL-DOWN):
         * Hemos comentado la anulación de filtros para Prefecto.
         * Ahora, si seleccionas Prefecto y luego un Cantón, verás los votos 
         * del Prefecto en ese Cantón específico.
         */
        /* if ($dignidadSeleccionada === 'Prefecto') {
            $finalCantonId = null;
            $finalParroquiaId = null;
        } 
        */

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

        // Valores por defecto en caso de que no existan actas aún
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
            
            // Cantones disponibles para General y Provincial
            'cantones'             => ($user->esAdminGeneral() || $user->role === 'admin_provincial') 
                                      ? Canton::orderBy('nombre')->get() 
                                      : [],
                                      
            // Carga dinámica de parroquias según el cantón seleccionado
            'parroquias'           => ($finalCantonId) 
                                      ? Parroquia::where('canton_id', $finalCantonId)->orderBy('nombre')->get() 
                                      : []
        ]);
    }

    /**
     * Define qué pestañas puede ver el usuario según su rol y la configuración de jurisdicción.
     */
    private function obtenerPestanasVisibles($user, $config)
    {
        if ($user->esAdminGeneral()) {
            return ['Prefecto', 'Alcalde', 'Concejales', 'Junta Parroquial'];
        }

        $pestanas = [];

        // Admin Provincial ve Prefectos por defecto y Alcaldes
        if ($user->role === 'admin_provincial') {
            $pestanas[] = 'Prefecto';
            $pestanas[] = 'Alcalde';
        }

        if ($user->esAdminCantonal()) {
            $pestanas[] = 'Alcalde';
            $pestanas[] = 'Concejales';
            if ($config && $config->ver_parroquias) {
                $pestanas[] = 'Junta Parroquial';
            }
        }

        if ($user->esAdminParroquial()) {
            $pestanas[] = 'Junta Parroquial';
            if ($config && $config->ver_alcalde) {
                $pestanas[] = 'Alcalde';
            }
        }

        // Acceso a Prefectura por configuración SaaS para roles menores
        if ($user->role !== 'admin_provincial') {
            if (($config && $config->ver_provincia) || $user->ver_prefectos) {
                if (!in_array('Prefecto', $pestanas)) {
                    array_unshift($pestanas, 'Prefecto');
                }
            }
        }

        return array_unique($pestanas);
    }
}