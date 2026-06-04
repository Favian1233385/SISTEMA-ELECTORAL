<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registrar Nuevo Movimiento Político') }} - {{ ucfirst($proceso) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form action="{{ route('partidos.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- CAMPO OCULTO CRÍTICO PARA EL CONTROL DE PROCESO ELECTORAL -->
                    <input type="hidden" name="proceso_eleccion" value="{{ $proceso }}">
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-input-label for="nombre" :value="__('Nombre del Movimiento')" />
                            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre')" required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="lista" :value="__('Número de Lista')" />
                            <x-text-input id="lista" name="lista" type="text" class="mt-1 block w-full" :value="old('lista')" required />
                            <x-input-error :messages="$errors->get('lista')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="logo" :value="__('Logo del Movimiento (Imagen)')" />
                            <input type="file" name="logo" id="logo" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm p-2 border">
                            <p class="text-xs text-gray-gray-500 mt-1">Formatos permitidos: JPG, PNG, WEBP. Máximo 2MB.</p>
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 border-t pt-4">
                        <!-- El botón cancelar ahora regresa manteniendo el filtro del proceso en el index -->
                        <a href="{{ route('partidos.index', ['proceso' => $proceso]) }}" class="text-sm text-gray-600 hover:underline mr-4">Cancelar</a>
                        <x-primary-button>
                            {{ __('Guardar Movimiento') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>