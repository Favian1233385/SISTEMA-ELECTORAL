<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Provincia: <span class="text-indigo-600">{{ $provincia->nombre }}</span>
            </h2>
            <a href="{{ route('territorios.index') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded transition">Volver</a>
        </div>
    </x-slot>

    <div class="py-12 text-center">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="mb-6 font-bold text-gray-500 uppercase tracking-widest">Cantones de {{ $provincia->nombre }}</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach($cantones as $canton)
                    <div class="bg-white p-6 rounded-2xl shadow-sm border hover:border-indigo-500 hover:shadow-md transition group flex flex-col justify-between h-full">
                        
                        {{-- Enlace para seguir navegando a parroquias --}}
                        <a href="{{ route('territorios.index', ['canton' => $canton->id]) }}" class="mb-4 block">
                            <p class="font-black text-gray-800 group-hover:text-indigo-600 uppercase">{{ $canton->nombre }}</p>
                            <p class="text-xs text-gray-400 mt-2">{{ $canton->parroquias_count }} Parroquias</p>
                        </a>

                        {{-- BLOQUE DE ACCIONES PARA DIGITADORES DEL CANTÓN --}}
                        <div class="space-y-2">
                            {{-- BOTÓN 1: GENERAR --}}
                            <form action="{{ route('usuarios.generar') }}" method="POST" onsubmit="return confirm('¿Generar usuarios para el cantón {{ $canton->nombre }}?')">
                                @csrf
                                <input type="hidden" name="tipo" value="canton">
                                <input type="hidden" name="id" value="{{ $canton->id }}">
                                {{-- Selector de Dignidad para el Cantón --}}
                                <div class="mb-2">
                                    <select name="dignidad" required class="w-full text-[10px] rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 font-bold uppercase py-1">
                                        <option value="">-- DIGNIDAD --</option>
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
                            <a href="{{ route('admin.ver.digitadores', ['tipo' => 'canton', 'id' => $canton->id]) }}" 
                               class="w-full bg-emerald-600 hover:bg-emerald-700 text-black text-[10px] font-bold py-2 rounded-lg transition shadow-sm uppercase tracking-tighter flex items-center justify-center">
                                👁️ Ver / Imprimir Digitadores
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>