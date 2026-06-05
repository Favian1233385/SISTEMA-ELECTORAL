<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Administración de Procesos Electorales</h1>
            <p class="text-sm text-gray-600">Historial dinámico y control de periodos anuales del sistema SaaS.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
                <span class="font-semibold">Por favor, corrige los siguientes errores:</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200 h-fit">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Nuevo Periodo Electoral</h2>
                
                <form action="{{ route('procesos.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Proceso</label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" 
                               placeholder="Ej: Seccionales Prefectura"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                    </div>

                    <div class="mb-4">
                        <label for="anio" class="block text-sm font-medium text-gray-700 mb-1">Año de Ejecución</label>
                        <input type="number" name="anio" id="anio" value="{{ old('anio', date('Y')) }}" 
                               min="2020" max="2050"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                    </div>

                    <div class="mb-6">
                        <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Elección</label>
                        <select name="tipo" id="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            <option value="generales" {{ old('tipo') == 'generales' ? 'selected' : '' }}>Elecciones Generales / Seccionales</option>
                            <option value="primarias" {{ old('tipo') == 'primarias' ? 'selected' : '' }}>Elecciones Primarias (Internas)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Define el comportamiento y reglas de escrutinio del sistema.</p>
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out text-sm shadow">
                        Inicializar Proceso
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Historial de Procesos Registrados</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider w-16">Año</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Nombre del Proceso</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider w-28">Tipo</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider w-36">Estado</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider w-40">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($procesos as $item)
                                <tr class="{{ $item->estado === 'activo' ? 'bg-blue-50/50' : '' }}">
                                    <td class="px-4 py-4 whitespace-nowrap font-bold text-gray-700">
                                        {{ $item->anio }}
                                    </td>
                                    <td class="px-4 py-4 text-gray-600">
                                        {{ $item->nombre }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($item->tipo === 'primarias')
                                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-medium rounded bg-purple-100 text-purple-800 border border-purple-200">
                                                Primarias
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-medium rounded bg-blue-100 text-blue-800 border border-blue-200">
                                                Generales
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        @if($item->estado === 'activo')
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                                Activo
                                            </span>
                                        @else
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                                Archivado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        @if($item->estado !== 'activo')
                                            <form action="{{ route('procesos.update', $item->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" 
                                                        onclick="return confirm('¿Está seguro de cambiar el entorno activo al periodo: {{ $item->nombre }} ({{ ucfirst($item->tipo) }})?')"
                                                        class="bg-white hover:bg-gray-50 text-blue-600 border border-blue-300 font-medium py-1 px-3 rounded shadow-sm text-xs transition duration-150">
                                                    Activar
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400 italic font-medium">Dominante</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>