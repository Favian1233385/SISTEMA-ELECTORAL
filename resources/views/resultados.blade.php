<x-app-layout>
        <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <!-- Título del Módulo e Indicador EN VIVO -->
            <div class="flex items-center">
                <div class="flex items-center bg-red-600 px-3 py-1 rounded-lg mr-4 shadow-[0_0_10px_rgba(220,38,38,0.5)]">
                    <span class="relative flex h-3 w-3 mr-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                    </span>
                    <span class="text-white text-xs font-black uppercase tracking-widest">En Vivo</span>
                </div>
                
                <h2 class="font-black text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Resultados') }}: <span class="text-blue-600 dark:text-blue-400">{{ is_array($dignidadSeleccionada) ? 'General' : $dignidadSeleccionada }}</span>
                </h2>
            </div>

            <!-- Selector Único de Proceso y Botón de Exportación -->
            <form id="formProceso" method="GET" action="{{ route('resultados.index') }}" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="dignidad" value="{{ is_array($dignidadSeleccionada) ? '' : $dignidadSeleccionada }}">
                <input type="hidden" name="canton_id" value="{{ request('canton_id') }}">
                <input type="hidden" name="parroquia_id" value="{{ request('parroquia_id') }}">
                <input type="hidden" name="recinto_id" value="{{ request('recinto_id') }}">
                <input type="hidden" name="mesa_id" value="{{ request('mesa_id') }}">

                <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <label for="proceso_id" class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Proceso:</label>
                    <select name="proceso_id" id="proceso_id" onchange="document.getElementById('formProceso').submit();" 
                        class="border-none bg-transparent text-xs font-bold text-gray-700 dark:text-gray-200 p-0 pr-6 focus:ring-0 cursor-pointer">
                        @foreach($historicos as $proc)
                            <option value="{{ $proc->id }}" {{ $proceso->id == $proc->id ? 'selected' : '' }}>
                                {{ $proc->nombre }} ({{ $proc->anio }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('resultados.pdf', ['proceso_id' => $proceso->id, 'dignidad' => (string) (is_array($dignidadSeleccionada) ? '' : $dignidadSeleccionada), 'canton_id' => request('canton_id'), 'parroquia_id' => request('parroquia_id'), 'recinto_id' => request('recinto_id'), 'mesa_id' => request('mesa_id')]) }}" 
                   target="_blank" 
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl font-bold text-xs text-gray-700 dark:text-gray-200 uppercase hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z" />
                    </svg>
                    Exportar PDF
                </a>
            </form>
        </div>
    </x-slot>

    <div class="py-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Tabs de Dignidades Electorales --}}
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                <ul class="flex flex-nowrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                    @foreach($pestanasVisibles as $pestana)
                        <li class="me-2">
                            <a href="{{ route('resultados.index', ['proceso_id' => $proceso->id, 'dignidad' => $pestana, 'canton_id' => request('canton_id'), 'parroquia_id' => request('parroquia_id'), 'recinto_id' => request('recinto_id'), 'mesa_id' => request('mesa_id')]) }}" 
                               class="inline-flex p-4 border-b-2 rounded-t-lg transition-all {{ $dignidadSeleccionada === $pestana ? 'text-blue-600 border-blue-600 font-bold bg-white dark:bg-gray-800 shadow-sm' : 'border-transparent hover:text-gray-600 hover:border-gray-300' }}">
                                {{ $pestana }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- FILA DE JURISDICCIÓN EN CASCADA COMPLETA Y ESCRUTINIO GLOBAL -->       
            <form id="formFiltrosJurisdiccion" method="GET" action="{{ route('resultados.index') }}" class="w-full mb-6">
                <input type="hidden" name="proceso_id" value="{{ $proceso->id }}">
                <input type="hidden" name="dignidad" value="{{ is_array($dignidadSeleccionada) ? '' : $dignidadSeleccionada }}">

                <div class="flex flex-col gap-4">
                    <!-- 1. Tarjeta de Jurisdicción Territorial -->
                    <div class="w-full bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Jurisdicción Territorial</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Cantón -->
                            <div>
                                <label for="canton_id" class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Cantón</label>
                                <select name="canton_id" id="canton_id" onchange="document.getElementById('formFiltrosJurisdiccion').submit();" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-700 dark:text-gray-200 py-1.5 focus:ring-blue-500">
                                    <option value="">Toda la Provincia</option>
                                    @foreach($cantones as $canton)
                                        <option value="{{ $canton->id }}" {{ request('canton_id') == $canton->id ? 'selected' : '' }}>{{ $canton->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Parroquia -->
                            <div>
                                <label for="parroquia_id" class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Parroquia / Zona</label>
                                <select name="parroquia_id" id="parroquia_id" onchange="document.getElementById('formFiltrosJurisdiccion').submit();" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-700 dark:text-gray-200 py-1.5 focus:ring-blue-500"
                                    {{ !request('canton_id') ? 'disabled' : '' }}>
                                    <option value="">Todas las Parroquias</option>
                                    @foreach($parroquias as $parroquia)
                                        <option value="{{ $parroquia->id }}" {{ request('parroquia_id') == $parroquia->id ? 'selected' : '' }}>{{ $parroquia->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Recinto -->
                            <div>
                                <label for="recinto_id" class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Recinto Electoral</label>
                                <select name="recinto_id" id="recinto_id" onchange="document.getElementById('formFiltrosJurisdiccion').submit();" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-700 dark:text-gray-200 py-1.5 focus:ring-blue-500"
                                    {{ !request('parroquia_id') ? 'disabled' : '' }}>
                                    <option value="">Todos los Recintos</option>
                                    @isset($recintos)
                                        @foreach($recintos as $recinto)
                                            <option value="{{ $recinto->id }}" {{ request('recinto_id') == $recinto->id ? 'selected' : '' }}>🏫 {{ $recinto->nombre }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            <!-- Mesa / Junta -->
                            <div>
                                <label for="mesa_id" class="block text-[9px] font-bold text-gray-400 uppercase mb-1">Mesa / Junta</label>
                                <select name="mesa_id" id="mesa_id" onchange="document.getElementById('formFiltrosJurisdiccion').submit();" 
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-700 dark:text-gray-200 py-1.5 focus:ring-blue-500"
                                    {{ !request('recinto_id') ? 'disabled' : '' }}>
                                    <option value="">Todas las Mesas</option>
                                    @isset($mesas)
                                        @foreach($mesas as $mesa)
                                            <option value="{{ $mesa->id }}" {{ request('mesa_id') == $mesa->id ? 'selected' : '' }}>📋 Nº {{ $mesa->numero }} ({{ $mesa->genero }})</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Bloque de Escrutinio Global -->
                    <div class="w-full bg-white dark:bg-gray-800 px-5 py-3 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">Escrutinio Global:</span>
                            <span class="text-base font-black text-blue-600 dark:text-blue-400">{{ number_format($porcentajeEscrutinio, 2) }}%</span>
                        </div>
                        
                        <!-- Barra de progreso -->
                        <div class="flex-1 max-w-xl bg-gray-100 dark:bg-gray-700 rounded-full h-3 relative">
                            <div class="bg-blue-600 h-3 rounded-full shadow-sm transition-all duration-500" style="width: {{ $porcentajeEscrutinio }}%"></div>
                        </div>
                        
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 italic mt-1 sm:mt-0">* Datos basados en actas ingresadas de esta demarcación.</p>
                    </div>
                </div>
            </form>

            {{-- Sección de Tendencia de Votación --}}
            <div class="w-full bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 mb-6">
                @php
                    // Ordenamos de forma segura. NOTA: Lo ideal es que $resultados ya venga ordenado desde el Controller.
                    $candidatosOrdenados = $resultados->sortByDesc(function($c) {
                        return (int)($c->total_votos ?? 0);
                    })->values();

                    // Estandarizamos el denominador usando $granTotalVotos para evitar discrepancias gráficas
                    $denominadorVotos = $granTotalVotos > 0 ? $granTotalVotos : $candidatosOrdenados->sum('total_votos');
                @endphp

                @if($candidatosOrdenados->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400 font-bold italic">
                        No existen candidatos registrados o datos para esta jurisdicción.
                    </div>
                @else
                    {{-- BLOQUE 1: TARJETAS DE LOS LÍDERES --}}
                    {{-- BLOQUE 1: TARJETA UNIFICADA (FOTOS MAXIMIZADAS + GRÁFICO REAL CON CHART.JS) --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 mb-6">
                        
                        {{-- Contenedor Principal --}}
                        <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 2rem; width: 100%; align-items: center;">
                            
                            {{-- LADO IZQUIERDO: Espacio para los 2 Líderes --}}
                            <div style="flex: 1.2; min-width: 340px; display: flex; flex-direction: column; gap: 1rem;">
                                <div class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 pb-2">
                                    @if($denominadorVotos > 0)
                                        Líderes de Escrutinio (Tiempo Real)
                                    @else
                                        Postulantes de la Demarcación (Sin Votos Ingresados)
                                    @endif
                                </div>

                                {{-- Contenedor horizontal de candidatos --}}
                                <div style="display: flex; flex-direction: row; gap: 1.5rem; width: 100%; align-items: flex-start;">
                                    
                                    @foreach($candidatosOrdenados->take(2) as $index => $candidato)
                                        @php
                                            $votos = (int)($candidato->total_votos ?? 0);
                                            $porcentaje = $denominadorVotos > 0 ? round(($votos * 100) / $denominadorVotos, 2) : 0;
                                        @endphp
                                        
                                        {{-- Tarjeta Individual Vertical --}}
                                        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-900/20" 
                                            style="flex: 1; display: flex; flex-direction: column; align-items: center; padding: 1.25rem; text-align: center; min-width: 0;">
                                            
                                            {{-- FOTO AMPLIADA A 150PX (Gran impacto visual y simétrica) --}}
                                            <div class="flex-shrink-0 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 shadow-sm mb-4" 
                                                style="width: 150px; height: 150px; min-width: 150px; min-height: 150px;">
                                                @if(!empty($candidato->foto))
                                                    <img src="{{ asset($candidato->foto) }}" alt="{{ $candidato->nombre }}" 
                                                        style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                                                @else
                                                    <div style="height: 100%; display: flex; align-items: center; justify-content: center;" class="text-gray-400">
                                                        <svg style="width: 3.5rem; height: 3.5rem;" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Datos estructurados ABAJO --}}
                                            <div style="width: 100%; min-width: 0;">
                                                <h4 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight truncate" style="margin: 0;" title="{{ $candidato->nombre }}">
                                                    {{ $candidato->nombre }}
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wide truncate" style="margin: 3px 0 0 0;">
                                                    {{ $candidato->partido->nombre ?? 'Lista / Alianza' }}
                                                </p>
                                                
                                                {{-- Votos y porcentajes --}}
                                                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.06); display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                                                        Votos: <span class="font-black text-gray-900 dark:text-gray-200">{{ number_format($votos) }}</span>
                                                    </span>
                                                    <span class="text-xs font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-3 py-1 rounded-md mt-0.5" style="display: inline-block;">
                                                        {{ number_format($porcentaje, 2) }}%
                                                    </span>
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach

                                </div>
                            </div>

                            {{-- LADO DERECHO: Canvas del Gráfico Real (Equilibrado y alineado) --}}
                            <div style="flex: 1; min-width: 320px; display: flex; flex-direction: column; items-center; justify-content: center; border-left: 1px solid rgba(0,0,0,0.08); padding-left: 2rem; min-height: 260px;">
                                <div style="width: 100%; max-width: 280px; margin: 0 auto; position: relative;">
                                    <canvas id="graficoLideres"></canvas>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- BLOQUE 2: LISTADO COMPACTO (Puestos 3 en adelante) --}}
                    @if($candidatosOrdenados->count() > 2)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-3">Otros Candidatos</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($candidatosOrdenados->slice(2) as $index => $candidato)
                                    @php
                                        $votos = (int)($candidato->total_votos ?? 0);
                                        $porcentaje = $denominadorVotos > 0 ? round(($votos * 100) / $denominadorVotos, 2) : 0;
                                    @endphp
                                    <div class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/70">
                                        <div class="min-w-0 flex items-center space-x-2">
                                            <span class="text-xs font-bold text-gray-400 w-4">{{ $index + 3 }}</span>
                                            <div class="truncate">
                                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate uppercase leading-tight">{{ $candidato->nombre }}</p>
                                                <p class="text-[9px] text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ $candidato->partido->nombre ?? 'Lista / Alianza' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right flex-shrink-0 ml-2">
                                            <span class="text-xs font-black text-blue-600 dark:text-blue-400 block">
                                                {{ number_format($porcentaje, 2) }}%
                                            </span>
                                            <span class="text-[9px] text-gray-400 block font-mono">{{ number_format($votos) }} v.</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Cards de Resumen Estadístico --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-600 p-5 rounded-2xl shadow-sm text-white flex flex-col justify-between min-h-[110px]">
                    <p class="text-[10px] uppercase font-bold opacity-80 tracking-wider">Votos Válidos</p>
                    <p class="text-3xl font-black tracking-tight mt-1">{{ number_format($granTotalVotos) }}</p>
                </div>
                <div class="bg-emerald-500 p-5 rounded-2xl shadow-sm text-black flex flex-col justify-between min-h-[110px]">
                    <p class="text-[10px] uppercase font-bold opacity-80 tracking-wider">Actas Procesadas</p>
                    <p class="text-3xl font-black tracking-tight mt-1">{{ number_format($totalActas) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between min-h-[110px]">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider">Votos Blancos</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white tracking-tight mt-1">{{ number_format($totalVotosBlancos) }}</p>
                </div>
                
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between min-h-[110px]">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider">Votos Nulos</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white tracking-tight mt-1">{{ number_format($totalVotosNulos) }}</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
                    <span class="text-sm text-gray-500 font-bold uppercase block mb-2">Ausentismo</span>
                    <span class="text-2xl font-bold text-gray-800">
                        {{ isset($totalAusentismo) ? number_format($totalAusentismo) : 0 }}
                    </span>
                </div>
            </div>

            {{-- Tabla de Posiciones de Candidatos --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-[10px] uppercase font-bold text-gray-400 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Ranking / Candidato</th>
                            <th class="px-6 py-4">Organización Política</th>
                            <th class="px-6 py-4 text-right">Votos</th>
                            <th class="px-6 py-4 text-right">Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($candidatosOrdenados as $index => $candidato)
                            @php 
                                $porc = $denominadorVotos > 0 ? ($candidato->total_votos / $denominadorVotos) * 100 : 0; 
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/50 transition-colors text-gray-700 dark:text-gray-300">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="w-6 h-6 rounded-full {{ $index == 0 ? 'bg-yellow-400 text-black' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }} flex items-center justify-center text-[10px] font-bold mr-3">
                                            {{ $index + 1 }}
                                        </span>
                                        <a href="{{ route('resultados.detalle', ['candidato' => $candidato->id, 'canton_id' => request('canton_id'), 'parroquia_id' => request('parroquia_id'), 'recinto_id' => request('recinto_id'), 'mesa_id' => request('mesa_id'), 'proceso_id' => $proceso->id]) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $candidato->nombre }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs italic text-gray-500 dark:text-gray-400">{{ $candidato->partido->nombre ?? 'Alianza / Independiente' }}</td>
                                <td class="px-6 py-4 text-right font-mono font-bold">{{ number_format($candidato->total_votos) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end">
                                        <div class="w-24 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mr-3 hidden md:block">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $porc }}%"></div>
                                        </div>
                                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($porc, 2) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-10 text-center text-gray-400 dark:text-gray-500 italic">
                                    No hay candidatos o actas registradas para este proceso electoral.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // =========================================================================
        // 1. PROCESAMIENTO DE DATOS Y RENDERIZADO DEL GRÁFICO
        // =========================================================================
        const candidatosCard = {!! json_encode($candidatosOrdenados->map(fn($c) => ['nombre' => $c->nombre, 'votos' => (int)($c->total_votos ?? 0)])->toArray()) !!};
        const totalVotos = candidatosCard.reduce((a, b) => a + b.votos, 0);
        
        let labelsGrafico = [];
        let datosGrafico = [];
        let coloresGrafico = [];

        if (totalVotos === 0) {
            labelsGrafico = ['Sin votos registrados'];
            datosGrafico = [1];
            coloresGrafico = [document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'];
        } else {
            const maxVisibles = 4;
            if (candidatosCard.length <= maxVisibles + 1) {
                labelsGrafico = candidatosCard.map(c => c.nombre);
                datosGrafico = candidatosCard.map(c => c.votos);
            } else {
                const topCandidatos = candidatosCard.slice(0, maxVisibles);
                labelsGrafico = topCandidatos.map(c => c.nombre);
                datosGrafico = topCandidatos.map(c => c.votos);
                
                const resto = candidatosCard.slice(maxVisibles);
                const votosResto = resto.reduce((a, b) => a + b.votos, 0);
                
                if (votosResto > 0) {
                    labelsGrafico.push('Otros Candidatos');
                    datosGrafico.push(votosResto);
                }
            }
            coloresGrafico = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#6B7280'];
        }

        const ctx = document.getElementById('graficoLideres').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labelsGrafico,
                datasets: [{
                    data: datosGrafico,
                    backgroundColor: coloresGrafico,
                    borderWidth: totalVotos === 0 ? 0 : 2,
                    borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        display: totalVotos > 0,
                        labels: {
                            boxWidth: 10,
                            font: { size: 10, weight: '600' },
                            color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#4b5563'
                        }
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                if (totalVotos === 0) return ' Esperando ingreso de actas...';
                                return ` ${context.label}: ${context.raw} votos`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // =========================================================================
        // 2. LÓGICA DE AUTO-RECARGA INTELIGENTE (CORREGIDA PARA PRODUCCIÓN)
        // =========================================================================
        const actasActualesEnPantalla = {{ (int)$totalActas }}; 
        const INTERVALO_RECARGA = 30000; // 30 segundos

        function verificarNuevosIngresos() {
            const urlVerificacion = new URL(window.location.href);
            
            // CORRECCIÓN 1: .set() evita la duplicación infinita de parámetros en la URL
            urlVerificacion.searchParams.set('solo_verificar_cambios', '1');
            
            // CORRECCIÓN 2: Forzar la consistencia de la dignidad consultada en segundo plano
            urlVerificacion.searchParams.set('dignidad', '{{ $dignidadSeleccionada }}');
            
            fetch(urlVerificacion.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data && typeof data.total_actas !== 'undefined') {
                    const totalActasServidor = parseInt(data.total_actas);
                    
                    // Si el conteo del servidor difiere de lo desplegado en la pantalla, actualizamos
                    if (totalActasServidor !== actasActualesEnPantalla) {
                        window.location.reload();
                    } else {
                        setTimeout(verificarNuevosIngresos, INTERVALO_RECARGA);
                    }
                } else {
                    setTimeout(verificarNuevosIngresos, INTERVALO_RECARGA);
                }
            })
            .catch(error => {
                console.error("Error en la consulta ligera de actas:", error);
                setTimeout(verificarNuevosIngresos, INTERVALO_RECARGA);
            });
        }

        // Inicializar el ciclo de monitoreo
        setTimeout(verificarNuevosIngresos, INTERVALO_RECARGA);
    });
    </script>
</x-app-layout>