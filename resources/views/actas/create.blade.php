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
                                
                                @if(auth()->user()->esDigitador())
                                    {{-- VISTA PARA DIGITADOR: Valores fijos --}}
                                    <div class="space-y-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <p><strong>Provincia:</strong> {{ auth()->user()->provincia->nombre ?? 'N/A' }}</p>
                                        <p><strong>Cantón:</strong> {{ auth()->user()->canton->nombre ?? 'N/A' }}</p>
                                        <p><strong>Parroquia:</strong> {{ auth()->user()->parroquia->nombre ?? 'N/A' }}</p>
                                        
                                        <input type="hidden" name="provincia_id" id="provincia_id" value="{{ auth()->user()->provincia_id }}">
                                        <input type="hidden" name="canton_id" id="canton_id" value="{{ auth()->user()->canton_id }}">
                                        <input type="hidden" name="parroquia_id" id="parroquia_id" value="{{ auth()->user()->parroquia_id }}">
                                    </div>
                                @else
                                    {{-- VISTA PARA ADMIN: Selectores dinámicos --}}
                                    <div>
                                        <label class="block font-medium text-gray-700 italic">Provincia</label>
                                        <select id="provincia_id" name="provincia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required>
                                            @foreach($provincias as $prov)
                                                <option value="{{ $prov->id }}" {{ (auth()->user()->provincia_id == $prov->id) ? 'selected' : '' }}>
                                                    {{ $prov->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-medium text-gray-700 italic">Cantón</label>
                                        <select id="canton_id" name="canton_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required>
                                            <option value="">Cargando...</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-medium text-gray-700 italic">Parroquia</label>
                                        <select id="parroquia_id" name="parroquia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required>
                                            <option value="">Esperando...</option>
                                        </select>
                                    </div>
                                @endif
                            </div>

                            {{-- 2. SECCIÓN RECINTO Y MESA --}}
                            <div class="space-y-4 border-l pl-6">
                                <h3 class="text-lg font-bold text-indigo-700">2. Recinto y Mesa</h3>
                                
                                @if(auth()->user()->esDigitador())
                                    {{-- VISTA BLOQUEADA PARA DIGITADOR --}}
                                    <div class="space-y-4 bg-indigo-50 p-4 rounded-xl border border-indigo-100 shadow-sm">
                                        <div>
                                            <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest">Recinto Electoral</label>
                                            <p class="text-gray-800 font-bold">{{ auth()->user()->recinto->nombre ?? 'Sin Recinto' }}</p>
                                            <input type="hidden" name="recinto_id" id="recinto_id" value="{{ auth()->user()->recinto_id }}">
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest">Dignidad Asignada</label>
                                            <p class="text-indigo-700 font-black uppercase text-base">
                                                {{ str_replace('_', ' ', auth()->user()->dignidad_asignada) }}
                                            </p>
                                            <input type="hidden" name="dignidad" id="dignidad" value="{{ auth()->user()->dignidad_asignada }}">
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest">Junta Receptora (Mesa)</label>
                                            <p class="text-red-600 font-black text-xl">
                                                Mesa #{{ auth()->user()->mesa->numero ?? 'N/A' }} 
                                                <span class="text-sm font-bold">({{ auth()->user()->mesa->genero ?? '' }})</span>
                                            </p>
                                            <input type="hidden" name="mesa_id" id="mesa_id" value="{{ auth()->user()->mesa_id }}">
                                        </div>
                                        
                                        <p class="text-[10px] text-indigo-500 italic mt-2 border-t pt-2 border-indigo-100">
                                            * Datos fijados según su perfil de digitador.
                                        </p>
                                    </div>
                                @else
                                    {{-- VISTA ABIERTA PARA ADMINISTRADOR --}}
                                    <div>
                                        <label class="block font-medium text-gray-700 italic">Recinto Electoral</label>
                                        <select id="recinto_id" name="recinto_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-white" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block font-medium text-gray-700 italic">Dignidad de esta Acta</label>
                                        <select name="dignidad" id="dignidad" 
                                            class="mt-1 block w-full rounded-md border-indigo-300 shadow-sm font-bold bg-white" required>
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
                                                <option value="{{ $valor }}">
                                                    {{ $nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block font-medium text-gray-700 italic">Junta Receptora (Mesa)</label>
                                        <select id="mesa_id" name="mesa_id" class="mt-1 block w-full rounded-md border-red-300 shadow-sm bg-white font-bold" required>
                                            <option value="">Primero elija Recinto...</option>
                                        </select>
                                    </div>
                                @endif
                            </div>

                            {{-- 3. CONTROL DE VOTOS --}}
                            <div class="space-y-4 border-l pl-6">
                                <h3 class="text-lg font-bold text-indigo-700">3. Control de Votos</h3>
                                
                                <div>
                                    <label class="block text-sm font-bold text-indigo-600 uppercase">Total Sufragantes (Padrón)</label>
                                    <input type="number" name="sufragantes" id="sufragantes" value="0" min="0" 
                                        class="mt-1 block w-full rounded-md border-indigo-300 shadow-sm focus:ring-indigo-500 font-black text-lg bg-indigo-50" required>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Votos en Blanco</label>
                                        <input type="number" name="votos_blancos" id="votos_blancos" value="0" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Votos Nulos</label>
                                        <input type="number" name="votos_nulos" id="votos_nulos" value="0" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500" required>
                                    </div>
                                </div>

                                <div id="alerta-validacion" class="mt-4 p-3 rounded-xl border flex justify-between items-center bg-gray-50 border-gray-200 transition-all duration-300">
                                    <span id="texto-validacion" class="text-[10px] font-black uppercase tracking-tighter text-gray-500">Esperando datos...</span>
                                    <span id="conteo-actual" class="text-sm font-black text-gray-700">0 / 0</span>
                                </div>
                            </div>
                        </div>

                        {{-- SECCIÓN 4: CANDIDATOS --}}
                        <div class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                                <span class="bg-indigo-600 text-white rounded-full w-8 h-8 flex items-center justify-center mr-3 shadow-lg text-sm">4</span>
                                Ingreso de Votos por Lista
                            </h3>
                            <div id="contenedor-candidatos" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
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
            // 1. Configuración de Selectores y Contenedores
            const selects = {
                provincia: document.getElementById('provincia_id'),
                canton: document.getElementById('canton_id'),
                parroquia: document.getElementById('parroquia_id'),
                recinto: document.getElementById('recinto_id'),
                mesa: document.getElementById('mesa_id'),
                dignidad: document.getElementById('dignidad')
            };

            const contenedorCandidatos = document.getElementById('contenedor-candidatos');
            const esDigitador = {{ auth()->user()->esDigitador() ? 'true' : 'false' }};

            // 2. Función genérica para carga de datos (API)
            async function cargarDatos(url, targetSelect, placeholder) {
                if (!url || !targetSelect) return;
                // 1. Bloqueamos el botón antes de empezar
                const btnGuardar = document.querySelector('button[type="submit"]');
                if(btnGuardar) btnGuardar.disabled = true;
                try {
                    const res = await fetch(url);
                    if (!res.ok) throw new Error('Error en la petición');
                    const data = await res.json();
                    
                    targetSelect.innerHTML = `<option value="">${placeholder}</option>`;
                    
                    // Fragmento para mejorar rendimiento de inserción
                    const fragment = document.createDocumentFragment();
                    // Captura el tipo de proceso de la URL actual o del contexto de Laravel
                    const tipoProceso = "{{ $tipo_proceso ?? 'generales' }}"; 

                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.numero ? `Mesa #${item.numero} (${item.genero})` : item.nombre;
                        
                        // INYECCIÓN: Si es el selector de mesa, guardamos sus electores asignados
                        if (targetSelect === selects.mesa && item.num_electores !== undefined) {
                            option.setAttribute('data-electores', item.num_electores);
                        }
                        
                        if (targetSelect === selects.mesa && item.completada) {
                            option.textContent += " ✔ [YA INGRESADA]";
                            option.classList.add('bg-green-50', 'text-green-700');
                            option.disabled = true;
                        }
                        fragment.appendChild(option);
                    });
                    targetSelect.appendChild(fragment);

                    // Lógica de auto-selección para flujo de Digitador
                    if (targetSelect === selects.canton && data.length > 0) {
                        const preSelected = "{{ auth()->user()->canton_id }}";
                        if (preSelected && esDigitador) {
                            selects.canton.value = preSelected;
                            selects.canton.dispatchEvent(new Event('change'));
                        }
                    }
                } catch (e) { 
                    console.error("Error cargando datos:", e); 
                } finally {
                    // 2. Liberamos el botón al terminar (con o sin error)
                    if(btnGuardar) btnGuardar.disabled = false;
                }
            }

            // 3. Función para actualizar la grilla de candidatos (CORREGIDA Y PROTEGIDA)
            async function actualizarCandidatos() {
                const d = selects.dignidad?.value.trim();
                const c = selects.canton?.value.trim();
                const p = selects.parroquia?.value.trim();
                const prov = selects.provincia?.value.trim();

                if (!d) return;

                // Validación de contexto para dignidades locales
                if ((d.includes('alcalde') || d.includes('concejal')) && (!c || c === "")) {
                    console.warn("Filtro de ubicación incompleto para esta dignidad.");
                    return; 
                }

                // --- [INICIO DE INSERCIÓN] ---
                const btnGuardar = document.querySelector('button[type="submit"]');
                if(btnGuardar) btnGuardar.disabled = true; // Bloqueamos el botón mientras carga la API
                // --- [FIN DE INSERCIÓN] ---

                contenedorCandidatos.innerHTML = '<div class="col-span-full text-center py-10 italic">Buscando listas electorales...</div>';

                try {
                    const url = `/api/get-candidatos-filtrados?dignidad=${d}&provincia_id=${prov}&canton_id=${c}&parroquia_id=${p}`;
                    
                    const res = await fetch(url);
                    if (!res.ok) throw new Error('Error en la respuesta de candidatos');
                    
                    const data = await res.json();
                    
                    if (data.length === 0) {
                        contenedorCandidatos.innerHTML = '<div class="col-span-full text-center py-10 text-red-500 font-bold">No se encontraron candidatos para esta ubicación.</div>';
                        return;
                    }

                    let htmlContent = '';
                    data.forEach(cand => {
                        let path = cand.foto ? cand.foto : '';
                        path = path.replace('public/', '').replace('/storage/', '').replace('storage/', '');
                        const foto = path 
                            ? `/storage/${path}` 
                            : `https://ui-avatars.com/api/?name=${encodeURIComponent(cand.nombre)}&background=4f46e5&color=fff`;

                        htmlContent += `
                            <div class="flex flex-col p-4 bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition h-full">
                                <div class="flex flex-col items-center text-center mb-3">
                                    <div class="w-24 h-24 mb-3 overflow-hidden rounded-full border-2 border-indigo-100 shadow-sm bg-gray-50 flex-shrink-0">
                                        <img src="${foto}" 
                                            class="w-full h-full object-cover object-top" 
                                            style="aspect-ratio: 1 / 1;" 
                                            alt="Foto">
                                    </div>
                                    <div class="w-full flex flex-col justify-center min-h-[85px]">
                                        <p class="text-[10px] font-bold text-indigo-500 uppercase truncate px-1">
                                            ${cand.partido?.nombre || 'Independiente'}
                                        </p>
                                        <p class="text-xs font-black text-gray-800 uppercase leading-tight my-1 px-1 h-8 flex items-center justify-center">
                                            ${cand.nombre}
                                        </p>
                                        <div class="flex justify-center mt-1">
                                            <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-3 py-0.5 rounded-full whitespace-nowrap">
                                                LISTA: ${cand.partido?.siglas || cand.partido_id || 'N/A'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-auto pt-3 border-t border-gray-100">
                                    <label class="block text-[10px] text-gray-400 uppercase font-bold mb-1 text-center">Total Votos</label>
                                    <input type="number" name="votos_candidatos[${cand.id}]" value="0" min="0" inputmode="numeric"
                                        class="w-full text-lg font-black text-center border-gray-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 py-2">
                                </div>
                            </div>`;
                    });
                    contenedorCandidatos.innerHTML = htmlContent;

                } catch (e) { 
                    console.error(e);
                    contenedorCandidatos.innerHTML = '<div class="col-span-full text-center py-10 text-red-600">Error crítico al cargar candidatos.</div>'; 
                } finally {
                    // --- [INICIO DE INSERCIÓN] ---
                    // Liberamos el botón al terminar la operación
                    if(btnGuardar) btnGuardar.disabled = false; 
                    // Ejecutamos la validación final para verificar si el formulario debe estar activo
                    validarTotales();
                    // --- [FIN DE INSERCIÓN] ---
                }
            }

            // 4. Manejadores de Eventos (Listeners)
            if (selects.provincia) {
                selects.provincia.addEventListener('change', () => {
                    if (selects.provincia.value) {
                        cargarDatos(`/api/cantones/${selects.provincia.value}`, selects.canton, 'Seleccione Cantón...');
                    }
                });
            }

            if (selects.canton) {
                selects.canton.addEventListener('change', () => {
                    if (selects.canton.value) {
                        cargarDatos(`/api/parroquias/${selects.canton.value}`, selects.parroquia, 'Seleccione Parroquia...');
                    }
                });
            }

            if (selects.parroquia) {
                selects.parroquia.addEventListener('change', () => {
                    if (selects.parroquia.value) {
                        cargarDatos(`/api/recintos/${selects.parroquia.value}`, selects.recinto, 'Seleccione Recinto...');
                    }
                });
            }
            
           // Sincronización de rutas territoriales para Admin
            const refrescarMesas = () => {
                if (selects.recinto.value && selects.dignidad.value) {
                    const tipoProceso = "{{ $tipo_proceso ?? 'generales' }}";
                    // 1. Cargamos las mesas correspondientes primero
                    cargarDatos(`/api/mesas/${selects.recinto.value}?dignidad=${selects.dignidad.value}&tipo_proceso=${tipoProceso}`, selects.mesa, 'Seleccione Mesa...');
                    // 2. Limpiamos el contenedor de candidatos hasta que elija la mesa explícitamente
                    contenedorCandidatos.innerHTML = '<div class="col-span-full text-center py-12 text-gray-400">Seleccione una Junta Receptora (Mesa) para cargar las listas.</div>';
                    validarTotales();
                }
            };

            selects.recinto.addEventListener('change', refrescarMesas);
            selects.dignidad.addEventListener('change', refrescarMesas);

            // DETONANTE DE MESA: Autocompletado, bloqueo de padrón y carga real de candidatos
            if (selects.mesa) {
                selects.mesa.addEventListener('change', () => {
                    const selectedOption = selects.mesa.options[selects.mesa.selectedIndex];
                    const inputSufragantes = document.getElementById('sufragantes');
                    
                    if (selectedOption && inputSufragantes) {
                        const electores = selectedOption.getAttribute('data-electores');
                        
                        if (electores) {
                            inputSufragantes.value = electores;
                            inputSufragantes.readOnly = true;
                            inputSufragantes.classList.add('bg-gray-100', 'cursor-not-allowed', 'select-none');
                            
                            // Una vez que la mesa es válida y tenemos los electores, cargamos sus candidatos
                            actualizarCandidatos();
                        } else {
                            // Limpieza en caso de deselección
                            inputSufragantes.value = '0';
                            inputSufragantes.readOnly = false;
                            inputSufragantes.classList.remove('bg-gray-100', 'cursor-not-allowed', 'select-none');
                            contenedorCandidatos.innerHTML = '<div class="col-span-full p-12 text-center bg-gray-50 rounded-3xl border-2 border-dashed border-gray-200 text-gray-400">Seleccione la ubicación y mesa para cargar las listas.</div>';
                            validarTotales();
                        }
                    }
                });
            }

            // 5. Control de Disparo Inicial Seguro (Bootstrapping)
            if (esDigitador) {
                setTimeout(async () => {
                    const recId = document.getElementById('recinto_id')?.value;
                    const dig = document.getElementById('dignidad')?.value;
                    
                    if (recId && dig) {
                        const tipoProceso = "{{ $tipo_proceso ?? 'generales' }}";
                        // 1. Aseguramos primero la carga del selector de mesas de su perfil
                        await cargarDatos(`/api/mesas/${recId}?dignidad=${dig}&tipo_proceso=${tipoProceso}`, selects.mesa, 'Seleccione Mesa...');
                        
                        // 2. Ejecutamos la llamada de candidatos bajo un contexto controlado
                        actualizarCandidatos();
                    }
                }, 100);
            } else {
                if (selects.provincia && selects.provincia.value) {
                    selects.provincia.dispatchEvent(new Event('change'));
                }
            }
            // FUNCIÓN DE VALIDACIÓN PROFESIONAL BLINDADA
            function validarTotales() {
                const inputSufragantes = document.getElementById('sufragantes');
                const totalSufragantes = inputSufragantes ? (parseInt(inputSufragantes.value) || 0) : 0;
                
                const btnGuardar = document.querySelector('button[type="submit"]');
                const alerta = document.getElementById('alerta-validacion');
                const textoVal = document.getElementById('texto-validacion');
                const conteoVal = document.getElementById('conteo-actual');

                // 1. Sumar votos de candidatos dinámicos
                let sumaCandidatos = 0;
                document.querySelectorAll('input[name^="votos_candidatos"]').forEach(input => {
                    sumaCandidatos += parseInt(input.value) || 0;
                });

                // 2. Sumar Blancos y Nulos con validación de existencia en el DOM
                const inputBlancos = document.getElementById('votos_blancos');
                const inputNulos = document.getElementById('votos_nulos');
                
                const blancos = inputBlancos ? (parseInt(inputBlancos.value) || 0) : 0;
                const nulos = inputNulos ? (parseInt(inputNulos.value) || 0) : 0;

                const sumaTotal = sumaCandidatos + blancos + nulos;
                
                if (conteoVal) {
                    conteoVal.innerText = `${sumaTotal} / ${totalSufragantes}`;
                }

                // 3. Lógica de Semaforización y Bloqueo
                if (!btnGuardar || !alerta || !textoVal) return;

                if (totalSufragantes === 0) {
                    alerta.className = "mt-4 p-3 rounded-xl border flex justify-between items-center bg-gray-50 text-gray-500 border-gray-200";
                    textoVal.innerText = "Ingrese total de sufragantes";
                    btnGuardar.disabled = true;
                    btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
                } else if (sumaTotal > totalSufragantes) {
                    alerta.className = "mt-4 p-3 rounded-xl border flex justify-between items-center bg-red-100 text-red-700 border-red-300 animate-pulse";
                    textoVal.innerText = "⚠️ EXCESO DE VOTOS";
                    btnGuardar.disabled = true;
                    btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
                } else if (sumaTotal < totalSufragantes) {
                    alerta.className = "mt-4 p-3 rounded-xl border flex justify-between items-center bg-yellow-50 text-yellow-700 border-yellow-300";
                    textoVal.innerText = "Faltan votos por ingresar";
                    btnGuardar.disabled = true;
                    btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    alerta.className = "mt-4 p-3 rounded-xl border flex justify-between items-center bg-green-100 text-green-800 border-green-400 shadow-inner";
                    textoVal.innerText = "✅ CUADRADO PERFECTO";
                    btnGuardar.disabled = false;
                    btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }

            // Escuchar cambios en CUALQUIER input del formulario para validar al instante
            document.getElementById('form-acta').addEventListener('input', (e) => {
                validarTotales();
            });
            // --- EVITAR ENVÍO ACCIDENTAL CON ENTER ---
            document.getElementById('form-acta').addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</x-app-layout>