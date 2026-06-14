<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-xl text-gray-800 leading-tight">
                {{ __('Detalle de Acta de Escrutinio') }}
            </h2>
            <div class="flex items-center gap-3">
                @if($acta->estado === 'ingresada')
                    <span class="px-3 py-1 text-xs font-extrabold uppercase tracking-wider rounded-full bg-green-100 text-green-800 border border-green-300 shadow-sm">
                        ● Cuadrada / Ingresada
                    </span>
                @else
                    <span class="px-3 py-1 text-xs font-extrabold uppercase tracking-wider rounded-full bg-red-100 text-red-800 border border-red-300 animate-pulse shadow-sm">
                        ⚠️ Con Novedad / Inconsistente
                    </span>
                @endif

                <a href="{{ route('actas.index') }}" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-4 py-2 rounded-lg text-sm transition shadow">
                    &larr; Volver al Listado
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white shadow-md rounded-xl p-6 mb-6 border border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-extrabold tracking-wider">Ubicación Jurisdiccional</p>
                        <p class="text-sm font-bold text-gray-700 mt-1">
                            {{ $acta->mesa->recinto->parroquia->canton->provincia->nombre }} / 
                            {{ $acta->mesa->recinto->parroquia->canton->nombre }} / 
                            {{ $acta->mesa->recinto->parroquia->nombre }}
                        </p>
                        <p class="text-xs text-gray-500 font-medium mt-0.5">Recinto: <span class="text-gray-800 font-semibold">{{ $acta->mesa->recinto->nombre }}</span></p>
                        <p class="text-xl text-indigo-800 font-black mt-2">Mesa #{{ $acta->mesa->numero }} (<span class="capitalize">{{ strtolower($acta->mesa->genero) }}</span>)</p>
                    </div>
                    <div class="md:text-right flex flex-col justify-between">
                        <div>
                            <p class="text-xs uppercase text-gray-400 font-extrabold tracking-wider">Dignidad Procesada</p>
                            <p class="text-xl font-black text-gray-900 mt-1 capitalize">{{ str_replace('_', ' ', $acta->dignidad) }}</p>
                            <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mt-0.5">Proceso: {{ $acta->tipo_proceso }}</p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <p class="text-xs text-gray-400">Digitador: <span class="font-semibold text-gray-700">{{ $acta->usuario->name }}</span></p>
                            <p class="text-xs text-gray-400">Fecha de Ingreso: <span class="font-semibold text-gray-700">{{ $acta->created_at->format('d/m/Y H:i') }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white shadow-sm border border-gray-200 p-4 rounded-xl text-center">
                    <p class="text-gray-500 font-bold text-xs uppercase tracking-wider">Padrón Mesa</p>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ $acta->mesa->num_electores }}</p>
                    <span class="text-[10px] text-gray-400 font-semibold">Electores Disp.</span>
                </div>
                <div class="bg-white shadow-sm border border-gray-200 p-4 rounded-xl text-center border-b-4 border-b-gray-400">
                    <p class="text-gray-600 font-bold text-xs uppercase tracking-wider">Votos Blancos</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ $acta->votos_blancos }}</p>
                    <span class="text-[10px] text-gray-400 font-semibold">Votos No Marcados</span>
                </div>
                <div class="bg-white shadow-sm border border-gray-200 p-4 rounded-xl text-center border-b-4 border-b-red-400">
                    <p class="text-red-600 font-bold text-xs uppercase tracking-wider">Votos Nulos</p>
                    <p class="text-2xl font-black text-red-700 mt-1">{{ $acta->votos_nulos }}</p>
                    <span class="text-[10px] text-red-400 font-semibold">Votos Invalidados</span>
                </div>
                <div class="bg-white shadow-sm border border-gray-200 p-4 rounded-xl text-center border-b-4 border-b-amber-500">
                    <p class="text-amber-600 font-bold text-xs uppercase tracking-wider">Ausentismo</p>
                    <p class="text-2xl font-black text-amber-700 mt-1">{{ $acta->ausentismo }}</p>
                    <span class="text-[10px] text-amber-500 font-semibold">Ciudadanos que NO votaron</span>
                </div>
            </div>

            <div class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-indigo-700 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-white font-bold tracking-wide text-sm uppercase">Desglose de Votos Válidos por Candidato</h3>
                    <span class="text-xs bg-indigo-800 text-indigo-200 px-2 py-0.5 rounded font-mono">CONFIDENCIAL</span>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Lista</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Candidato / Organización Política</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Votos Obtenidos</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($acta->candidatos as $candidato)
                        <tr class="hover:bg-indigo-50/50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-bold bg-gray-100 text-gray-800 rounded-md border border-gray-200">
                                    {{ $candidato->partido->numero ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900">{{ $candidato->nombre }}</div>
                                <div class="text-xs text-indigo-600 font-semibold mt-0.5">{{ $candidato->partido->nombre }}</div>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <span class="text-xl font-black text-gray-900 font-mono">
                                    {{ $candidato->pivot->votos }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400 font-medium">
                                No se encontraron votos asociados a candidatos en este registro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8 flex justify-center items-center gap-4 print:hidden">
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition">
                    🖨️ Imprimir Comprobante de Ingreso
                </button>
            </div>

        </div>
    </div>
</x-app-layout>