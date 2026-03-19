<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 leading-tight border-l-4 border-indigo-600 pl-4">
            {{ __('Sistema de Control Electoral v1.0') }}
        </h2>
    </x-slot>

    <div class="py-10 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Banner Informativo --}}
            <div class="mb-8 p-6 bg-gradient-to-r from-indigo-800 to-blue-700 rounded-2xl shadow-xl text-white">
                <h3 class="text-2xl font-bold">Bienvenido, {{ Auth::user()->name }}</h3>
                <p class="opacity-80 font-light">
                    {{-- Ajuste de texto según el nivel de mando --}}
                    @if(Auth::user()->role === 'admin')
                        Panel de Control y Configuración Global.
                    @elseif(in_array(Auth::user()->role, ['admin_provincial', 'admin_cantonal']))
                        Panel de Supervisión y Gestión Territorial.
                    @else
                        Módulo de Registro de Datos en Campo.
                    @endif
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                
                {{-- 1. MÓDULO DE ORGANIZACIONES (Solo Admin General) --}}
                @if(Auth::user()->role === 'admin')
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 transition-all duration-300 hover:shadow-blue-100 hover:-translate-y-1 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-50 rounded-lg group-hover:bg-blue-600 transition">
                                <svg class="w-6 h-6 text-blue-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-blue-500 uppercase tracking-wider">Organizaciones</span>
                        </div>
                        <h4 class="text-gray-800 font-extrabold text-lg mb-2">Partidos Políticos</h4>
                        <div class="flex flex-col gap-1.5 text-sm text-indigo-600 font-medium">
                            <a href="{{ route('partidos.create') }}" class="hover:underline">→ Registrar Nuevo</a>
                            <a href="{{ route('partidos.index') }}" class="text-gray-500 font-normal hover:text-gray-700">Ver base de datos</a>
                        </div>
                    </div>
                @endif

                {{-- 2. MÓDULO DE ESCALABILIDAD (Solo Admin General) --}}
                @if(Auth::user()->role === 'admin')
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 transition-all duration-300 hover:shadow-purple-100 hover:-translate-y-1 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-purple-50 rounded-lg group-hover:bg-purple-600 transition">
                                <svg class="w-6 h-6 text-purple-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-purple-500 uppercase tracking-wider">Territorio</span>
                        </div>
                        <h4 class="text-gray-800 font-extrabold text-lg mb-2">División Política</h4>
                        <div class="flex flex-col gap-1.5 text-sm text-indigo-600 font-medium">
                            <a href="{{ route('territorios.index') }}" class="hover:underline">Gestionar Divisiones</a>
                            <span class="text-gray-400 font-normal italic">Configuración de mapas</span>
                        </div>
                    </div>
                @endif

                {{-- 3. NUEVO MÓDULO: CONFIGURACIÓN SAAS (Solo Admin General) --}}
                @if(Auth::user()->role === 'admin')
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-indigo-600 p-6 transition-all duration-300 hover:shadow-indigo-200 hover:-translate-y-1 group relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-2">
                            <span class="flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                            </span>
                        </div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-indigo-50 rounded-lg group-hover:bg-indigo-600 transition">
                                <svg class="w-6 h-6 text-indigo-600 group-hover:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">SaaS Admin</span>
                        </div>
                        <h4 class="text-gray-800 font-extrabold text-lg mb-2">Permisos de Visibilidad</h4>
                        <div class="flex flex-col gap-1.5 text-sm text-indigo-700 font-bold">
                            <a href="{{ route('jurisdiccion.config') }}" class="hover:underline">→ Gestionar Switches</a>
                            <span class="text-gray-400 font-normal italic">Habilitar Prefecto/Vocal</span>
                        </div>
                    </div>
                @endif

                {{-- 4. MÓDULO DE ACTORES (Admin General y Administradores de Mando) --}}
                @if(in_array(Auth::user()->role, ['admin', 'admin_provincial', 'admin_cantonal']))
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6 transition-all duration-300 hover:shadow-orange-100 hover:-translate-y-1 group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-orange-50 rounded-lg group-hover:bg-orange-600 transition">
                                <svg class="w-6 h-6 text-orange-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-orange-500 uppercase tracking-wider">Actores</span>
                        </div>
                        <h4 class="text-gray-800 font-extrabold text-lg mb-2">Gestión de Usuarios</h4>
                        <div class="flex flex-col gap-1.5 text-sm text-indigo-600 font-medium">
                            <a href="{{ route('users.create') }}" class="hover:underline">→ Registrar Equipo</a>
                            <a href="{{ route('users.index') }}" class="text-gray-500 font-normal hover:text-gray-700">Ver nómina de personal</a>
                        </div>
                    </div>
                @endif

                {{-- 5. MÓDULO OPERATIVO: ESCRUTINIO --}}
                <div class="bg-white rounded-2xl shadow-lg border-2 border-emerald-500 p-6 transition-all duration-300 hover:shadow-emerald-100 hover:-translate-y-1 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-50 rounded-lg group-hover:bg-emerald-600 transition">
                            <svg class="w-6 h-6 text-emerald-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-xs font-bold text-emerald-500 uppercase tracking-wider">Escrutinio</span>
                    </div>
                    <h4 class="text-gray-800 font-extrabold text-lg mb-2">Conteo de Votos</h4>
                    <div class="flex flex-col gap-1.5 text-sm text-indigo-600 font-medium">
                        @if(Auth::user()->role === 'digitador')
                            <a href="{{ route('actas.create') }}" class="font-bold bg-emerald-600 text-white text-center py-2 rounded-lg hover:bg-emerald-700 transition shadow-md">
                                Ingresar Actas
                            </a>
                        @else
                            <div class="bg-slate-100 text-slate-500 text-center py-2 rounded-lg text-xs font-semibold italic">
                                Modo: Solo Consulta
                            </div>
                        @endif
                        <a href="{{ route('actas.index') }}" class="text-gray-500 text-center font-normal hover:text-gray-700 mt-1">Historial de Ingresos</a>
                    </div>
                </div>

                {{-- 6. MONITOR DE RESULTADOS: TODOS --}}
                <div class="bg-white rounded-2xl shadow-lg border border-slate-100 border-l-4 border-l-indigo-600 p-6 transition-all duration-300 hover:shadow-indigo-200 hover:-translate-y-1 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-indigo-50 rounded-lg group-hover:bg-indigo-600 transition">
                            <svg class="w-6 h-6 text-indigo-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Visualización</span>
                    </div>
                    <h4 class="text-gray-800 font-extrabold text-lg mb-2">Monitor Público</h4>
                    <div class="flex flex-col gap-1.5 text-sm text-indigo-600 font-medium">
                        <a href="{{ route('resultados.index') }}" class="font-bold hover:underline">Ver Resultados →</a>
                        <span class="text-gray-400 font-normal">Actualización en tiempo real</span>
                    </div>
                </div>

            </div> 
        </div>
    </div>
</x-app-layout>