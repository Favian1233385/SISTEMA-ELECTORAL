<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Recinto: <span class="text-indigo-600">{{ $recinto->nombre }}</span>
            </h2>
            <a href="{{ route('territorios.index', ['parroquia' => $recinto->parroquia_id]) }}" class="text-sm bg-gray-200 px-3 py-1 rounded">← Volver al Recinto</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl">
                <div class="flex justify-between items-center mb-8 border-b pb-4">
                    <h3 class="font-bold text-lg text-slate-700 uppercase tracking-tight">Listado de Mesas (JRV)</h3>
                    <button onclick="document.getElementById('modalMesa').classList.remove('hidden'); document.getElementById('modalMesa').classList.add('flex')" class="bg-emerald-600 text-black px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 transition">
                        + Agregar Mesa Manualmente
                    </button>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="p-4 text-xs font-black uppercase text-slate-500 tracking-widest">N° Mesa</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-500 tracking-widest">Género</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-500 tracking-widest text-center">Electores</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-500 tracking-widest text-center">Estado</th>
                                <th class="p-4 text-xs font-black uppercase text-slate-500 tracking-widest text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($mesas as $mesa)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-4 font-bold text-slate-700">Mesa #{{ $mesa->numero }}</td>
                                <td class="p-4 text-slate-600">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $mesa->genero == 'MASCULINO' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' }}">
                                        {{ $mesa->genero }}
                                    </span>
                                </td>
                                <td class="p-4 text-center font-mono font-bold text-indigo-600">{{ $mesa->num_electores }}</td>
                                
                                <td class="p-4 text-center">
                                    <form action="{{ route('mesa.update', $mesa->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="toggle_status" value="1">
                                        <button type="submit" class="group relative inline-flex items-center">
                                            @if($mesa->estado == 'Habilitada')
                                                <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-black uppercase group-hover:bg-emerald-600 group-hover:text-white transition cursor-pointer">
                                                    ● Habilitada
                                                </span>
                                            @else
                                                <span class="px-3 py-1 bg-slate-100 text-slate-500 rounded-full text-[10px] font-black uppercase group-hover:bg-red-600 group-hover:text-white transition cursor-pointer">
                                                    ○ Deshabilitada
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>

                                <td class="p-4 text-right flex justify-end gap-2">
                                    <button onclick="abrirModalEditarMesa('{{ $mesa->id }}', '{{ $mesa->numero }}', '{{ $mesa->num_electores }}')" 
                                            class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <form action="{{ route('mesa.destroy', $mesa->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta mesa definitivamente?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="modalMesa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-100">
            <h3 class="text-xl font-black mb-6 text-slate-800 uppercase">Registrar Nueva Mesa (JRV)</h3>
            <form action="{{ route('mesa.store') }}" method="POST">
                @csrf
                <input type="hidden" name="recinto_id" value="{{ $recinto->id }}">

                <div class="mb-4">
                    <label class="block text-xs font-black uppercase text-slate-400 mb-2">Número de Mesa:</label>
                    <input type="text" name="numero" class="w-full border-slate-200 rounded-xl focus:ring-indigo-500 shadow-sm" placeholder="Ej: 001" required>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-black uppercase text-slate-400 mb-2">Género:</label>
                    <select name="genero" class="w-full border-slate-200 rounded-xl focus:ring-indigo-500 shadow-sm" required>
                        <option value="" disabled selected>Seleccione un género</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-black uppercase text-slate-400 mb-2">Total Electores:</label>
                    <input type="number" name="num_electores" class="w-full border-slate-200 rounded-xl focus:ring-indigo-500 shadow-sm" placeholder="Ej: 350" required>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="document.getElementById('modalMesa').classList.add('hidden')" class="px-5 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Guardar Mesa</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditarMesa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl border border-slate-100">
            <h3 class="text-xl font-black mb-6 text-slate-800 uppercase">Editar Datos de la Mesa</h3>
            <form id="formEditarMesa" method="POST">
                @csrf @method('PUT')
                
                <div class="mb-4">
                    <label class="block text-xs font-black uppercase text-slate-400 mb-2">Número de Mesa:</label>
                    <input type="text" name="numero" id="edit_numero" class="w-full border-slate-200 rounded-xl focus:ring-indigo-500 shadow-sm" required>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-black uppercase text-slate-400 mb-2">Total Electores:</label>
                    <input type="number" name="num_electores" id="edit_electores" class="w-full border-slate-200 rounded-xl focus:ring-indigo-500 shadow-sm" required>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button type="button" onclick="cerrarModalEditar()" class="px-5 py-2 text-slate-500 font-bold">Cancelar</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-black rounded-xl font-bold shadow-lg shadow-blue-200">Actualizar Mesa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEditarMesa(id, numero, electores) {
            // Seteamos la acción del formulario dinámicamente
            const form = document.getElementById('formEditarMesa');
            form.action = `/mesa/${id}`; 

            // Rellenamos los campos
            document.getElementById('edit_numero').value = numero;
            document.getElementById('edit_electores').value = electores;

            // Mostramos el modal
            const modal = document.getElementById('modalEditarMesa');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalEditar() {
            const modal = document.getElementById('modalEditarMesa');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Cerrar modales si se hace clic fuera del contenido
        window.onclick = function(event) {
            const modal1 = document.getElementById('modalMesa');
            const modal2 = document.getElementById('modalEditarMesa');
            if (event.target == modal1) modal1.classList.add('hidden');
            if (event.target == modal2) modal2.classList.add('hidden');
        }
    </script>
</x-app-layout>