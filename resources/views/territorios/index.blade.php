<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Territorio Electoral') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- 1. BLOQUE DE ALERTAS --}}
            @if(session('success'))
                <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 shadow-sm" role="alert">
                    <span class="font-bold">¡Éxito!</span> {{ session('success') }}
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 mb-6 text-sm text-blue-800 rounded-lg bg-blue-50 border border-blue-200 shadow-sm" role="alert">
                    <span class="font-bold">Información:</span> {{ session('info') }}
                    <form action="{{ route('usuarios.generar') }}" method="POST" class="inline ml-2">
                        @csrf
                        <input type="hidden" name="tipo" value="{{ session('tipo_error') }}">
                        <input type="hidden" name="id" value="{{ session('id_error') }}">
                        <input type="hidden" name="reemplazar" value="1">
                        <input type="hidden" name="proceso_eleccion" value="{{ session('proceso_eleccion_error', 'generales') }}">
                        <button type="submit" class="underline font-bold text-blue-900">¿Deseas actualizarlos de todas formas?</button>
                    </form>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 mb-6 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200 shadow-sm" role="alert">
                    <span class="font-bold">Error:</span> {{ session('error') }}
                </div>
            @endif

            {{-- 2. BLOQUE DE CARGA MASIVA MODIFICADO --}}
            <div class="mb-8 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border border-blue-100">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-blue-500 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H5a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-black">Carga Masiva de Estructura Nacional</h3>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 italic">
                    Sube un archivo Excel (.xlsx) para crear automáticamente Provincias, Cantones, Parroquias, Recintos y Mesas segregados por tipo de elección y filtrados por la provincia seleccionada.
                </p>

                <form action="{{ route('import.electoral') }}" method="POST" enctype="multipart/form-data" class="flex flex-col lg:flex-row items-end gap-4 flex-wrap">
                    @csrf
                    
                    {{-- Selector del tipo de proceso electoral --}}
                    <div class="w-full md:w-64">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Proceso</label>
                        <select name="proceso_eleccion" required 
                            class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 font-semibold bg-gray-50">
                            <option value="generales" selected>Elecciones Generales</option>
                            <option value="primarias">Elecciones Primarias</option>
                        </select>
                    </div>

                    {{-- 📥 NUEVO SELECTOR INYECTADO: Filtro Dinámico de Provincia para el SaaS --}}
                    <div class="w-full md:w-64">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Provincia a Importar</label>
                        <select name="provincia_id" required 
                            class="block w-full text-sm text-indigo-900 border border-indigo-200 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-bold bg-indigo-50/50 uppercase">
                            <option value="" disabled selected>-- SELECCIONE PROVINCIA --</option>
                            @foreach($todasLasProvincias as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Campo de selección de archivo --}}
                    <div class="flex-1 w-full min-w-[250px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar archivo Excel</label>
                        <input type="file" name="file" required
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    {{-- Botón de envío --}}
                    <button type="submit" 
                        class="w-full lg:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center justify-center shadow-lg shadow-blue-200 whitespace-nowrap">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Procesar e Importar
                    </button>
                </form>
            </div>

            {{-- 3. PROVINCIAS REGISTRADAS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                <h3 class="text-lg font-bold mb-4 text-gray-800">Provincias Registradas</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($provinciasActivas as $provincia)
                        <div class="border rounded-xl p-4 hover:bg-slate-50 transition shadow-sm bg-white flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center uppercase font-black text-indigo-700 mb-2">
                                    {{ $provincia->nombre }}
                                    <span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">
                                        {{ $provincia->cantones_count }} Cantones
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-3">
                                    {{-- BOTÓN 1: GENERAR USUARIOS --}}
                                    <form action="{{ route('usuarios.generar') }}" method="POST" onsubmit="return confirm('¿Deseas generar automáticamente los usuarios para las mesas de la provincia de {{ $provincia->nombre }}?')">
                                        @csrf
                                        <input type="hidden" name="tipo" value="provincia">
                                        <input type="hidden" name="id" value="{{ $provincia->id }}">
                                        
                                        <div class="mb-1.5">
                                            <select name="proceso_eleccion" required class="w-full text-[10px] rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 font-bold uppercase p-1">
                                                <option value="generales" selected>Para: Elecciones Generales</option>
                                                <option value="primarias">Para: Elecciones Primarias</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <select name="dignidad" required class="w-full text-[10px] rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 font-bold uppercase">
                                                <option value="">-- SELECCIONAR DIGNIDAD --</option>
                                                <option value="PREFECTO">PREFECTO</option>
                                                <option value="ALCALDE">ALCALDE</option>
                                                <option value="CONCEJAL">CONCEJAL</option>
                                                <option value="JUNTA PARROQUIAL">JUNTA PARROQUIAL</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-black text-[10px] uppercase tracking-widest font-bold py-2 rounded-lg transition-all shadow-md">
                                            ⚙️ Generar Usuarios Provincia
                                        </button>
                                    </form>

                                    {{-- BOTÓN 2: VER / IMPRIMIR --}}
                                    @if(Route::has('admin.ver.digitadores'))
                                        <a href="{{ route('admin.ver.digitadores', ['tipo' => 'provincia', 'id' => $provincia->id]) }}" 
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-black text-[10px] uppercase tracking-widest font-bold py-2 rounded-lg transition-all shadow-md flex items-center justify-center">
                                            👁️ Ver / Imprimir Digitadores
                                        </a>
                                    @else
                                        <div class="text-[9px] text-red-500 text-center italic">Ruta de visualización pendiente de configurar</div>
                                    @endif
                                </div>

                                <hr class="my-2 border-gray-100">
                            </div>

                            <a href="{{ route('territorios.index', ['provincia' => $provincia->id]) }}" 
                            class="text-sm text-gray-600 hover:text-indigo-600 font-bold italic block mt-2">
                                Ver Cantones e Ingresar Recintos →
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>