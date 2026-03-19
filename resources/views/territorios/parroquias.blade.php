<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cantón: <span class="text-indigo-600">{{ $canton->nombre }}</span>
            </h2>
            <a href="{{ route('territorios.index', ['provincia' => $canton->provincia_id]) }}" class="text-sm bg-gray-200 px-3 py-1 rounded">← Volver a Cantones</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-2xl shadow-lg">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-black text-xl uppercase text-slate-700">Parroquias de {{ $canton->nombre }}</h3>
                    <button onclick="document.getElementById('modalParroquia').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">
                        + Nueva Parroquia
                    </button>

                    <div id="modalParroquia" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
                        <div class="bg-white rounded-xl p-8 max-w-md w-full shadow-2xl">
                            <h3 class="text-xl font-bold mb-4 text-slate-800">Registrar Nueva Parroquia</h3>
                            <form action="{{ route('parroquia.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="canton_id" value="{{ $canton->id }}">
                                <div class="mb-4">
                                    <label class="block text-sm font-bold mb-2">Nombre de la Parroquia:</label>
                                    <input type="text" name="nombre" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: Macas (Urbana)" required>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('modalParroquia').classList.add('hidden'); document.getElementById('modalParroquia').classList.remove('flex')" class="px-4 py-2 text-gray-500 font-bold">Cancelar</button>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold shadow-md">Guardar Parroquia</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    @foreach($parroquias as $parroquia)
                            <div class="flex justify-between items-center p-5 border-2 border-slate-100 rounded-2xl bg-white mb-3 hover:border-indigo-500 transition shadow-sm">
                                <div>
                                    <p class="font-black text-lg text-slate-800 uppercase leading-none">{{ $parroquia->nombre }}</p>
                                    <p class="text-xs text-slate-400 mt-1 font-bold">
                                        {{ $parroquia->recintos_count ?? 0 }} RECINTOS REGISTRADOS
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <a href="{{ route('territorios.index', ['parroquia' => $parroquia->id]) }}" 
                                    class="bg-slate-900 text-black px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter hover:bg-indigo-600 transition shadow-lg shadow-slate-200">
                                        Ver / Crear Recintos →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>