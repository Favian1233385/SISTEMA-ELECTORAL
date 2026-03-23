<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Desglose de Votos') }}: {{ $candidato->nombre }}
            </h2>
            <a href="{{ route('resultados.index', ['dignidad' => $candidato->dignidad]) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                &larr; Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Info del Candidato --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400 uppercase font-bold">Candidato seleccionado</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ $candidato->nombre }}</h3>
                        <p class="text-blue-600 dark:text-blue-400 font-medium">{{ $candidato->partido->nombre ?? 'Independiente' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400 uppercase font-bold">Dignidad</p>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-bold">
                            {{ $candidato->dignidad }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Tabla de Desglose --}}
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h4 class="font-bold text-gray-700 dark:text-gray-300">Detalle por Parroquia, Recinto y Mesa</h4>
                </div>
                
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Parroquia</th>
                            <th class="px-6 py-3">Recinto Electoral</th>
                            <th class="px-6 py-3 text-center">Mesa</th>
                            <th class="px-6 py-3 text-center">Género</th>
                            <th class="px-6 py-3 text-right bg-blue-50 dark:bg-gray-700">Votos Obtenidos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @php $totalVotosCandidato = 0; @endphp
                        @forelse($detalles as $detalle)
                            @php $totalVotosCandidato += $detalle->votos; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $detalle->parroquia }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $detalle->recinto }}
                                </td>
                                <td class="px-6 py-4 text-center font-mono">
                                    {{ str_pad($detalle->mesa, 3, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase {{ $detalle->genero == 'M' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700' }}">
                                        {{ $detalle->genero == 'M' ? 'Masculino' : 'Femenino' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-gray-700/50">
                                    {{ number_format($detalle->votos) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="italic text-gray-400">Aún no hay actas procesadas para este candidato.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($detalles->count() > 0)
                    <tfoot class="bg-gray-800 text-white dark:bg-black">
                        <tr>
                            <td colspan="4" class="px-6 py-3 font-bold text-right uppercase">Total Acumulado:</td>
                            <td class="px-6 py-3 text-right font-black text-lg text-yellow-400">
                                {{ number_format($totalVotosCandidato) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</x-app-layout>