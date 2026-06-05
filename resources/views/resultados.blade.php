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

                <a href="{{ route('resultados.pdf', ['proceso_id' => $proceso->id, 'dignidad' => (string) $dignidadSeleccionada, 'canton_id' => request('canton_id'), 'parroquia_id' => request('parroquia_id'), 'recinto_id' => request('recinto_id'), 'mesa_id' => request('mesa_id')]) }}" 
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
            
            {{-- Tabs de Dignidades Electorales (Limpio, sin código roto) --}}
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
                    
                    <!-- 1. Tarjeta de Jurisdicción Territorial Estrictamente Horizontal -->
                    <div class="w-full bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Jurisdicción Territorial</p>
                        
                        <!-- Grid forzado a 4 columnas horizontales a partir de pantallas pequeñas -->
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

                    <!-- 2. Bloque de Escrutinio Global (Con salto de línea, optimizado y compacto) -->
                    <div class="w-full bg-white dark:bg-gray-800 px-5 py-3 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">Escrutinio Global:</span>
                            <span class="text-base font-black text-blue-600 dark:text-blue-400">{{ $porcentajeEscrutinio }}%</span>
                        </div>
                        
                        <!-- Barra de progreso horizontal fluida -->
                        <div class="flex-1 max-w-xl bg-gray-100 dark:bg-gray-700 rounded-full h-3 relative">
                            <div class="bg-blue-600 h-3 rounded-full shadow-sm transition-all duration-500" style="width: {{ $porcentajeEscrutinio }}%"></div>
                        </div>
                        
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 italic mt-1 sm:mt-0">* Datos basados en actas ingresadas de esta demarcación.</p>
                    </div>

                </div>
            </form>

            {{-- Sección de Tendencia de Votación Profesional --}}
            <div class="w-full bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-base font-black text-gray-800 dark:text-white uppercase tracking-wider">
                        | Tendencia de Votación
                    </h3>
                    <span class="text-[10px] bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-3 py-1 rounded-full font-bold uppercase tracking-wider">Cifras en Votos</span>
                </div>
                <div class="h-[380px] w-full">
                    <canvas id="chartResultados"></canvas>
                </div>
            </div>

            {{-- Cards de Resumen Estadístico Uniformes --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-600 p-5 rounded-2xl shadow-sm text-white">
                    <p class="text-[10px] uppercase font-bold opacity-80 tracking-wider">Votos Válidos</p>
                    <p class="text-3xl font-black tracking-tight mt-1">{{ number_format($granTotalVotos) }}</p>
                </div>
                <div class="bg-emerald-500 p-5 rounded-2xl shadow-sm text-white">
                    <p class="text-[10px] uppercase font-bold opacity-80 tracking-wider">Actas Procesadas</p>
                    <p class="text-3xl font-black tracking-tight mt-1">{{ $totalActas }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider">Votos Blancos</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white tracking-tight mt-1">{{ number_format($totalVotosBlancos) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider">Votos Nulos</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white tracking-tight mt-1">{{ number_format($totalVotosNulos) }}</p>
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
                        @forelse($resultados as $index => $candidato)
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
                                <td class="px-6 py-4 text-xs italic text-gray-500 dark:text-gray-400">{{ $candidato->partido->nombre ?? 'Alianza' }}</td>
                                <td class="px-6 py-4 text-right font-mono font-bold">{{ number_format($candidato->total_votos) }}</td>
                                <td class="px-6 py-4">
                                    @php $porc = $granTotalVotos > 0 ? ($candidato->total_votos / $granTotalVotos) * 100 : 0; @endphp
                                    <div class="flex items-center justify-end">
                                        <div class="w-24 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mr-3 hidden md:block">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $porc }}%"></div>
                                        </div>
                                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($porc, 2) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-10 text-center text-gray-400 dark:text-gray-500 italic">No hay actas o datos escrutados en esta demarcación.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Inicialización del Gráfico con Chart.js --}}   
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('chartResultados').getContext('2d');
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#4b5563';

            const etiquetasCandidatos = {!! json_encode($resultados->pluck('nombre')->toArray()) !!};
            const datosVotos = {!! json_encode($resultados->pluck('total_votos')->map(fn($v) => (int)($v ?? 0))->toArray()) !!};

            if (etiquetasCandidatos.length === 0) {
                ctx.font = "14px sans-serif";
                ctx.fillStyle = textColor;
                ctx.textAlign = "center";
                ctx.fillText("Sin tendencias disponibles para esta jurisdicción.", ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: etiquetasCandidatos,
                    datasets: [{
                        label: 'Votos',
                        data: datosVotos,
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
                        y: { grid: { display: false }, ticks: { color: textColor, font: { size: 12, weight: 'bold' } } }
                    }
                }
            });
        });
    </script>

    {{-- Auto-recarga automática cada 30 segundos --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() { window.location.reload(); }, 30000); 
        });
    </script>
</x-app-layout>