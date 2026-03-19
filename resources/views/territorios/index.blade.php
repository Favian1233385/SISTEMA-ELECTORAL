<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Territorio Electoral') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <h3 class="text-lg font-bold mb-4">Provincias Registradas</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($provincias as $provincia)
                        <div class="border rounded-xl p-4 hover:bg-slate-50 transition">
                            <div class="flex justify-between items-center uppercase font-black text-indigo-700">
                                {{ $provincia->nombre }}
                                <span class="bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full">
                                    {{ $provincia->cantones_count }} Cantones
                                </span>
                            </div>
                            <hr class="my-2">
                            <a href="{{ route('territorios.index', ['provincia' => $provincia->id]) }}" 
                            class="text-sm text-gray-600 hover:text-indigo-600 font-bold italic">
                                Ver Cantones e Ingresar Recintos →
                            </a>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
</x-app-layout>