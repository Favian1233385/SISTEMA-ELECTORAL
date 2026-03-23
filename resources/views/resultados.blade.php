<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
           
            {{-- Línea 4 original con protección extra --}}
                {{ __('Resultados') }} - {{ is_array($dignidadSeleccionada) ? 'Cargando...' : ($dignidadSeleccionada ?? 'Sin Selección') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Tabs de Dignidades --}}
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500">
                    @foreach($pestanasVisibles as $pestana)
                        <li class="me-2">
                            <a href="{{ route('resultados.index', ['dignidad' => $pestana]) }}" 
                               class="inline-flex p-4 border-b-2 rounded-t-lg transition-colors 
                               {{ $dignidadSeleccionada === $pestana 
                                    ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500 active' 
                                    : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' }}">
                                {{ $pestana }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Filtros de Jurisdicción --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
                <form action="{{ route('resultados.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="dignidad" value="{{ $dignidadSeleccionada }}">
                    
                    {{-- Filtro de Cantón --}}
                    @if(Auth::user()->esAdminGeneral() || Auth::user()->role === 'admin_provincial')
                        <select name="canton_id" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500">
                            <option value="">
                                {{ $dignidadSeleccionada === 'Prefecto' ? 'Toda la Provincia (Total Prefectura)' : (Auth::user()->role === 'admin_provincial' ? 'Toda la Provincia (Alcaldías)' : 'Todos los Cantones') }}
                            </option>
                            @foreach($cantones as $canton)
                                <option value="{{ $canton->id }}" {{ request('canton_id') == $canton->id ? 'selected' : '' }}>
                                    {{ $canton->nombre }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Filtro de Parroquia --}}
                    @if(!Auth::user()->esAdminParroquial())
                        <select name="parroquia_id" onchange="this.form.submit()" class="rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500">
                            <option value="">Todas las Parroquias</option>
                            @foreach($parroquias as $parroquia)
                                <option value="{{ $parroquia->id }}" {{ request('parroquia_id') == $parroquia->id ? 'selected' : '' }}>
                                    {{ $parroquia->nombre }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </form>
            </div>

            {{-- Cards de Resumen Estadístico --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow text-center border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Total Votos</p>
                    <p class="text-2xl font-black dark:text-white">{{ number_format($granTotalVotos) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow text-center border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Actas Procesadas</p>
                    <p class="text-2xl font-black dark:text-white">{{ $totalActas }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow text-center border-l-4 border-yellow-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Votos Blancos</p>
                    <p class="text-2xl font-black dark:text-white">{{ number_format($totalVotosBlancos) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow text-center border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Votos Nulos</p>
                    <p class="text-2xl font-black dark:text-white">{{ number_format($totalVotosNulos) }}</p>
                </div>
            </div>

            {{-- Tabla de Resultados Detallada --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Candidato</th>
                            <th class="px-6 py-3">Partido / Alianza</th>
                            <th class="px-6 py-3 text-right">Votos</th>
                            <th class="px-6 py-3 text-right">% Participación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($resultados as $candidato)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                                {{-- Celda de Candidato con Enlace a Detalle --}}
                                <td class="px-6 py-4 font-bold">
                                    <a href="{{ route('resultados.detalle', $candidato->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline flex flex-col">
                                        <span>{{ $candidato->nombre }}</span>
                                        <span class="text-[10px] font-normal text-gray-400 italic">Ver desglose por mesas</span>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ $candidato->partido->nombre ?? 'Independiente' }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($candidato->total_votos ?? 0) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @php
                                        $porcentaje = $granTotalVotos > 0 
                                            ? (($candidato->total_votos ?? 0) / $granTotalVotos) * 100 
                                            : 0;
                                    @endphp
                                    <span class="inline-block min-w-[60px] text-center bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                        {{ number_format($porcentaje, 2) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-10 text-center italic text-gray-400">
                                    No se encontraron actas registradas para la dignidad de {{ $dignidadSeleccionada }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>