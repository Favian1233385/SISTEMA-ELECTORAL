<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ Auth::user()->esAdminGeneral() ? 'Registrar Mando Provincial/Cantonal' : 'Registrar Personal de Equipo' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 border-t-4 border-indigo-500">
                
                {{-- Manejo de Errores en Servidor --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
                        <p class="font-bold text-sm">Hay errores en el formulario:</p>
                        <ul class="mt-1 list-disc list-inside text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Nombre Completo</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 text-sm" placeholder="Ej. Juan Pérez" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Correo Electrónico</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 text-sm" placeholder="usuario@sistema.com" required>
                            </div>
                        </div>

                        <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-indigo-900 text-sm font-bold mb-2">Función en el Sistema (Rol)</label>
                                <select name="role" id="role_select" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    {{-- El SuperAdmin puede delegar mandos de cualquier nivel jerárquico --}}
                                    @if(Auth::user()->esAdminGeneral())
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador General</option>
                                        <option value="admin_provincial" {{ old('role') == 'admin_provincial' ? 'selected' : '' }}>Administrador Provincial</option>
                                        <option value="admin_cantonal" {{ old('role') == 'admin_cantonal' ? 'selected' : '' }}>Administrador Cantonal</option>
                                        <option value="admin_parroquial" {{ old('role') == 'admin_parroquial' ? 'selected' : '' }}>Administrador Parroquial</option>
                                    
                                    {{-- El Admin Provincial solo delega mandos de su propia región --}}
                                    @elseif(Auth::user()->esAdminProvincial())
                                        <option value="admin_cantonal" {{ old('role') == 'admin_cantonal' ? 'selected' : '' }}>Administrador Cantonal</option>
                                        <option value="admin_parroquial" {{ old('role') == 'admin_parroquial' ? 'selected' : '' }}>Administrador Parroquial</option>
                                    
                                    {{-- El Admin Cantonal solo delega operadores parroquiales directos --}}
                                    @elseif(Auth::user()->esAdminCantonal())
                                        <option value="admin_parroquial" {{ old('role') == 'admin_parroquial' ? 'selected' : '' }} selected>Administrador Parroquial</option>
                                    @endif
                                </select>
                            </div>

                            <div>
                                <label class="block text-indigo-900 text-sm font-bold mb-2">Dignidad Asignada (Control)</label>
                                <select name="dignidad_asignada" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    <option value="todas">Todas (Recomendado para Mandos)</option>
                                    <option value="prefecto">Prefecto</option>
                                    <option value="alcalde">Alcalde</option>
                                    <option value="junta_parroquial">Junta Parroquial</option>
                                    <option value="concejal">Concejales</option>
                                </select>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-xs font-bold text-gray-500 mb-4 uppercase tracking-wider">Jurisdicción y Territorio de Cobertura</p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                
                                {{-- Selector de Provincia exclusivo para el SuperAdmin General --}}
                                @if(Auth::user()->esAdminGeneral())
                                    <div id="div_provincia">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Provincia</label>
                                        <select name="provincia_id" id="provincia_select" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                            <option value="">Nacional (Acceso Total)</option>
                                            @foreach($provincias as $prov)
                                                <option value="{{ $prov->id }}" {{ old('provincia_id') == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                {{-- Selector de Cantón para SuperAdmin y Administradores Provinciales --}}
                                @if(Auth::user()->esAdminGeneral() || Auth::user()->esAdminProvincial())
                                    <div id="div_canton">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Cantón</label>
                                        <select name="canton_id" id="canton_select" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                            <option value="">Seleccione Cantón...</option>
                                            @foreach($cantones as $canton)
                                                <option value="{{ $canton->id }}" {{ old('canton_id') == $canton->id ? 'selected' : '' }}>{{ $canton->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                {{-- Selector de Parroquia visible para todos los niveles de creación manual --}}
                                <div id="div_parroquia">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Parroquia</label>
                                    <select name="parroquia_id" id="parroquia_select" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="">Seleccione Parroquia...</option>
                                        @foreach($parroquias as $parroquia)
                                            <option value="{{ $parroquia->id }}" {{ old('parroquia_id') == $parroquia->id ? 'selected' : '' }}>{{ $parroquia->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t pt-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña de Acceso</label>
                                <input type="password" name="password" class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Mínimo 8 caracteres" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Confirmar Contraseña</label>
                                <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Repita la contraseña" required>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-10">
                        <a href="{{ route('users.index') }}" class="px-6 py-2 rounded-lg text-gray-600 hover:bg-gray-100 font-bold transition text-sm">Cancelar</a>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-2 rounded-lg font-bold shadow-lg active:scale-95 transition text-sm">
                            Registrar en Sistema
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role_select');
        const provinciaSelect = document.getElementById('provincia_select');
        const cantonSelect = document.getElementById('canton_select');
        const parroquiaSelect = document.getElementById('parroquia_select');
        
        const divProvincia = document.getElementById('div_provincia');
        const divCanton = document.getElementById('div_canton');
        const divParroquia = document.getElementById('div_parroquia');

        function ajustarCampos() {
            if (!roleSelect) return;
            const role = roleSelect.value;

            // Reiniciar visibilidad base por defecto
            if (divCanton) divCanton.style.display = 'block';
            if (divParroquia) divParroquia.style.display = 'block';

            // Reglas de visualización semántica según el rol elegido
            if (role === 'admin' || role === 'admin_general') {
                if (divCanton) divCanton.style.display = 'none';
                if (divParroquia) divParroquia.style.display = 'none';
            } else if (role === 'admin_provincial') {
                if (divCanton) divCanton.style.display = 'none';
                if (divParroquia) divParroquia.style.display = 'none';
            } else if (role === 'admin_cantonal') {
                if (divCanton) divCanton.style.display = 'block';
                if (divParroquia) divParroquia.style.display = 'none';
            } else if (role === 'admin_parroquial') {
                if (divCanton) divCanton.style.display = 'block';
                if (divParroquia) divParroquia.style.display = 'block';
            }
        }

        roleSelect.addEventListener('change', ajustarCampos);
        ajustarCampos(); // Ejecución inicial al cargar la página

        // --- Carga dinámica de Cantones por Provincia (Para SuperAdmin) ---
        if (provinciaSelect) {
            provinciaSelect.addEventListener('change', function() {
                const provinciaId = this.value;
                if (!cantonSelect) return;

                cantonSelect.innerHTML = '<option value="">Cargando cantones...</option>';
                if (parroquiaSelect) parroquiaSelect.innerHTML = '<option value="">Seleccione un cantón primero</option>';

                if (!provinciaId) {
                    cantonSelect.innerHTML = '<option value="">Nacional / Sin provincia</option>';
                    return;
                }

                fetch(`/api/cantones/${provinciaId}`)
                    .then(response => response.json())
                    .then(data => {
                        cantonSelect.innerHTML = '<option value="">Seleccione Cantón...</option>';
                        data.forEach(canton => {
                            cantonSelect.innerHTML += `<option value="${canton.id}">${canton.nombre}</option>`;
                        });
                    });
            });
        }

        // --- Carga de Parroquias según el Cantón seleccionado ---
        if (cantonSelect) {
            cantonSelect.addEventListener('change', function() {
                const cantonId = this.value;
                if (!parroquiaSelect) return;

                parroquiaSelect.innerHTML = '<option value="">Cargando parroquias...</option>';

                if (!cantonId) {
                    parroquiaSelect.innerHTML = '<option value="">Seleccione Cantón...</option>';
                    return;
                }

                fetch(`/api/parroquias/${cantonId}`)
                    .then(response => response.json())
                    .then(data => {
                        parroquiaSelect.innerHTML = '<option value="">Seleccione Parroquia...</option>';
                        data.forEach(parroquia => {
                            parroquiaSelect.innerHTML += `<option value="${parroquia.id}">${parroquia.nombre}</option>`;
                        });
                    });
            });
        }
    });
    </script>
</x-app-layout>