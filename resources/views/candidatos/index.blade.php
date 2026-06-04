<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                {{ $proceso === 'primarias' ? __('Nómina de Precandidatos Inscritos (Primarias)') : __('Nómina de Candidatos Inscritos (Generales)') }}
            </h2>
            <a href="{{ route('candidatos.create', ['proceso' => $proceso]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-md transition duration-150 ease-in-out">
                {{ $proceso === 'primarias' ? '+ Inscribir Precandidato' : '+ Inscribir Candidato' }}
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- COMPONENTE DE PESTAÑAS (TABS) PARA EL CONTROL DEL PROCESO ELECTORAL -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="{{ route('candidatos.index', ['proceso' => 'generales']) }}" 
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition duration-150 ease-in-out {{ $proceso === 'generales' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Elecciones Generales
                    </a>
                    <a href="{{ route('candidatos.index', ['proceso' => 'primarias']) }}" 
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition duration-150 ease-in-out {{ $proceso === 'primarias' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Elecciones Primarias (Precandidatos)
                    </a>
                </nav>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($candidatos as $candidato)
                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between">
                            
                            <div class="flex items-center p-5">
                                <div class="relative flex-shrink-0">
                                    <img class="h-20 w-20 rounded-full object-cover border-2 border-indigo-100 shadow-sm" 
                                         src="{{ $candidato->foto ? asset($candidato->foto) : asset('img/default-avatar.png') }}" 
                                         alt="{{ $candidato->nombre }}">
                                    <!-- Insignia del Partido Político Coincidente -->
                                    <div class="absolute -bottom-1 -right-1 bg-white p-1 rounded-full shadow border border-gray-100">
                                        @if($candidato->partido && $candidato->partido->logo)
                                            <img src="{{ asset($candidato->partido->logo) }}" class="h-6 w-6 rounded-full object-cover" title="{{ $candidato->partido->nombre }}">
                                        @else
                                            <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-[8px] text-gray-500 font-bold">SP</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="ml-4">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ $candidato->nombre }}</h3>
                                    </div>
                                    <p class="text-sm font-semibold text-indigo-600">{{ $candidato->dignidad }}</p>
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">
                                        {{ $candidato->partido ? $candidato->partido->nombre : 'Sin Partido/Lista asignada' }}
                                    </p>
                                </div>
                            </div>
                            
                            <!-- CONTENEDOR DE JURISDICCIÓN TERRITORIAL DETALLADA Y ACCIONES -->
                            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center mt-auto">
                                <span class="text-[11px] font-bold tracking-wide text-gray-600 bg-gray-200 px-2.5 py-1 rounded-full uppercase">
                                    @if($candidato->parroquia)
                                        Parroquia: {{ $candidato->parroquia->nombre }}
                                    @elseif($candidato->canton)
                                        Cantón: {{ $candidato->canton->nombre }}
                                    @elseif($candidato->provincia)
                                        Provincia: {{ $candidato->provincia->nombre }}
                                    @else
                                        Nacional
                                    @endif
                                </span>
                                
                                <div class="flex space-x-3 items-center">
                                    <a href="{{ route('candidatos.edit', [$candidato, 'proceso' => $proceso]) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-bold transition duration-150">
                                        Editar
                                    </a>
                                    <form action="{{ route('candidatos.destroy', $candidato) }}" method="POST" onsubmit="return confirm('¿Está completamente seguro de eliminar esta inscripción?')">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-bold transition duration-150">
                                            Borrar
                                        </button>
                                    </form>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

                @if($candidatos->isEmpty())
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <p class="text-gray-500 mt-4 text-sm font-medium">
                            No hay {{ $proceso === 'primarias' ? 'precandidatos' : 'candidatos' }} registrados para este proceso electoral.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>