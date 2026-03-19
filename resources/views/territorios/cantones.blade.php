<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Provincia: <span class="text-indigo-600">{{ $provincia->nombre }}</span>
            </h2>
            <a href="{{ route('territorios.index') }}" class="text-sm bg-gray-200 px-3 py-1 rounded">Volver</a>
        </div>
    </x-slot>

    <div class="py-12 text-center">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h3 class="mb-6 font-bold text-gray-500 uppercase tracking-widest">Cantones de {{ $provincia->nombre }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach($cantones as $canton)
                    <a href="{{ route('territorios.index', ['canton' => $canton->id]) }}" 
                       class="bg-white p-6 rounded-2xl shadow-sm border hover:border-indigo-500 hover:shadow-md transition group">
                        <p class="font-black text-gray-800 group-hover:text-indigo-600">{{ $canton->nombre }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $canton->parroquias_count }} Parroquias</p>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>