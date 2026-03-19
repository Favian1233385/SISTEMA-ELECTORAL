<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Asignar Territorio a: ') }} {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Rol del Usuario</label>
                        <select name="role" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="digitador" {{ $user->role == 'digitador' ? 'selected' : '' }}>Digitador / Delegado</option>
                            <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Administrador Provincial</option>
                        </select>
                    </div>

                    <div class="p-6 bg-gray-50 rounded-xl border border-gray-200 mb-6">
                        <h3 class="text-md font-bold text-gray-800 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Jurisdicción Territorial
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Cantón</label>
                                <select name="canton_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Toda la Provincia --</option>
                                    @foreach($cantones as $canton)
                                        <option value="{{ $canton->id }}" {{ $user->canton_id == $canton->id ? 'selected' : '' }}>{{ $canton->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Parroquia</label>
                                <select name="parroquia_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Todo el Cantón --</option>
                                    @foreach($parroquias as $parroquia)
                                        <option value="{{ $parroquia->id }}" {{ $user->parroquia_id == $parroquia->id ? 'selected' : '' }}>{{ $parroquia->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 italic">
                            * Si el usuario es Admin, estas restricciones se ignoran automáticamente.
                        </p>
                    </div>

                    <div class="flex items-center mb-8 p-2">
                        <input type="checkbox" name="ver_prefectos" id="ver_prefectos" value="1" {{ $user->ver_prefectos ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5">
                        <label for="ver_prefectos" class="ml-3 text-sm font-medium text-gray-700">
                            ¿Permitir visualización y conteo de votos para <strong>Prefectura</strong>?
                        </label>
                    </div>

                    <div class="flex justify-end items-center gap-4 border-t pt-6">
                        <a href="{{ route('users.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-800">
                            Cancelar y volver
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Guardar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>