<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Parroquia: <span class="text-indigo-600">{{ $parroquia->nombre }}</span>
            </h2>
            <a href="{{ route('territorios.index', ['canton' => $parroquia->canton_id]) }}" class="text-sm bg-gray-200 px-3 py-1 rounded">← Volver a Parroquias</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg border-t-4 border-indigo-500">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="font-black text-xl uppercase text-slate-700">Recintos Electorales</h3>
                        <p class="text-sm text-slate-500">{{ $parroquia->canton->nombre }} / {{ $parroquia->canton->provincia->nombre }}</p>
                    </div>
                    <button onclick="document.getElementById('modalRecinto').classList.remove('hidden'); document.getElementById('modalRecinto').classList.add('flex')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition">
                        + Nuevo Recinto
                    </button>
                </div>
                {{-- DISEÑO UNIFICADO: Cuadrícula de 4 Columnas para mantener la consistencia SaaS --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    @foreach($recintos as $recinto)
                        <div class="p-6 border-2 border-slate-100 rounded-3xl bg-white hover:border-indigo-500 transition shadow-sm group flex flex-col justify-between h-full">
                            
                            {{-- INFORMACIÓN SUPERIOR DEL RECINTO --}}
                            <div class="mb-4 text-center">
                                <div class="text-slate-800 bg-slate-50 p-2 rounded-2xl inline-block mb-3">
                                    <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    </svg>
                                </div>
                                <p class="font-black text-base text-slate-800 uppercase leading-tight h-12 flex items-center justify-center">{{ $recinto->nombre }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 truncate" title="{{ $recinto->direccion }}">{{ $recinto->direccion ?? 'Sin dirección' }}</p>
                                
                                <div class="mt-3">
                                    <span class="bg-indigo-50 text-indigo-600 text-[9px] font-black px-3 py-1 rounded-full uppercase">
                                        {{ $recinto->mesas_count ?? 0 }} MESAS
                                    </span>
                                </div>
                            </div>

                            {{-- BLOQUE DE ACCIONES INFERIORES --}}
                            <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                                
                                {{-- ENLACE PRINCIPAL DE ADMINISTRACIÓN --}}
                                <a href="{{ route('territorios.index', ['recinto' => $recinto->id]) }}" 
                                   class="w-full bg-emerald-600 hover:bg-emerald-700 text-black text-[10px] font-bold py-2 rounded-lg transition shadow-sm uppercase tracking-tighter flex items-center justify-center gap-1">
                                    👁️ Administrar Juntas
                                </a>

                                {{-- BOTONES SECUNDARIOS DE EDICIÓN Y ELIMINACIÓN --}}
                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="abrirModalEditar('{{ $recinto->id }}', '{{ $recinto->nombre }}', '{{ $recinto->direccion }}')" 
                                            class="w-full py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition shadow-sm text-[10px] font-bold uppercase flex items-center justify-center gap-1"
                                            title="Editar Recinto">
                                        ✏️ Editar
                                    </button>

                                    <form action="{{ route('recinto.destroy', $recinto->id) }}" method="POST" onsubmit="return confirm('¿Eliminar recinto?')" class="w-full">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                class="w-full py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition shadow-sm text-[10px] font-bold uppercase flex items-center justify-center gap-1"
                                                title="Eliminar Recinto">
                                            🗑️ Borrar
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div id="modalRecinto" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-8 max-w-md w-full shadow-2xl">
            <h3 class="text-xl font-bold mb-4 text-slate-800 uppercase italic">Registrar Nuevo Recinto</h3>
            <form action="{{ route('recinto.store') }}" method="POST">
                @csrf
                <input type="hidden" name="parroquia_id" value="{{ $parroquia->id }}">
                <div class="mb-4">
                    <label class="block font-bold mb-2 uppercase text-xs text-slate-400">Nombre del Recinto:</label>
                    <input type="text" name="nombre" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2 uppercase text-xs text-slate-400">Dirección:</label>
                    <input type="text" name="direccion" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500" required>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('modalRecinto').classList.add('hidden'); document.getElementById('modalRecinto').classList.remove('flex')" class="px-4 py-2 text-gray-400 font-bold uppercase text-[10px]">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold shadow-md uppercase text-[10px]">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditarRecinto" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-8 max-w-md w-full shadow-2xl">
            <h3 class="text-xl font-bold mb-4 text-slate-800 uppercase italic">Editar Recinto</h3>
            <form id="formEditar" method="POST">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label class="block font-bold mb-2 uppercase text-xs text-slate-400">Nombre del Recinto:</label>
                    <input type="text" name="nombre" id="edit_nombre" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label class="block font-bold mb-2 uppercase text-xs text-slate-400">Dirección:</label>
                    <input type="text" name="direccion" id="edit_direccion" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500" required>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('modalEditarRecinto').classList.add('hidden'); document.getElementById('modalEditarRecinto').classList.remove('flex')" class="px-4 py-2 text-gray-400 font-bold uppercase text-[10px]">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-bold shadow-md uppercase text-[10px]">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEditar(id, nombre, direccion) {
            const modal = document.getElementById('modalEditarRecinto');
            const form = document.getElementById('formEditar');
            form.action = `/recinto/${id}`;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_direccion').value = direccion;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    </script>
</x-app-layout>