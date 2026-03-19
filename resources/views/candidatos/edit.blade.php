<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-gray-800 leading-tight">
            {{ __('Editar Inscripción') }}: {{ $candidato->nombre }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100">
                <div class="p-8">
                    <form action="{{ route('candidatos.update', $candidato) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-indigo-700 border-b pb-2">Actualizar Información</h3>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                                    <input type="text" name="nombre" value="{{ old('nombre', $candidato->nombre) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Dignidad</label>
                                    <select name="dignidad" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        @foreach(['Prefecto', 'Alcalde', 'Concejal Urbano', 'Concejal Rural', 'Vocal Parroquial'] as $opcion)
                                            <option value="{{ $opcion }}" {{ old('dignidad', $candidato->dignidad) == $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Partido Político</label>
                                    <select name="partido_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        @foreach($partidos as $partido)
                                            <option value="{{ $partido->id }}" {{ old('partido_id', $candidato->partido_id) == $partido->id ? 'selected' : '' }}>{{ $partido->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center space-x-4 pt-2">
                                    <img src="{{ $candidato->foto ? asset($candidato->foto) : asset('img/default-avatar.png') }}" class="h-16 w-16 rounded-full border shadow-sm">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700">Cambiar Foto</label>
                                        <input type="file" name="foto" class="mt-1 block w-full text-xs text-gray-500">
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-indigo-700 border-b pb-2">Ubicación de la Dignidad</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Provincia</label>
                                    <select id="provincia_id" name="provincia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        @foreach($provincias as $prov)
                                            <option value="{{ $prov->id }}" {{ old('provincia_id', $candidato->provincia_id) == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cantón</label>
                                    <select id="canton_id" name="canton_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="{{ $candidato->canton_id }}">{{ $candidato->canton->nombre ?? 'Seleccione Cantón...' }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Parroquia</label>
                                    <select id="parroquia_id" name="parroquia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="{{ $candidato->parroquia_id }}">{{ $candidato->parroquia->nombre ?? 'Seleccione Parroquia...' }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('candidatos.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancelar</a>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded-lg transition shadow-lg">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('provincia_id').addEventListener('change', function() {
            let provinciaId = this.value;
            let cantonSelect = document.getElementById('canton_id');
            if (provinciaId) {
                fetch(`/api/cantones/${provinciaId}`)
                    .then(res => res.json())
                    .then(data => {
                        cantonSelect.innerHTML = '<option value="">Seleccione Cantón...</option>';
                        data.forEach(c => cantonSelect.innerHTML += `<option value="${c.id}">${c.nombre}</option>`);
                    });
            }
        });

        document.getElementById('canton_id').addEventListener('change', function() {
            let cantonId = this.value;
            let parroquiaSelect = document.getElementById('parroquia_id');
            if (cantonId) {
                fetch(`/api/parroquias/${cantonId}`)
                    .then(res => res.json())
                    .then(data => {
                        parroquiaSelect.innerHTML = '<option value="">Seleccione Parroquia...</option>';
                        data.forEach(p => parroquiaSelect.innerHTML += `<option value="${p.id}">${p.nombre}</option>`);
                    });
            }
        });
    </script>
</x-app-layout>