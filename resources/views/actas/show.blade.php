<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                {{ __('Detalle de Acta de Escrutinio') }}
            </h2>
            <a href="{{ route('actas.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">
                &larr; Volver al Listado
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs uppercase text-gray-500 font-bold">Ubicación</p>
                        <p class="text-sm font-bold text-gray-800">
                            {{ $acta->mesa->recinto->parroquia->nombre }} / {{ $acta->mesa->recinto->nombre }}
                        </p>
                        <p class="text-lg text-indigo-700 font-black">Mesa #{{ $acta->mesa->numero }} ({{ $acta->mesa->genero }})</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase text-gray-500 font-bold">Dignidad</p>
                        <p class="text-xl font-bold text-gray-900">{{ $acta->dignidad }}</p>
                        <p class="text-xs text-gray-400">Ingresada por: {{ $acta->usuario->name }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-gray-100 p-4 rounded-lg text-center border-l-4 border-gray-400">
                    <p class="text-gray-600 font-bold text-sm">Votos Blancos</p>
                    <p class="text-3xl font-black text-gray-800">{{ $acta->votos_blancos }}</p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg text-center border-l-4 border-red-400">
                    <p class="text-red-600 font-bold text-sm">Votos Nulos</p>
                    <p class="text-3xl font-black text-red-800">{{ $acta->votos_nulos }}</p>
                </div>
            </div>

            <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden">
                <div class="bg-indigo-600 px-6 py-3">
                    <h3 class="text-white font-bold italic">Resultados de Candidatos</h3>
                </div>
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Candidato / Partido</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Votos Obtenidos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($acta->candidatos as $candidato)
                        <tr class="hover:bg-indigo-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $candidato->nombre }}</div>
                                <div class="text-xs text-indigo-600 font-semibold">{{ $candidato->partido->nombre }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-2xl font-black text-gray-800">
                                    {{ $candidato->pivot->votos }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 text-center">
                <button onclick="window.print()" class="text-sm text-gray-500 underline hover:text-gray-800">
                    Imprimir Comprobante de Ingreso
                </button>
            </div>
        </div>
    </div>
</x-app-layout>