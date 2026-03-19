<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                {{ __('Nómina de Candidatos Inscritos') }}
            </h2>
            <a href="{{ route('candidatos.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-md transition">
                + Inscribir Candidato
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($candidatos as $candidato)
                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow duration-300">
                            <div class="flex items-center p-5">
                                <div class="relative">
                                    <img class="h-20 w-20 rounded-full object-cover border-2 border-indigo-100" 
                                         src="{{ $candidato->foto ? asset($candidato->foto) : asset('img/default-avatar.png') }}" 
                                         alt="{{ $candidato->nombre }}">
                                    <div class="absolute -bottom-1 -right-1 bg-white p-1 rounded-full shadow-sm">
                                        <img src="{{ asset($candidato->partido->logo) }}" class="h-6 w-6 rounded-full">
                                    </div>
                                </div>

                                <div class="ml-4">
                                    <h3 class="text-lg font-bold text-gray-900 leading-tight">{{ $candidato->nombre }}</h3>
                                    <p class="text-sm font-semibold text-indigo-600">{{ $candidato->dignidad }}</p>
                                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ $candidato->partido->nombre }}</p>
                                </div>
                            </div>
                            
                            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-xs font-medium text-gray-600 bg-gray-200 px-2 py-1 rounded">
                                    {{ $candidato->canton ? $candidato->canton->nombre : $candidato->provincia->nombre }}
                                </span>
                                <div class="flex space-x-2">
                                    <a href="{{ route('candidatos.edit', $candidato) }}" class="text-indigo-600 hover:text-indigo-900 text-sm font-bold">Editar</a>
                                    <form action="{{ route('candidatos.destroy', $candidato) }}" method="POST" onsubmit="return confirm('¿Eliminar esta inscripción?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 text-sm font-bold">Borrar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($candidatos->isEmpty())
                    <div class="text-center py-10">
                        <p class="text-gray-400 text-lg italic">No hay candidatos registrados en la base de datos.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>