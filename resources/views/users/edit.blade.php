<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Modificar Administrador: ') }} {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                {{-- Manejo de Errores de Validación de Laravel --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
                        <p class="font-bold mb-1">Por favor corrige los siguientes errores:</p>
                        <ul class="list-disc list-inside text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="p-6 bg-white rounded-xl border border-gray-200 mb-6 shadow-sm">
                        <h3 class="text-md font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Credenciales e Identidad del Usuario
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico (Login)</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Contraseña</label>
                                {{-- INICIO DEL CAMBIO --}}
                                <div class="relative mt-1 rounded-md shadow-sm">
                                    <input type="password" name="password" id="password_input" class="w-full border-gray-300 pr-10 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="••••••••">
                                    <button type="button" id="toggle_password_btn" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" id="eye_icon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                                {{-- FIN DEL CAMBIO --}}
                                <p class="mt-1 text-xs text-gray-400 italic">Dejar en blanco si desea conservar la contraseña actual.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nueva Contraseña</label>
                                <input type="password" name="password_confirmation" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <h3 class="text-md font-bold text-gray-800 mb-4 flex items-center border-b pb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Nivel de Privilegios Administrativos
                        </h3>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Asignar Rol Jerárquico</label>
                        <select name="role" id="role_select" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="admin" {{ old('role', $user->role) === 'admin' || old('role', $user->role) === 'admin_general' ? 'selected' : '' }}>Súper Administrador (Acceso Completo)</option>
                            <option value="admin_provincial" {{ old('role', $user->role) === 'admin_provincial' ? 'selected' : '' }}>Administrador Provincial</option>
                            <option value="admin_cantonal" {{ old('role', $user->role) === 'admin_cantonal' ? 'selected' : '' }}>Administrador Cantonal</option>
                            <option value="admin_parroquial" {{ old('role', $user->role) === 'admin_parroquial' ? 'selected' : '' }}>Administrador Parroquial</option>
                        </select>
                    </div>

                    <div id="territorio_container" class="p-6 bg-gray-50 rounded-xl border border-gray-200 mb-6 shadow-sm">
                        <h3 class="text-md font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Jurisdicción Territorial Asignada
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Provincia</label>
                                <select name="provincia_id" id="provincia_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-gray-100" readonly>
                                    @foreach($provincias as $prov)
                                        <option value="{{ $prov->id }}" {{ $user->provincia_id == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Cantón</label>
                                <select name="canton_id" id="canton_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">-- Toda la Provincia --</option>
                                    @foreach($cantones as $canton)
                                        <option value="{{ $canton->id }}" {{ $user->canton_id == $canton->id ? 'selected' : '' }}>{{ $canton->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Parroquia</label>
                                <select name="parroquia_id" id="parroquia_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">-- Todo el Cantón --</option>
                                    @foreach($parroquias as $parroquia)
                                        <option value="{{ $parroquia->id }}" {{ $user->parroquia_id == $parroquia->id ? 'selected' : '' }}>{{ $parroquia->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 italic">
                            * Las restricciones territoriales definen qué datos electorales podrá auditar este usuario en el sistema.
                        </p>
                    </div>

                    <div class="flex justify-end items-center gap-4 border-t pt-6">
                        <a href="{{ route('users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                            Cancelar y volver
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Guardar Cambios Operativos
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Lógica Script Dinámica para sincronización de selects vía Axios/Fetch --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cantonSelect = document.getElementById('canton_id');
            const parroquiaSelect = document.getElementById('parroquia_id');

            cantonSelect.addEventListener('change', function () {
                const cantonId = this.value;
                
                // Limpiar select de parroquias
                parroquiaSelect.innerHTML = '<option value="">-- Todo el Cantón --</option>';

                if (!cantonId) return;

                // Llamada a la API nativa configurada en tus rutas web
                fetch(`/api/cantones/${cantonId}/parroquias`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(parroquia => {
                            const option = document.createElement('option');
                            option.value = parroquia.id;
                            option.textContent = parroquia.nombre;
                            parroquiaSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error al cargar parroquias dinámicas:', error));
            });

            // --- NUEVA LÓGICA PARA VISUALIZAR CONTRASEÑA ---
            const passwordInput = document.getElementById('password_input');
            const togglePasswordBtn = document.getElementById('toggle_password_btn');
            const eyeIcon = document.getElementById('eye_icon');

            togglePasswordBtn.addEventListener('click', function () {
                // Alternar el atributo type entre password y text
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    // Cambiar el diseño del icono a "ojo tachado" (ocultar)
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    `;
                } else {
                    passwordInput.type = 'password';
                    // Regresar al diseño del icono de "ojo abierto" (mostrar)
                    eyeIcon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    `;
                }
            }); // <-- Aquí se cierra correctamente el evento click
        }); // <-- Aquí se cierra correctamente el DOMContentLoaded
    </script>
</x-app-layout>
