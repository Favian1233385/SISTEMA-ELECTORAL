<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Lista de Digitadores Generados') }}
            </h2>
            {{-- BOTÓN DE LIMPIEZA TOTAL --}}
            <form action="{{ route('usuarios.limpiar') }}" method="POST" onsubmit="return confirm('¿ESTÁ TOTALMENTE SEGURO? Esta acción eliminará a TODOS los digitadores del sistema y no se puede deshacer. Las cuentas de administrador no se verán afectadas.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-black hover:bg-red-700 text-black px-4 py-2 rounded-lg text-sm font-black transition uppercase tracking-tighter flex items-center shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Limpiar Sistema (Borrar Todo)
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Alertas de éxito o error --}}
            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100">
                
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">
                            Filtro: <span class="capitalize text-indigo-600">{{ $tipo ?? 'General' }}</span>
                        </h3>
                        <p class="text-sm text-gray-500 font-medium">Se han encontrado {{ $digitadores->count() }} usuarios.</p>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-black rounded-lg text-sm font-bold transition border border-gray-300">
                            ← Volver
                        </a>
                        {{-- Botón para imprimir PDF --}}
                        <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-black rounded-lg text-sm font-bold shadow-md transition flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path></svg>
                            PDF / Imprimir
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3">Nombre / Digitador</th>
                                <th class="px-6 py-3">Email / Usuario</th>
                                <th class="px-6 py-3 text-center">Contraseña Acceso</th>
                                <th class="px-6 py-3">Jurisdicción</th>
                                <th class="px-6 py-3">Mesa</th>
                                <th class="px-6 py-3 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($digitadores as $user)
                                <tr class="bg-white border-b hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <code class="bg-blue-50 text-blue-800 px-2 py-1 rounded font-bold border border-blue-100">{{ $user->email }}</code>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-yellow-100 text-black px-3 py-1 rounded font-mono font-black text-xs border border-yellow-300">
                                            voto2026
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] leading-tight text-gray-600">
                                            <strong>Prov:</strong> {{ $user->mesa->recinto->parroquia->canton->provincia->nombre ?? 'N/A' }}<br>
                                            <strong>Cant:</strong> {{ $user->mesa->recinto->parroquia->canton->nombre ?? 'N/A' }}<br>
                                            <strong>Parr:</strong> {{ $user->mesa->recinto->parroquia->nombre ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">
                                            #{{ $user->mesa->numero ?? 'N/A' }} ({{ $user->mesa->genero ?? '' }})
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center text-green-700 font-bold text-xs uppercase">
                                            <span class="w-2 h-2 mr-1.5 bg-green-500 rounded-full animate-pulse"></span> Activo
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            <p class="text-gray-400 font-bold">No hay digitadores generados para esta selección o nivel territorial.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>