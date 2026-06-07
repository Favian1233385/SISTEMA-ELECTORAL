<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Administración de Procesos Electorales</h1>
            <p class="text-sm text-gray-600">Historial dinámico y control de periodos anuales del sistema SaaS.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
                <span class="font-semibold">Por favor, corrige los siguientes errores:</span>
                <ul class="mt-2 list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Nuevo Periodo Electoral</h2>
                
                <form action="{{ route('procesos.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Proceso</label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" 
                               placeholder="Ej: Seccionales Prefectura"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                    </div>

                    <div class="mb-4">
                        <label for="anio" class="block text-sm font-medium text-gray-700 mb-1">Año de Ejecución</label>
                        <input type="number" name="anio" id="anio" value="{{ old('anio', date('Y')) }}" 
                               min="2020" max="2050"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                    </div>

                    <div class="mb-6">
                        <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Elección</label>
                        <select name="tipo" id="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            <option value="generales" {{ old('tipo') == 'generales' ? 'selected' : '' }}>Elecciones Generales / Seccionales</option>
                            <option value="primarias" {{ old('tipo') == 'primarias' ? 'selected' : '' }}>Elecciones Primarias (Internas)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Define el comportamiento y reglas de escrutinio del sistema.</p>
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out text-sm shadow">
                        Inicializar Proceso
                    </button>
                </form>
            </div>
            <!-- Historial de Procesos con Indicadores Visuales y Acciones Contextuales -->
            <div class="lg:col-span-4 bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">Historial de Procesos Registrados</h2>

                {{-- ✅ CORRECCIÓN: overflow-x-auto para scroll horizontal si el espacio es insuficiente --}}
                <div class="block w-full overflow-x-auto">
                    {{-- ✅ CORRECCIÓN: min-w-[640px] evita que la tabla se comprima y corte las acciones --}}
                    <table class="w-full min-w-[640px] divide-y divide-gray-200 text-xs sm:text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 sm:px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider w-12">Año</th>
                                <th class="px-2 sm:px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Nombre del Proceso</th>
                                <th class="px-2 sm:px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider w-24">Tipo</th>
                                <th class="px-2 sm:px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider w-24">Estado</th>
                                {{-- ✅ CORRECCIÓN: w-52 en lugar de w-48 + min-w para que no se corte --}}
                                <th class="px-2 sm:px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider w-52 min-w-[200px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($procesos as $item)
                                <tr class="{{ $item->estado === 'activo' ? 'bg-blue-50/50' : '' }}">
                                    <td class="px-2 sm:px-4 py-3 whitespace-nowrap font-bold text-gray-700">
                                        {{ $item->anio }}
                                    </td>
                                    <td class="px-2 sm:px-4 py-3 text-gray-600 break-words text-xs sm:text-sm">
                                        {{ $item->nombre }}
                                    </td>
                                    <td class="px-2 sm:px-4 py-3 whitespace-nowrap">
                                        @if($item->tipo === 'primarias')
                                            <span class="px-1.5 py-0.5 inline-flex text-[10px] sm:text-xs leading-5 font-medium rounded bg-purple-100 text-purple-800 border border-purple-200">
                                                Primarias
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 inline-flex text-[10px] sm:text-xs leading-5 font-medium rounded bg-blue-100 text-blue-800 border border-blue-200">
                                                Generales
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-2 sm:px-4 py-3 whitespace-nowrap text-center">
                                        @if($item->estado === 'activo')
                                            <span class="px-2 py-0.5 inline-flex text-[10px] sm:text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                                Activo
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 inline-flex text-[10px] sm:text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                                                Archivado
                                            </span>
                                        @endif
                                    </td>
                                    {{-- ✅ CORRECCIÓN: whitespace-nowrap en td de acciones para que no se parta --}}
                                    <td class="px-2 sm:px-4 py-3 whitespace-nowrap text-right">
                                        @if($item->estado !== 'activo')
                                            <form action="{{ route('procesos.update', $item->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                        onclick="return confirm('¿Está seguro de cambiar el entorno activo al periodo: {{ $item->nombre }}?')"
                                                        class="bg-white hover:bg-gray-50 text-blue-600 border border-gray-300 font-medium py-1 px-2.5 rounded shadow-sm text-xs transition duration-150">
                                                    Activar
                                                </button>
                                            </form>
                                        @else
                                            {{-- ✅ CORRECCIÓN: gap-2 para separar mejor los elementos --}}
                                            <div class="inline-flex items-center gap-2 justify-end text-xs">
                                                <span class="text-gray-400 italic font-medium bg-gray-50 px-1 py-0.5 rounded border border-gray-100 text-[11px]">Dominante</span>
                                                <button type="button"
                                                        onclick="abrirModalLimpieza('{{ route('procesos.limpiarPruebas', $item->id) }}', '{{ $item->nombre }}')"
                                                        class="bg-red-600 hover:bg-red-700 text-white font-medium py-1 px-2.5 rounded shadow-sm text-[11px] transition duration-150 ease-in-out">
                                                    Limpiar Simulacro
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Seguridad Estricta para Limpieza de Datos -->
    <div id="modalLimpieza" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 border-t-4 border-red-600">
            <h3 class="text-lg font-bold text-gray-900 mb-2">🚨 Acción Altamente Crítica</h3>
            <p class="text-sm text-gray-600 mb-4">
                Está a punto de vaciar por completo las tablas de escrutinio de <strong id="modalProcesoNombre"></strong>. Esta operación eliminará de forma irreversible todas las actas digitadas y desgloses de votos cargados durante las capacitaciones.
            </p>

            <form id="formLimpieza" method="POST" action="">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Contraseña del Administrador:</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                        Escriba exactamente <span class="text-red-600 font-bold">BORRAR PRUEBAS</span>:
                    </label>
                    <input type="text" name="confirmacion" required autocomplete="off"
                           class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="cerrarModalLimpieza()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition shadow">
                        Confirmar Destrucción de Pruebas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalLimpieza(urlAction, nombreProceso) {
            document.getElementById('formLimpieza').action = urlAction;
            document.getElementById('modalProcesoNombre').innerText = nombreProceso;
            document.getElementById('modalLimpieza').classList.remove('hidden');
        }

        function cerrarModalLimpieza() {
            document.getElementById('modalLimpieza').classList.add('hidden');
            document.getElementById('formLimpieza').reset();
        }
    </script>
</x-app-layout>