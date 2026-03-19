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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($recintos as $recinto)
                        <div class="p-6 border-2 border-slate-100 rounded-3xl bg-white hover:border-indigo-500 transition shadow-sm group flex flex-col justify-between">
                            
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex gap-4">
                                    <div class="text-slate-800 bg-slate-50 p-2 rounded-2xl">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-black text-xl text-slate-800 uppercase leading-tight">{{ $recinto->nombre }}</p>
                                        <p class="text-xs text-slate-400 font-bold uppercase mt-1">{{ $recinto->direccion ?? 'Sin dirección' }}</p>
                                    </div>
                                </div>
                                <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-full uppercase">
                                    {{ $recinto->mesas_count ?? 0 }} MESAS
                                </span>
                            </div>

                            <div class="mt-4 pt-4 border-t border-slate-50 flex justify-between items-center">
                                
                                <a href="{{ route('territorios.index', ['recinto' => $recinto->id]) }}" 
                                class="bg-emerald-600 text-black px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-tighter hover:bg-emerald-700 transition shadow-lg shadow-emerald-100 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Administrar Juntas (Mesas)
                                </a>

                                <div class="flex gap-3">
                                    <button onclick="abrirModalEditar('{{ $recinto->id }}', '{{ $recinto->nombre }}', '{{ $recinto->direccion }}')" 
                                            class="p-3 bg-blue-50 text-blue-600 rounded-2xl hover:bg-blue-600 hover:text-white transition shadow-sm"
                                            title="Editar Recinto">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <form action="{{ route('recinto.destroy', $recinto->id) }}" method="POST" onsubmit="return confirm('¿Eliminar recinto?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" 
                                                class="p-3 bg-red-50 text-red-600 rounded-2xl hover:bg-red-600 hover:text-white transition shadow-sm"
                                                title="Eliminar Recinto">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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