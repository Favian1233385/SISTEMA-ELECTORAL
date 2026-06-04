<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center">
                {{-- Indicador Pulsante EN VIVO --}}
                <div class="flex items-center bg-red-600 px-3 py-1 rounded-lg mr-4 shadow-[0_0_10px_rgba(220,38,38,0.5)]">
                    <span class="relative flex h-3 w-3 mr-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                    </span>
                    <span class="text-white text-xs font-black uppercase tracking-widest">En Vivo</span>
                </div>
                
                <h2 class="font-black text-2xl text-gray-800 dark:text-gray-200 leading-tight border-l-2 border-gray-300 dark:border-gray-700 pl-4">
                    {{ __('Resultados') }}: <span class="text-blue-600 dark:text-blue-400">{{ is_array($dignidadSeleccionada) ? 'General' : $dignidadSeleccionada }}</span>
                </h2>
            </div>
            <!-- SELECTOR DE PROCESO ELECTORAL DINÁMICO -->
            <div class="mb-6 flex flex-wrap justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3">
                    <label for="proceso" class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Contexto Electoral:</label>
                    <form id="formProceso" method="GET" action="{{ route('resultados.index') }}" class="inline-block">
                        <!-- Mantenemos los filtros actuales para no perder la navegación geográfica del usuario -->
                        <input type="hidden" name="dignidad" value="{{ $dignidadSeleccionada }}">
                        @if(request('canton_id')) <input type="hidden" name="canton_id" value="{{ request('canton_id') }}"> @endif
                        @if(request('parroquia_id')) <input type="hidden" name="parroquia_id" value="{{ request('parroquia_id') }}"> @endif

                        <select name="proceso" id="proceso" onchange="document.getElementById('formProceso').submit();" 
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium text-gray-800 bg-gray-50 px-3 py-1.5 cursor-pointer hover:bg-gray-100 transition ease-in-out duration-150">
                            <option value="generales" {{ $proceso === 'generales' ? 'selected' : '' }}>🗳️ Elecciones Generales</option>
                            <option value="primarias" {{ $proceso === 'primarias' ? 'selected' : '' }}>🛡️ Elecciones Primarias</option>
                        </select>
                    </form>
                </div>

                <!-- Indicador de estado del proceso actual -->
                <div class="text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wider {{ $proceso === 'generales' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-purple-100 text-purple-800 border border-purple-200' }}">
                    Visualizando: {{ $proceso }}
                </div>
            </div>
            <div class="flex items-center gap-3">
                {{-- Botón de Reporte PDF --}}
                <a href="{{ route('resultados.pdf', [
                    'dignidad' => is_array($dignidadSeleccionada) ? '' : $dignidadSeleccionada,
                    'canton_id' => request('canton_id'),
                    'parroquia_id' => request('parroquia_id')
                ]) }}" 
                target="_blank" 
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg font-bold text-xs text-gray-700 dark:text-gray-200 uppercase hover:bg-red-50 dark:hover:bg-red-900/20 transition shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z" />
                        <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                    </svg>
                    Exportar PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 bg-blue-50/30 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Tabs de Dignidades --}}
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                <ul class="flex flex-nowrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                    @foreach($pestanasVisibles as $pestana)
                        <li class="me-2">
                            <a href="{{ route('resultados.index', ['dignidad' => $pestana, 'canton_id' => request('canton_id'), 'parroquia_id' => request('parroquia_id')]) }}" 
                               class="inline-flex p-4 border-b-2 rounded-t-lg transition-all {{ $dignidadSeleccionada === $pestana ? 'text-blue-600 border-blue-600 font-bold bg-blue-50/50 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-400' : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                                {{ $pestana }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Filtros y Progreso --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <form action="{{ route('resultados.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                        <input type="hidden" name="dignidad" value="{{ is_array($dignidadSeleccionada) ? '' : $dignidadSeleccionada }}">
                        
                        <div class="flex flex-col h-full justify-end">
                            <label class="block text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500 mb-1 ml-1 leading-tight">
                                Jurisdicción Cantonal
                            </label>
                            <select name="canton_id" onchange="this.form.submit()" class="w-full h-10 rounded-lg border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm focus:ring-blue-500 py-1">
                                <option value="">Toda la Provincia</option>
                                @foreach($cantones as $canton)
                                    <option value="{{ $canton->id }}" {{ request('canton_id') == $canton->id ? 'selected' : '' }}>{{ $canton->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col h-full justify-end">
                            <label class="block text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500 mb-1 ml-1 leading-tight">
                                Parroquia / Zona
                            </label>
                            <select name="parroquia_id" onchange="this.form.submit()" class="w-full h-10 rounded-lg border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm focus:ring-blue-500 py-1">
                                <option value="">Todas las Parroquias</option>
                                @foreach($parroquias as $parroquia)
                                    <option value="{{ $parroquia->id }}" {{ request('parroquia_id') == $parroquia->id ? 'selected' : '' }}>{{ $parroquia->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                {{-- Corregido text-black a text-white debido al fondo oscuro --}}
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-5 rounded-xl shadow-lg text-black dark:text-white border border-blue-700">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold uppercase opacity-80 italic">Escrutinio Global</span>
                        <span class="text-lg font-black">{{ $porcentajeEscrutinio }}%</span>
                    </div>
                    <div class="w-full bg-blue-900/40 rounded-full h-3 mb-2">
                        <div class="bg-white h-3 rounded-full shadow-[0_0_10px_rgba(255,255,255,0.5)]" 
                             style="width: {{ $porcentajeEscrutinio }}%"></div>
                    </div>
                    <p class="text-[10px] italic opacity-70">* Datos basados en actas ingresadas.</p>
                </div>
            </div>

            {{-- Sección del Gráfico --}}
            <div class="w-full bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white uppercase tracking-wider italic">
                        <span class="text-blue-600 dark:text-blue-400">|</span> Tendencia de Votación
                    </h3>
                    <span class="text-[10px] bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-full font-bold uppercase">Cifras en Votos</span>
                </div>
                <div class="h-[400px] w-full">
                    <canvas id="chartResultados"></canvas>
                </div>
            </div>

            {{-- Cards de Resumen en Fila Horizontal --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-blue-600 p-5 rounded-2xl shadow-md transform hover:scale-105 transition-all text-white">
                    <p class="text-[11px] uppercase font-black opacity-80">Votos Válidos</p>
                    <p class="text-3xl font-black tracking-tighter">{{ number_format($granTotalVotos) }}</p>
                </div>

                {{-- Corregido text-black a text-white para asegurar consistencia --}}
                <div class="bg-emerald-500 p-5 rounded-2xl shadow-md transform hover:scale-105 transition-all text-black dark:text-white">
                    <p class="text-[11px] uppercase font-black opacity-80">Actas Procesadas</p>
                    <p class="text-3xl font-black tracking-tighter">{{ $totalActas }}</p>
                </div>

                {{-- Corregido clase text-blue-600-400 errónea y corregido dark:text-black a dark:text-white --}}
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-md border-2 border-gray-100 dark:border-gray-700 transform hover:scale-105 transition-all">
                    <p class="text-[11px] text-blue-600 dark:text-blue-400 uppercase font-black">Votos Blancos</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white tracking-tighter">{{ number_format($totalVotosBlancos) }}</p>
                </div>

                <div class="bg-red-50 dark:bg-red-900/20 p-5 rounded-2xl shadow-md border-2 border-red-200 dark:border-red-900/50 transform hover:scale-105 transition-all">
                    <p class="text-[11px] text-red-500 dark:text-red-400 uppercase font-black">Votos Nulos</p>
                    <p class="text-3xl font-black text-red-600 dark:text-red-400 tracking-tighter">{{ number_format($totalVotosNulos) }}</p>
                </div>
            </div>

            {{-- Tabla de Posiciones --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-4">Ranking / Candidato</th>
                            <th class="px-6 py-4">Organización</th>
                            <th class="px-6 py-4 text-right">Votos</th>
                            <th class="px-6 py-4 text-right">Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($resultados as $index => $candidato)
                            <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors text-gray-700 dark:text-gray-300">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="w-6 h-6 rounded-full {{ $index == 0 ? 'bg-yellow-400 text-black' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' }} flex items-center justify-center text-[10px] font-bold mr-3">
                                            {{ $index + 1 }}
                                        </span>
                                        <a href="{{ route('resultados.detalle', ['candidato' => $candidato->id, 'canton_id' => request('canton_id'), 'parroquia_id' => request('parroquia_id')]) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $candidato->nombre }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs italic">{{ $candidato->partido->nombre ?? 'Alianza' }}</td>
                                <td class="px-6 py-4 text-right font-mono font-bold">{{ number_format($candidato->total_votos) }}</td>
                                <td class="px-6 py-4">
                                    @php $porc = $granTotalVotos > 0 ? ($candidato->total_votos / $granTotalVotos) * 100 : 0; @endphp
                                    <div class="flex items-center justify-end">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mr-3 hidden md:block">
                                            <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full" style="width: {{ $porc }}%"></div>
                                        </div>
                                        <span class="font-bold text-blue-700 dark:text-blue-400">{{ number_format($porc, 2) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-10 text-center text-gray-400 dark:text-gray-500 italic">No hay datos disponibles.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Script para el Gráfico Profesional --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('chartResultados').getContext('2d');
            
            // Detecta si el entorno está en modo oscuro para ajustar los colores de las fuentes
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#9ca3af' : '#4b5563';

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($resultados->pluck('nombre')) !!},
                    datasets: [{
                        label: 'Votos',
                        data: {!! json_encode($resultados->pluck('total_votos')) !!},
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6b7280'],
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { left: 10, right: 30 }
                    },
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { 
                            grid: { display: false }, 
                            ticks: { color: textColor, font: { size: 10 } } 
                        },
                        y: { 
                            grid: { display: false }, 
                            ticks: { 
                                color: textColor,
                                font: { size: 12, weight: 'bold' },
                                autoSkip: false
                            } 
                        }
                    }
                }
            });
        });
    </script>

    {{-- Script para Actualización Automática Real --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                window.location.reload();
            }, 30000); 
        });
    </script>
</x-app-layout>