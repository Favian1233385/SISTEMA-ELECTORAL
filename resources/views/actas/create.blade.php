<x-app-layout>
    @if ($errors->any())
        <div class="w-full max-w-md mx-auto mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="form-acta" method="POST" action="{{ route('actas.store') }}" class="w-full max-w-md mx-auto p-4 space-y-6 bg-white shadow rounded-lg">
        @csrf
        <input type="hidden" id="dignidad" name="dignidad" value="{{ $user->dignidad_asignada ?? '' }}">
        <input type="hidden" id="ausentismo" name="ausentismo" value="0">
        
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-gray-800 border-b pb-2">1. Ubicación Electoral</h2>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
                <select id="provincia_id" name="provincia_id" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-base">
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cantón</label>
                <select id="canton_id" name="canton_id" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-base" disabled>
                    <option value="">Seleccione un cantón...</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parroquia</label>
                <select id="parroquia_id" name="parroquia_id" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-base" disabled>
                    <option value="">Seleccione una parroquia...</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recinto Electoral</label>
                <select id="recinto_id" name="recinto_id" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-base" disabled>
                    <option value="">Seleccione un recinto...</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Junta Receptora del Voto (Mesa)</label>
                <select id="mesa_id" name="mesa_id" class="w-full p-3 bg-gray-50 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-base" disabled>
                    <option value="">Seleccione una mesa...</option>
                </select>
            </div>
        </div>

        <hr class="border-gray-200">

        <div class="space-y-4 bg-gray-50 p-4 rounded-md border border-gray-200">
            <h2 class="text-lg font-bold text-gray-800 pb-1">2. Control del Padrón</h2>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Total Sufragantes en Padrón</label>
                <input type="text" id="total_sufragantes" name="total_sufragantes" 
                    class="w-full p-3 bg-gray-200 font-bold text-center text-gray-800 border border-gray-300 rounded-md text-xl" 
                    value="0" readonly>
                <small class="text-xs text-gray-500 block mt-1">Valor oficial cargado automáticamente para esta mesa.</small>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Votos en Blanco</label>
                <input type="number" id="votos_blancos" name="votos_blancos" min="0" placeholder="0" inputmode="numeric" pattern="[0-9]*"
                    class="w-full p-3 text-center border border-gray-300 rounded-md text-xl focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Votos Nulos</label>
                <input type="number" id="votos_nulos" name="votos_nulos" min="0" placeholder="0" inputmode="numeric" pattern="[0-9]*"
                    class="w-full p-3 text-center border border-gray-300 rounded-md text-xl focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <hr class="border-gray-200">

        <div class="space-y-4">
            <h2 class="text-lg font-bold text-gray-800 border-b pb-2">3. Votos por Candidato</h2>
            <div id="contenedor-candidatos" class="space-y-3">
                <p class="text-sm text-gray-500 text-center py-4">Seleccione una mesa para cargar los candidatos asignados.</p>
            </div>
        </div>

        <div class="pt-4 pb-2 space-y-4">
            <div id="semaforo-validacion" class="w-full p-3 text-center font-bold rounded-md bg-yellow-100 text-yellow-800 text-sm border border-yellow-300">
                Seleccione una mesa para iniciar el control.
            </div>

            <button type="submit" id="btn-guardar-acta" disabled
                class="w-full p-4 bg-gray-400 text-gray-700 font-bold text-lg rounded-md shadow uppercase tracking-wide cursor-not-allowed transition-colors">
                Guardar Acta Oficial
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. DATA INYECTADA DESDE EL BACKEND
            const j = @json($jurisdiccion);

            // 2. MAPEADO DE ELEMENTOS
            const selectProvincia  = document.getElementById('provincia_id');
            const selectCanton     = document.getElementById('canton_id');
            const selectParroquia  = document.getElementById('parroquia_id');
            const selectRecinto    = document.getElementById('recinto_id');
            const selectMesa       = document.getElementById('mesa_id');
            
            const inputSufragantes = document.getElementById('total_sufragantes');
            const inputBlancos     = document.getElementById('votos_blancos'); 
            const inputNulos       = document.getElementById('votos_nulos');
            
            const contenedorCandidatos = document.getElementById('contenedor-candidatos');
            const semaforo             = document.getElementById('semaforo-validacion');
            const btnGuardar           = document.getElementById('btn-guardar-acta');

            // 3. FLUJO POR ROL
            if (j.esDigitador) {
                @if(isset($mesaAsignada) && $mesaAsignada)
                    selectProvincia.innerHTML = `<option value="${j.provincia_id}" selected>{{ $mesaAsignada->recinto->parroquia->canton->provincia->nombre }}</option>`;
                    selectProvincia.disabled = true;

                    selectCanton.innerHTML = `<option value="${j.canton_id}" selected>{{ $mesaAsignada->recinto->parroquia->canton->nombre }}</option>`;
                    selectCanton.disabled = true;

                    selectParroquia.innerHTML = `<option value="${j.parroquia_id}" selected>{{ $mesaAsignada->recinto->parroquia->nombre }}</option>`;
                    selectParroquia.disabled = true;

                    selectRecinto.innerHTML = `<option value="${j.recinto_id}" selected>{{ $mesaAsignada->recinto->nombre }}</option>`;
                    selectRecinto.disabled = true;

                    selectMesa.innerHTML = `<option value="${j.mesa_id}" selected>Mesa N° {{ $mesaAsignada->numero }} - {{ $mesaAsignada->genero }}</option>`;
                    selectMesa.disabled = true;
                    
                    inputSufragantes.value = "{{ $mesaAsignada->num_electores }}";

                    ejecutarCargaCandidatos(j.provincia_id, j.canton_id, j.parroquia_id);
                @else
                    contenedorCandidatos.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error: El digitador no cuenta con una mesa configurada.</p>';
                @endif
            } else {
                selectProvincia.innerHTML = '<option value="">Seleccione una opción...</option>';
                @foreach($provincias as $p)
                    selectProvincia.innerHTML += `<option value="{{ $p->id }}">{{ $p->nombre }}</option>`;
                @endforeach

                selectProvincia.addEventListener('change', () => {
                    reiniciarSelectores([selectCanton, selectParroquia, selectRecinto, selectMesa]);
                    if(selectProvincia.value) cargarSelect(selectCanton, `/actas/cantones/${selectProvincia.value}`);
                });

                selectCanton.addEventListener('change', () => {
                    reiniciarSelectores([selectParroquia, selectRecinto, selectMesa]);
                    if(selectCanton.value) cargarSelect(selectParroquia, `/actas/parroquias/${selectCanton.value}`);
                });

                selectParroquia.addEventListener('change', () => {
                    reiniciarSelectores([selectRecinto, selectMesa]);
                    if(selectParroquia.value) cargarSelect(selectRecinto, `/actas/recintos/${selectParroquia.value}`);
                });

                selectRecinto.addEventListener('change', () => {
                    reiniciarSelectores([selectMesa]);
                    if(selectRecinto.value) cargarSelectMesas(selectMesa, `/actas/mesas/${selectRecinto.value}`);
                });

                selectMesa.addEventListener('change', function() {
                    if (!this.value) {
                        limpiarSeccionVotos();
                        return;
                    }
                    const opcionSeleccionada = this.options[this.selectedIndex];
                    inputSufragantes.value = opcionSeleccionada.getAttribute('data-electores') || 0;

                    document.getElementById('dignidad').value = j.dignidad_asignada || '{{ request()->query("dignidad") }}';

                    ejecutarCargaCandidatos(selectProvincia.value, selectCanton.value, selectParroquia.value);
                });
            }

            // 4. CARGA ASÍNCRONA DE CANDIDATOS
            function ejecutarCargaCandidatos(provId, cantId, parrId) {
                contenedorCandidatos.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Cargando candidatos asignados...</p>';
                
                const urlCandidatos = `{{ route('candidatos.filtrados') }}?provincia_id=${provId}&canton_id=${cantId}&parroquia_id=${parrId}`;
                
                fetch(urlCandidatos)
                    .then(res => {
                        if (!res.ok) throw new Error(`Status ${res.status}`);
                        return res.json();
                    })
                    .then(candidatos => {
                        renderizerCandidatos(candidatos);
                        calcularCuadradoPerfecto();
                    })
                    .catch(err => {
                        console.error("Error Fetch:", err);
                        contenedorCandidatos.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error al cargar el listado de candidatos.</p>';
                    });
            }

            // 5. ESCUCHA DE ENTRADA DE DATOS
            document.getElementById('form-acta').addEventListener('input', function(e) {
                if (e.target.matches('#votos_blancos, #votos_nulos, .input-voto-candidato')) {
                    if (parseInt(e.target.value) < 0 || e.target.value === '') {
                        e.target.value = 0;
                    }
                    calcularCuadradoPerfecto();
                }
            });

            // 6. CONTROL MATEMÁTICO
            function calcularCuadradoPerfecto() {
                const limiteElectores = parseInt(inputSufragantes.value) || 0;
                if (limiteElectores === 0) {
                    actualizarSemaforo('Seleccione una mesa para iniciar el control.', 'yellow');
                    establecerEstadoBoton('inicial');
                    return;
                }

                const votosBlancos = parseInt(inputBlancos.value) || 0;
                const votosNulos   = parseInt(inputNulos.value) || 0;
                
                let sumaCandidatos = 0;
                const inputsCandidatos = document.querySelectorAll('.input-voto-candidato');
                inputsCandidatos.forEach(input => {
                    sumaCandidatos += parseInt(input.value) || 0;
                });

                const totalVotosActa = sumaCandidatos + votosBlancos + votosNulos;

                if (totalVotosActa === limiteElectores) {
                    actualizarSemaforo('✓ Mesa Cuadrada. Todos los electores han sufragado.', 'green');
                    establecerEstadoBoton('valido');
                } 
                else if (totalVotosActa < limiteElectores) {
                    const ausentismo = limiteElectores - totalVotosActa;
                    actualizarSemaforo(`✓ Mesa Cuadrada con Ausentismo (${ausentismo} electores ausentes).`, 'green');
                    document.getElementById('ausentismo').value = ausentismo;
                    establecerEstadoBoton('valido');
                } 
                else {
                    const exceso = totalVotosActa - limiteElectores;
                    actualizarSemaforo(`⚠ Inconsistencia: Exceso de votos. Hay ${exceso} votos por encima del padrón.`, 'red');
                    establecerEstadoBoton('inconsistente');
                }
            }

            // 7. COMPONENTES AUXILIARES
            function cargarSelectMesas(selectElement, url) {
                selectElement.innerHTML = '<option value="">Cargando mesas...</option>';
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        selectElement.innerHTML = '<option value="">Seleccione una mesa...</option>';
                        data.forEach(mesa => {
                            let option = document.createElement('option');
                            option.value = mesa.id;
                            option.innerText = `Mesa N° ${mesa.numero} - ${mesa.genero}`;
                            option.setAttribute('data-electores', mesa.num_electores);
                            selectElement.appendChild(option);
                        });
                        selectElement.disabled = false;
                    })
                    .catch(() => {
                        selectElement.innerHTML = '<option value="">Error al cargar mesas</option>';
                    });
            }

            function renderizerCandidatos(candidatos) {
                contenedorCandidatos.innerHTML = '';
                if (!candidatos || candidatos.length === 0) {
                    contenedorCandidatos.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">No hay candidatos configurados para la dignidad o el proceso del usuario.</p>';
                    return;
                }

                const baseUrl = "{{ url('/') }}"; 
                const defaultAvatar = "{{ asset('img/default-avatar.png') }}";

                candidatos.forEach(cand => {
                    const card = document.createElement('div');
                    card.className = "flex flex-col p-4 bg-white border border-gray-200 rounded-lg shadow-sm space-y-3";
                    
                    let logoUrl = defaultAvatar;
                    let rutaFoto = cand.foto || (cand.partido ? cand.partido.logo_url : null);

                    if (rutaFoto) {
                        if (rutaFoto.startsWith('http')) {
                            logoUrl = rutaFoto;
                        } else {
                            logoUrl = rutaFoto.startsWith('/') ? baseUrl + rutaFoto : baseUrl + '/' + rutaFoto;
                        }
                    }

                    const partidoNombre = cand.partido ? cand.partido.nombre : 'Independiente';

                    card.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex-shrink-0 overflow-hidden border border-gray-300">
                                <img src="${logoUrl}" class="w-full h-full object-cover" alt="Logo" onerror="this.src='${defaultAvatar}'">
                            </div>
                            <div class="flex flex-col">
                                <span class="text-base font-bold text-gray-900">${cand.nombre}</span>
                                <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">${partidoNombre}</span>
                            </div>
                        </div>
                        <input type="number" name="votos_candidatos[${cand.id}]" min="0" placeholder="0" inputmode="numeric" pattern="[0-9]*"
                            class="input-voto-candidato w-full p-3 text-center border border-gray-300 rounded-md text-xl font-bold bg-blue-50 text-blue-900 focus:ring-2 focus:ring-blue-500 transition-all">
                    `;
                    contenedorCandidatos.appendChild(card);
                });
            }

            function cargarSelect(selectElement, url) {
                selectElement.innerHTML = '<option value="">Cargando...</option>';
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        selectElement.innerHTML = `<option value="">Seleccione una opción...</option>`;
                        data.forEach(item => {
                            let option = document.createElement('option');
                            option.value = item.id;
                            option.innerText = item.nombre;
                            selectElement.appendChild(option);
                        });
                        selectElement.disabled = false;
                    })
                    .catch(err => {
                        console.error(err);
                        selectElement.innerHTML = '<option value="">Error al cargar</option>';
                    });
            }

            function reiniciarSelectores(selectores) {
                selectores.forEach(select => {
                    select.innerHTML = `<option value="">Seleccione una opción...</option>`;
                    select.disabled = true;
                });
                limpiarSeccionVotos();
            }

            function limpiarSeccionVotos() {
                inputSufragantes.value = "0";
                inputBlancos.value = "0";
                inputNulos.value = "0";
                contenedorCandidatos.innerHTML = '<p class="text-sm text-gray-500 text-center py-4">Seleccione una mesa para cargar los candidatos asignados.</p>';
                calcularCuadradoPerfecto();
            }

            function actualizarSemaforo(mensaje, color) {
                semaforo.className = "w-full p-3 text-center font-bold rounded-md text-sm transition-all ";
                if (color === 'yellow') {
                    semaforo.className += "bg-yellow-100 text-yellow-800 border border-yellow-300";
                } else if (color === 'red') {
                    semaforo.className += "bg-red-100 text-red-800 border border-red-300";
                } else if (color === 'green') {
                    semaforo.className += "bg-emerald-100 text-emerald-800 border border-emerald-300";
                }
                semaforo.innerText = mensaje;
            }

            // Centralización semántica del control del botón
            function establecerEstadoBoton(estado) {
                if (!btnGuardar) return;

                // Remover todas las variantes previas de estilo
                btnGuardar.classList.remove(
                    'bg-gray-400', 'text-gray-700', 'cursor-not-allowed',
                    'bg-emerald-600', 'hover:bg-emerald-700', 'text-white',
                    'bg-red-600', 'hover:bg-red-700'
                );

                if (estado === 'inicial') {
                    btnGuardar.disabled = true;
                    btnGuardar.classList.add('bg-gray-400', 'text-gray-700', 'cursor-not-allowed');
                    btnGuardar.innerText = "Guardar Acta Oficial";
                } 
                else if (estado === 'valido') {
                    btnGuardar.disabled = false;
                    btnGuardar.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'text-white');
                    btnGuardar.innerText = "Guardar Acta Oficial";
                } 
                else if (estado === 'inconsistente') {
                    btnGuardar.disabled = false; // Permitir guardar con inconsistencia bajo advertencia visual
                    btnGuardar.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white');
                    btnGuardar.innerText = "Guardar Acta (Con Inconsistencias)";
                }
            }

            // Interceptor de submit
            document.getElementById('form-acta').addEventListener('submit', function(e) {
                const inputs = document.querySelectorAll('.input-voto-candidato');
                if (inputs.length === 0) {
                    alert("ERROR FRONTEND: No hay candidatos renderizados en el formulario. El envío se canceló.");
                    e.preventDefault();
                    return;
                }
                
                let datosCargados = false;
                inputs.forEach(i => {
                    if(i.value !== "" && parseInt(i.value) > 0) datosCargados = true;
                });
                
                if(!datosCargados) {
                    const confirmar = confirm("AVISO: Todos los campos de candidatos están en cero o vacíos. ¿Desea continuar con el registro?");
                    if (!confirmar) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</x-app-layout>