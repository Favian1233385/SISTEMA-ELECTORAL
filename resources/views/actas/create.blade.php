<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Registrar Nueva Acta de Escrutinio') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg shadow-sm">
                    <p class="font-bold">Error al guardar:</p>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <div class="p-8">
                    <form action="{{ route('actas.store') }}" method="POST" id="form-acta">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-b pb-8 text-sm">
                            {{-- 1. SECCIÓN UBICACIÓN --}}
                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-indigo-700">1. Ubicación</h3>
                                
                                <div>
                                    <label class="block font-medium text-gray-700 italic">Provincia</label>
                                    <select id="provincia_id" name="provincia_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ auth()->user()->esDigitador() ? 'bg-gray-100 pointer-events-none' : 'bg-white' }}" required>
                                        @foreach($provincias as $prov)
                                            <option value="{{ $prov->id }}" {{ (auth()->user()->provincia_id == $prov->id) ? 'selected' : '' }}>
                                                {{ $prov->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-700 italic">Cantón</label>
                                    <select id="canton_id" name="canton_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ auth()->user()->esDigitador() ? 'bg-gray-100 pointer-events-none' : 'bg-white' }}" required>
                                        <option value="">Cargando...</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-700 italic">Parroquia</label>
                                    <select id="parroquia_id" name="parroquia_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ auth()->user()->esDigitador() ? 'bg-gray-100 pointer-events-none' : 'bg-white' }}" required>
                                        <option value="">Esperando...</option>
                                    </select>
                                </div>
                            </div>

                            {{-- 2. SECCIÓN RECINTO Y MESA --}}
                            <div class="space-y-4 border-l pl-6">
                                <h3 class="text-lg font-bold text-indigo-700">2. Recinto y Mesa</h3>
                                
                                <div>
                                    <label class="block font-medium text-gray-700 italic">Recinto Electoral</label>
                                    <select id="recinto_id" name="recinto_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required>
                                        <option value="">Seleccione...</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-700 italic">Dignidad de esta Acta</label>
                                    <select name="dignidad" id="dignidad" 
                                        class="mt-1 block w-full rounded-md border-indigo-300 shadow-sm font-bold {{ auth()->user()->dignidad_asignada ? 'bg-indigo-50 pointer-events-none text-indigo-800' : 'bg-white' }}" required>
                                        <option value="">-- Seleccione --</option>
                                        @php
                                            $dignidades = [
                                                'prefecto' => 'Prefecto',
                                                'alcalde' => 'Alcalde',
                                                'concejal' => 'Concejales',
                                                'junta_parroquial' => 'Junta Parroquial'
                                            ];
                                        @endphp
                                        @foreach($dignidades as $valor => $nombre)
                                            <option value="{{ $valor }}" {{ auth()->user()->dignidad_asignada == $valor ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if(auth()->user()->dignidad_asignada)
                                        <p class="text-[10px] text-indigo-600 mt-1 font-bold uppercase tracking-tighter">* Acceso restringido a su dignidad asignada</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-700 italic">Junta Receptora (Mesa)</label>
                                    <select id="mesa_id" name="mesa_id" class="mt-1 block w-full rounded-md border-red-300 shadow-sm bg-white font-bold" required>
                                        <option value="">Primero elija Recinto...</option>
                                    </select>
                                </div>
                            </div>

                            {{-- 3. CONTROL DE VOTOS --}}
                            <div class="space-y-4 border-l pl-6">
                                <h3 class="text-lg font-bold text-indigo-700">3. Control de Votos</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Votos en Blanco</label>
                                    <input type="number" name="votos_blancos" id="votos_blancos" value="0" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Votos Nulos</label>
                                    <input type="number" name="votos_nulos" id="votos_nulos" value="0" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" required>
                                </div>
                                <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200 text-xs text-yellow-800 mt-4">
                                    <p class="font-bold uppercase tracking-widest text-[10px]">⚠️ Verificación Física</p>
                                    <p>Revise que los totales coincidan con el documento físico antes de guardar.</p>
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN 4: CANDIDATOS --}}
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                                <span class="bg-indigo-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 shadow-lg text-sm">4</span>
                                Ingreso de Votos por Lista
                            </h3>
                            <div id="contenedor-candidatos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="col-span-full p-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 text-gray-400">
                                    Seleccione la ubicación y mesa para cargar las listas.
                                </div>
                            </div>
                        </div>

                        <div class="mt-12 flex justify-center border-t pt-8">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-800 text-white font-black py-4 px-20 rounded-full transition duration-300 shadow-2xl transform hover:scale-105 uppercase tracking-widest">
                                Guardar Acta Oficial
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selects = {
            provincia: document.getElementById('provincia_id'),
            canton: document.getElementById('canton_id'),
            parroquia: document.getElementById('parroquia_id'),
            recinto: document.getElementById('recinto_id'),
            mesa: document.getElementById('mesa_id'),
            dignidad: document.getElementById('dignidad')
        };

        const contenedorCandidatos = document.getElementById('contenedor-candidatos');

        async function cargarDatos(url, targetSelect, placeholder) {
            try {
                const res = await fetch(url);
                const data = await res.json();
                targetSelect.innerHTML = `<option value="">${placeholder}</option>`;
                
                data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.numero ? `Mesa #${item.numero} (${item.genero})` : item.nombre;
                    
                    if (targetSelect === selects.mesa && item.completada) {
                        option.textContent += " ✔ [YA INGRESADA]";
                        option.classList.add('bg-green-50', 'text-green-700');
                        option.disabled = true;
                    }
                    targetSelect.appendChild(option);
                });

                // Auto-selección para digitadores
                if (targetSelect === selects.canton && data.length > 0) {
                    const preSelected = "{{ auth()->user()->canton_id }}";
                    if (preSelected) {
                        selects.canton.value = preSelected;
                        selects.canton.dispatchEvent(new Event('change'));
                    }
                }

            } catch (e) { console.error("Error cargando datos:", e); }
        }

        // Encadenamiento de selects
        selects.provincia.addEventListener('change', () => cargarDatos(`/api/cantones/${selects.provincia.value}`, selects.canton, 'Seleccione Cantón...'));
        selects.canton.addEventListener('change', () => cargarDatos(`/api/parroquias/${selects.canton.value}`, selects.parroquia, 'Seleccione Parroquia...'));
        selects.parroquia.addEventListener('change', () => cargarDatos(`/api/recintos/${selects.parroquia.value}`, selects.recinto, 'Seleccione Recinto...'));
        
        const refrescarFinal = () => {
            if (selects.recinto.value && selects.dignidad.value) {
                cargarDatos(`/api/mesas/${selects.recinto.value}?dignidad=${selects.dignidad.value}`, selects.mesa, 'Seleccione Mesa...');
                actualizarCandidatos();
            }
        };

        selects.recinto.addEventListener('change', refrescarFinal);
        selects.dignidad.addEventListener('change', refrescarFinal);

        async function actualizarCandidatos() {
            const d = selects.dignidad.value;
            if (!d) return;

            contenedorCandidatos.innerHTML = '<div class="col-span-full text-center py-10 italic">Buscando listas electorales...</div>';

            try {
                const url = `/api/candidatos-filtrados?dignidad=${d}&canton_id=${selects.canton.value}&parroquia_id=${selects.parroquia.value}`;
                const res = await fetch(url);
                const data = await res.json();

                contenedorCandidatos.innerHTML = '';
                data.forEach(cand => {
                    const foto = cand.foto ? `/storage/${cand.foto.replace('public/', '')}` : `https://ui-avatars.com/api/?name=${cand.nombre}&background=4f46e5&color=fff`;
                    contenedorCandidatos.innerHTML += `
                        <div class="flex flex-col p-4 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition">
                            <div class="flex items-center mb-4">
                                <img src="${foto}" class="w-14 h-14 rounded-full border-2 border-indigo-100 object-cover shadow-sm">
                                <div class="ml-4">
                                    <p class="text-[10px] font-bold text-indigo-500 uppercase">${cand.partido?.nombre || 'Independiente'}</p>
                                    <p class="text-sm font-black text-gray-800 uppercase leading-tight">${cand.nombre}</p>
                                </div>
                            </div>
                            <div class="mt-auto">
                                <label class="block text-[10px] text-gray-400 uppercase font-bold mb-1">Total Votos</label>
                                <input type="number" name="votos_candidatos[${cand.id}]" value="0" min="0" 
                                    class="w-full text-lg font-black text-center border-gray-300 rounded-xl focus:ring-green-500 focus:border-green-500 bg-gray-50">
                            </div>
                        </div>`;
                });
            } catch (e) { contenedorCandidatos.innerHTML = 'Error al cargar listas.'; }
        }

        // Disparo inicial
        if (selects.provincia.value) selects.provincia.dispatchEvent(new Event('change'));
    });
    </script>
</x-app-layout>