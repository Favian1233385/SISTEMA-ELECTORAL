<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex flex-col">
                        <h2 class="text-2xl font-bold text-gray-800">Delegados y Jurisdicciones</h2>
                        <p class="text-xs text-gray-500 mt-1">Lista exclusiva de personal administrativo y de control político.</p>
                    </div>
                    
                    <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-green-700 transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Registrar Nuevo Delegado
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif
                
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol Administrativo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jurisdicción / Cobertura</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users as $user)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{-- Asignación semántica de colores según la jerarquía del rol --}}
                                    @if($user->role === 'admin' || $user->role === 'admin_general')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            SUPER ADMIN
                                        </span>
                                    @elseif($user->role === 'admin_provincial')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                            ADMIN PROVINCIAL
                                        </span>
                                    @elseif($user->role === 'admin_cantonal')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            ADMIN CANTONAL
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ strtoupper($user->role) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($user->role === 'admin' || $user->role === 'admin_general')
                                        <span class="text-gray-500 font-medium">Nacional / Acceso Total</span>
                                    @elseif($user->role === 'admin_provincial')
                                        <span class="text-gray-700 font-semibold">Provincia:</span> 
                                        <span class="text-gray-900 font-medium">{{ $user->provincia->nombre ?? 'No asignada' }}</span>
                                    @else
                                        {{-- Muestra la ruta territorial específica para delegados y cantonales --}}
                                        <div class="text-gray-800 font-medium">{{ $user->canton->nombre ?? 'Sin Cantón' }}</div>
                                        <div class="text-xs text-gray-400">{{ $user->parroquia ? $user->parroquia->nombre : 'Toda la zona / Parroquia' }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    {{-- Impedimos la auto-edición accidental de super-administradores desde esta grilla --}}
                                    @if($user->role !== 'admin' && $user->role !== 'admin_general')
                                        <a href="{{ route('users.edit', $user->id) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 transition shadow-sm">
                                            Asignar Territorio
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Acceso Jerárquico</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 whitespace-nowrap text-sm text-gray-500 text-center italic">
                                    No existen delegados o administradores registrados para esta jurisdicción.
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