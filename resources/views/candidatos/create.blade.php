<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Inscripción de Nuevos Candidatos') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <div class="p-8">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('candidatos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-indigo-700 border-b pb-2">Datos del Candidato</h3>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Dignidad a la que postula</label>
                                    <select name="dignidad" id="select_dignidad" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Seleccione dignidad...</option>
                                        @foreach($dignidadesDisponibles as $key => $value)
                                            <option value="{{ $key }}" {{ old('dignidad') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Partido Político</label>
                                    <select name="partido_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="">Seleccione Organización...</option>
                                        @foreach($partidos as $partido)
                                            <option value="{{ $partido->id }}">{{ $partido->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Foto del Candidato</label>
                                    <input type="file" name="foto" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-indigo-700 border-b pb-2">Ubicación de la Dignidad</h3>
                                
                                <div id="div_provincia">
                                    <label class="block text-sm font-medium text-gray-700">Provincia</label>
                                    <select id="provincia_id" name="provincia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ !auth()->user()->esAdminGeneral() ? 'bg-gray-100 pointer-events-none' : '' }}" required>
                                        @if(auth()->user()->esAdminGeneral()) <option value="">Seleccione Provincia...</option> @endif
                                        @foreach($provincias as $prov)
                                            <option value="{{ $prov->id }}" {{ (auth()->user()->provincia_id == $prov->id) ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="div_canton" class="transition-all duration-300">
                                    <label class="block text-sm font-medium text-gray-700">Cantón</label>
                                    <select id="canton_id" name="canton_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ auth()->user()->esAdminCantonal() || auth()->user()->esAdminParroquial() ? 'bg-gray-100 pointer-events-none' : '' }}">
                                        @if(auth()->user()->esAdminCantonal() || auth()->user()->esAdminParroquial())
                                            @foreach($cantones as $c)
                                                <option value="{{ $c->id }}" selected>{{ $c->nombre }}</option>
                                            @endforeach
                                        @else
                                            <option value="">Seleccione Cantón...</option>
                                        @endif
                                    </select>
                                </div>

                                <div id="div_parroquia" class="transition-all duration-300">
                                    <label class="block text-sm font-medium text-gray-700">Parroquia</label>
                                    <select id="parroquia_id" name="parroquia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ auth()->user()->esAdminParroquial() ? 'bg-gray-100 pointer-events-none' : '' }}">
                                        @if(auth()->user()->esAdminParroquial())
                                            @foreach($parroquias as $p)
                                                <option value="{{ $p->id }}" selected>{{ $p->nombre }}</option>
                                            @endforeach
                                        @else
                                            <option value="">Seleccione Parroquia...</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition duration-300 shadow-lg">
                                Registrar Candidato Oficial
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectDignidad = document.getElementById('select_dignidad');
            const divCanton = document.getElementById('div_canton');
            const divParroquia = document.getElementById('div_parroquia');
            const provinciaSelect = document.getElementById('provincia_id');
            const cantonSelect = document.getElementById('canton_id');
            const parroquiaSelect = document.getElementById('parroquia_id');

            // 1. Lógica de Visibilidad de campos según Dignidad
            function actualizarVisibilidad() {
                let dignidad = selectDignidad.value;
                
                // Reset
                divCanton.style.display = 'block';
                divParroquia.style.display = 'block';

                if (dignidad === 'Prefecto') {
                    divCanton.style.display = 'none';
                    divParroquia.style.display = 'none';
                } else if (['Alcalde', 'Concejal Urbano', 'Concejal Rural'].includes(dignidad)) {
                    divParroquia.style.display = 'none';
                }
            }

            selectDignidad.addEventListener('change', actualizarVisibilidad);
            actualizarVisibilidad(); // Ejecutar al cargar por si hay un "old" value

            // 2. Carga de Cantones (Solo si el usuario es Admin General o Provincial)
            @if(auth()->user()->esAdminGeneral() || auth()->user()->esAdminProvincial())
            provinciaSelect.addEventListener('change', function() {
                let provinciaId = this.value;
                cantonSelect.innerHTML = '<option value="">Cargando...</option>';
                
                if (provinciaId) {
                    fetch(`/api/cantones/${provinciaId}`)
                        .then(res => res.json())
                        .then(data => {
                            cantonSelect.innerHTML = '<option value="">Seleccione Cantón...</option>';
                            data.forEach(c => {
                                cantonSelect.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
                            });
                        });
                }
            });
            @endif

            // 3. Carga de Parroquias (Solo si no es parroquial ya fijo)
            @if(!auth()->user()->esAdminParroquial())
            cantonSelect.addEventListener('change', function() {
                let cantonId = this.value;
                parroquiaSelect.innerHTML = '<option value="">Cargando...</option>';
                
                if (cantonId) {
                    fetch(`/api/parroquias/${cantonId}`)
                        .then(res => res.json())
                        .then(data => {
                            parroquiaSelect.innerHTML = '<option value="">Seleccione Parroquia...</option>';
                            data.forEach(p => {
                                parroquiaSelect.innerHTML += `<option value="${p.id}">${p.nombre}</option>`;
                            });
                        });
                }
            });
            @endif
        });
    </script>
</x-app-layout>