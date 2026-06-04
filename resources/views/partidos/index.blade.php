<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $proceso === 'primarias' ? __('Listas Internas (Elecciones Primarias)') : __('Movimientos Políticos (Elecciones Generales)') }}
            </h2>
            <!-- El botón hereda el contexto actual para abrir el formulario correcto -->
            <a href="{{ route('partidos.create', ['proceso' => $proceso]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition duration-150 ease-in-out">
                {{ $proceso === 'primarias' ? '+ Nueva Lista Interna' : '+ Nuevo Partido' }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- COMPONENTE DE PESTAÑAS (TABS) PARA EL CONTROL DE PROCESO ELECTORAL -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <a href="{{ route('partidos.index', ['proceso' => 'generales']) }}" 
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition duration-150 ease-in-out {{ $proceso === 'generales' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Elecciones Generales
                    </a>
                    <a href="{{ route('partidos.index', ['proceso' => 'primarias']) }}" 
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition duration-150 ease-in-out {{ $proceso === 'primarias' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Elecciones Primarias (Listas Internas)
                    </a>
                </nav>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lista</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($partidos as $partido)
                            <tr class="hover:bg-gray-50 transition duration-75">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($partido->logo)
                                        <img src="{{ asset($partido->logo) }}" alt="Logo" class="h-12 w-12 rounded-full object-cover border shadow-sm">
                                    @else
                                        <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center border border-dashed">
                                            <span class="text-gray-400 italic text-[10px]">Sin logo</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $partido->nombre }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-semibold">
                                    {{ $partido->lista }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <!-- Mantiene el contexto del proceso al ir a editar -->
                                    <a href="{{ route('partidos.edit', [$partido, 'proceso' => $proceso]) }}" class="text-indigo-600 hover:text-indigo-900 mr-4 transition duration-150">Editar</a>
                                    
                                    <form action="{{ route('partidos.destroy', $partido) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition duration-150" onclick="return confirm('¿Está seguro de eliminar este registro? Se borrarán sus candidatos asociados.')">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($partidos->isEmpty())
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p class="text-gray-500 mt-4 text-sm font-medium">No se encontraron registros para este proceso electoral.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>