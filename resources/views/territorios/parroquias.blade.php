<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cantón: <span class="text-indigo-600">{{ $canton->nombre }}</span>
            </h2>
            <a href="{{ route('territorios.index', ['provincia' => $canton->provincia_id]) }}" class="text-sm bg-gray-200 px-3 py-1 rounded text-black hover:bg-gray-300 transition">← Volver a Cantones</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black text-xl uppercase text-slate-700">Parroquias de {{ $canton->nombre }}</h3>
                    <button onclick="document.getElementById('modalParroquia').classList.remove('hidden')" class="bg-indigo-600 text-black px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                        + Nueva Parroquia
                    </button>

                    {{-- Modal de Registro --}}
                    <div id="modalParroquia" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-xl p-8 max-w-md w-full shadow-2xl text-left">
                            <h3 class="text-xl font-bold mb-4 text-slate-800">Registrar Nueva Parroquia</h3>
                            <form action="{{ route('parroquia.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="canton_id" value="{{ $canton->id }}">
                                <div class="mb-4">
                                    <label class="block text-sm font-bold mb-2">Nombre de la Parroquia:</label>
                                    <input type="text" name="nombre" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: Macas (Urbana)" required>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('modalParroquia').classList.add('hidden')" class="px-4 py-2 text-black font-bold">Cancelar</button>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-black rounded-lg font-bold shadow-md">Guardar Parroquia</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- DISEÑO UNIFICADO: Cuadrícula de Tarjetas Verticales (Igual a Cantones) --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    @foreach($parroquias as $parroquia)
                        <div class="bg-white p-6 rounded-2xl shadow-sm border hover:border-indigo-500 hover:shadow-md transition group flex flex-col justify-between h-full text-center">
                            
                            {{-- CABECERA DE LA TARJETA --}}
                            <div class="mb-4">
                                <p class="font-black text-gray-800 group-hover:text-indigo-600 uppercase text-base leading-tight">{{ $parroquia->nombre }}</p>
                                <p class="text-xs text-gray-400 mt-2 font-bold uppercase">{{ $parroquia->recintos_count ?? 0 }} Recintos Registrados</p>
                            </div>

                            {{-- BLOQUE DE ACCIONES PARA DIGITADORES DE LA PARROQUIA --}}
                            <div class="space-y-2 mt-auto">
                                
                                {{-- BOTÓN 1: FORMULARIO GENERAR --}}
                                <form action="{{ route('usuarios.generar') }}" method="POST" onsubmit="return confirm('¿Generar usuarios para TODAS las mesas de la parroquia {{ $parroquia->nombre }}?')">
                                    @csrf
                                    <input type="hidden" name="tipo" value="parroquia">
                                    <input type="hidden" name="id" value="{{ $parroquia->id }}">
                                    
                                    {{-- Selector de Tipo de Proceso Electoral --}}
                                    <div class="mb-2">
                                        <select name="proceso_eleccion" required class="w-full text-[10px] rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 font-bold uppercase py-1 bg-slate-50 text-slate-700">
                                            <option value="generales" selected>Para: Elecciones Generales</option>
                                            <option value="primarias">Para: Elecciones Primarias</option>
                                        </select>
                                    </div>

                                    {{-- Selector de Dignidad para la Parroquia --}}
                                    <div class="mb-2">
                                        <select name="dignidad" required class="w-full text-[10px] rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 font-bold uppercase py-1">
                                            <option value="">-- DIGNIDAD --</option>
                                            <option value="JUNTA PARROQUIAL">JUNTA PARROQUIAL</option>
                                            <option value="ALCALDE">ALCALDE</option>
                                            <option value="CONCEJAL">CONCEJAL</option>
                                            <option value="PREFECTO">PREFECTO</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-black text-[10px] font-bold py-2 rounded-lg transition shadow-sm uppercase tracking-tighter">
                                        ⚙️ Generar Digitadores
                                    </button>
                                </form>

                                {{-- BOTÓN 2: VER / IMPRIMIR --}}
                                <a href="{{ route('admin.ver.digitadores', ['tipo' => 'parroquia', 'id' => $parroquia->id]) }}" 
                                class="w-full bg-emerald-500 hover:bg-emerald-600 text-black text-[10px] font-bold py-2 rounded-lg transition shadow-sm uppercase tracking-tighter flex items-center justify-center">
                                    👁️ Ver / Imprimir
                                </a>

                                {{-- BOTÓN 3: NAVEGACIÓN PROPIA DE PARROQUIAS --}}
                                <a href="{{ route('territorios.index', ['parroquia' => $parroquia->id]) }}" 
                                class="w-full bg-slate-200 hover:bg-slate-300 text-black text-[10px] font-bold py-2 rounded-lg transition shadow-sm uppercase tracking-tighter flex items-center justify-center border border-slate-300">
                                    Ver Recintos →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>