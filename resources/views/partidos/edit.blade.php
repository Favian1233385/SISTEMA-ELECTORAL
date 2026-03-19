<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Editar Organización Política') }}: {{ $partido->nombre }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-8 border">
                
                <form action="{{ route('partidos.update', $partido) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT') {{-- Fundamental para que Laravel sepa que es una actualización --}}
                    
                    <div class="space-y-6">
                        <div>
                            <x-input-label for="nombre" :value="__('Nombre del Movimiento')" />
                            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $partido->nombre)" required />
                        </div>

                        <div>
                            <x-input-label for="lista" :value="__('Número de Lista')" />
                            <x-text-input id="lista" name="lista" type="text" class="mt-1 block w-full" :value="old('lista', $partido->lista)" required />
                        </div>

                        <div>
                            <x-input-label :value="__('Logo del Movimiento')" />
                            <div class="flex items-center mt-2 space-x-4">
                                <img src="{{ asset($partido->logo) }}" class="h-16 w-16 rounded-full border shadow-sm" alt="Logo actual">
                                <div class="flex-1">
                                    <input type="file" name="logo" class="block w-full text-sm text-gray-500 border rounded-lg cursor-pointer bg-gray-50 p-2">
                                    <p class="text-xs text-gray-400 mt-1">Sube un archivo nuevo solo si deseas cambiar el logo actual.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 border-t pt-4">
                        <a href="{{ route('partidos.index') }}" class="text-sm text-gray-600 hover:underline mr-6">Cancelar y volver</a>
                        <x-primary-button class="bg-indigo-600 hover:bg-indigo-700">
                            {{ __('Actualizar Cambios') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>