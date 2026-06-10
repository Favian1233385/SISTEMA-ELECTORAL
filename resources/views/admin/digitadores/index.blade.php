<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Lista de Digitadores Generados') }}
            </h2>

            {{-- MENÚ DESPLEGABLE DE LIMPIEZA SEGMENTADA POR DIGNIDAD --}}
            <div class="relative inline-block text-left" x-data="{ open: false }">
                <div>
                    <button @click="open = !open" @click.away="open = false" type="button" class="bg-white hover:bg-gray-50 text-black px-4 py-2 rounded-lg text-sm font-black transition uppercase tracking-tighter flex items-center shadow-lg border border-gray-300 focus:outline-none">
                        <svg class="w-4 h-4 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{-- CORRECCIÓN: Uso de la variable del proceso validada por el controlador --}}
                        Limpiar Digitadores ({{ strtoupper($procesoEleccion) }}) ▾
                    </button>
                </div>

                {{-- Opciones del Dropdown --}}
                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="origin-top-right absolute right-0 mt-2 w-64 rounded-md shadow-2xl bg-white ring-1 ring-black ring-opacity-5 z-50 divide-y divide-gray-100" style="display: none;">
                    
                    {{-- SECCIÓN: SELECCIÓN ESPECÍFICA POR DIGNIDAD --}}
                    <div class="py-1">
                        <span class="block px-4 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Eliminar por Dignidad</span>
                        
                        <form action="{{ route('usuarios.limpiar') }}" method="POST" onsubmit="return confirm('¿Seguro? Se eliminarán únicamente los digitadores de PREFECTO en este territorio.')" class="block">
                            @csrf @method('DELETE')
                            <input type="hidden" name="proceso_eleccion" value="{{ $procesoEleccion }}">
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="id" value="{{ $id }}">
                            <input type="hidden" name="dignidad" value="PREFECTO">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition font-medium flex items-center">
                                <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span> Solo Prefectura
                            </button>
                        </form>

                        <form action="{{ route('usuarios.limpiar') }}" method="POST" onsubmit="return confirm('¿Seguro? Se eliminarán únicamente los digitadores de ALCALDE en este territorio.')" class="block">
                            @csrf @method('DELETE')
                            <input type="hidden" name="proceso_eleccion" value="{{ $procesoEleccion }}">
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="id" value="{{ $id }}">
                            <input type="hidden" name="dignidad" value="ALCALDE">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition font-medium flex items-center">
                                <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span> Solo Alcaldías
                            </button>
                        </form>

                        <form action="{{ route('usuarios.limpiar') }}" method="POST" onsubmit="return confirm('¿Seguro? Se eliminarán únicamente los digitadores de CONCEJALES en este territorio.')" class="block">
                            @csrf @method('DELETE')
                            <input type="hidden" name="proceso_eleccion" value="{{ $procesoEleccion }}">
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="id" value="{{ $id }}">
                            <input type="hidden" name="dignidad" value="CONCEJALES">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition font-medium flex items-center">
                                <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span> Solo Concejales
                            </button>
                        </form>

                        <form action="{{ route('usuarios.limpiar') }}" method="POST" onsubmit="return confirm('¿Seguro? Se eliminarán únicamente los digitadores de JUNTAS PARROQUIALES en este territorio.')" class="block">
                            @csrf @method('DELETE')
                            <input type="hidden" name="proceso_eleccion" value="{{ $procesoEleccion }}">
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="id" value="{{ $id }}">
                            <input type="hidden" name="dignidad" value="JUNTAS PARROQUIALES">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition font-medium flex items-center">
                                <span class="w-2 h-2 rounded-full bg-red-500 mr-2"></span> Solo Juntas Parr.
                            </button>
                        </form>
                    </div>

                    {{-- SECCIÓN: ACCIÓN GLOBAL (BORRA TODO SIN FILTRAR DIGNIDAD) --}}
                    <div class="py-1 bg-gray-50">
                        <form action="{{ route('usuarios.limpiar') }}" method="POST" onsubmit="return confirm('¡ADVERTENCIA CRÍTICA! Esta acción vaciará TODOS los digitadores generados aquí sin importar su dignidad. ¿Desea continuar?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="proceso_eleccion" value="{{ $procesoEleccion }}">
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="id" value="{{ $id }}">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-700 hover:text-white transition font-black uppercase tracking-tighter flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                                Borrar Todo el Bloque
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">
                            Territorio: <span class="capitalize text-indigo-600">{{ $tipo ?? 'General' }}</span>
                            @if($dignidad)
                                <span class="mx-2 text-gray-400">|</span>
                                Dignidad: <span class="uppercase text-red-600 font-black text-sm tracking-wide">{{ $dignidad }}</span>
                            @endif
                        </h3>
                        <p class="text-sm text-gray-500 font-medium">Se han encontrado {{ $digitadores->count() }} usuarios.</p>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-black rounded-lg text-sm font-bold transition border border-gray-300">
                            ← Volver
                        </a>
                        {{-- CORRECCIÓN: Pasamos las variables explícitas validadas para el PDF seguro --}}
                        <a href="{{ request()->fullUrlWithQuery(['pdf' => 1, 'tipo' => $tipo, 'id' => $id, 'dignidad' => $dignidad, 'proceso_eleccion' => $procesoEleccion]) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold shadow-md transition flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path></svg>
                            PDF / Imprimir
                        </a>
                    </div>
                </div>

                {{-- CONTENEDOR CON ANCHO DE TABLA FIJO --}}
                <div class="overflow-x-auto w-full border border-gray-200 rounded-lg">
                    <table class="w-full table-fixed text-sm text-left text-gray-500 min-w-[1000px]">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                {{-- SE ESPECIFICAN LAS 7 COLUMNAS REALES --}}
                                <th class="w-[23%] px-4 py-3 font-bold">Nombre / Digitador</th>
                                <th class="w-[23%] px-4 py-3 font-bold">Email / Usuario</th>
                                <th class="w-[10%] px-2 py-3 font-bold text-center">Contraseña</th>
                                <th class="w-[24%] px-4 py-3 font-bold">Jurisdicción</th>
                                <th class="w-[12%] px-2 py-3 font-bold text-center">Mesa / Dignidad</th>
                                <th class="w-[8%] px-2 py-3 font-bold text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($digitadores as $user)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-4 font-bold text-gray-900 break-words text-xs">
                                        {{ $user->name }}
                                    </td>
                                    
                                    <td class="px-4 py-4 break-all">
                                        <code class="bg-blue-50 text-blue-800 px-2 py-1 rounded font-bold border border-blue-100 text-[11px] block whitespace-normal">
                                            {{ $user->email }}
                                        </code>
                                    </td>
                                   
                                    <td>
                                        <code style="padding: 2px 6px; background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
                                            {{ $user->password_plain ?? 'voto2026' }}
                                        </code>
                                    </td>
                                    
                                    <td class="px-4 py-4 text-[11px] leading-normal text-gray-600 break-words">
                                        <div><strong>Prov:</strong> {{ $user->mesa->recinto->parroquia->canton->provincia->nombre ?? 'N/A' }}</div>
                                        <div><strong>Cant:</strong> {{ $user->mesa->recinto->parroquia->canton->nombre ?? 'N/A' }}</div>
                                        <div><strong>Parr:</strong> {{ $user->mesa->recinto->parroquia->nombre ?? 'N/A' }}</div>
                                        <div class="text-indigo-700 mt-0.5"><strong class="text-indigo-900">Recinto:</strong> <span class="font-semibold text-gray-900">{{ $user->mesa->recinto->nombre ?? 'N/A' }}</span></div>
                                    </td>
                                    
                                    <td class="px-2 py-4 text-center">
                                        <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter inline-block mb-1">
                                            #{{ $user->mesa->numero ?? 'N/A' }} ({{ $user->mesa->genero ?? '' }})
                                        </span>
                                        @if($user->dignidad_asignada)
                                            <span class="text-[9px] font-black text-red-600 uppercase tracking-tighter block whitespace-normal">
                                                {{ $user->dignidad_asignada }}
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <td class="px-2 py-4 text-center">
                                        <span class="inline-flex items-center text-green-700 font-bold text-[10px] uppercase">
                                            <span class="w-1.5 h-1.5 mr-1 bg-green-500 rounded-full animate-pulse"></span> Activo
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- CORRECCIÓN DEL COLSPAN: Ahora mapea correctamente las 6 columnas del layout --}}
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                            </svg>
                                            <p class="text-gray-400 font-bold">No hay digitadores generados para esta selección o nivel territorial.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($digitadores, 'links'))
                    <div class="mt-4">
                        {{ $digitadores->withQueryString()->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>