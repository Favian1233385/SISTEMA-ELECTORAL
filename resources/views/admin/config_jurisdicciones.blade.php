<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-slate-800">
            {{ __('Gestión de Permisos SaaS') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-slate-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl rounded-2xl p-8 border border-slate-200">
                
                @if(session('success'))
                    <div class="mb-6 p-4 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-bold flex items-center">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="grid gap-4">
                    @foreach($cantones as $canton)
                        @php
                            $config = $canton->configuracion;
                            // Forzamos la conversión a booleano para evitar errores de tipo
                            $verProvincia = $config ? (bool)$config->ver_provincia : false;
                            $verParroquias = $config ? (bool)$config->ver_parroquias : false;
                        @endphp
                        
                        <div class="bg-white p-5 rounded-2xl border flex flex-col md:flex-row md:items-center justify-between transition-all">
                            <div>
                                <h4 class="text-lg font-bold text-slate-900">{{ $canton->nombre }}</h4>
                                <p class="text-[10px] text-slate-500 uppercase font-semibold">Provincia: {{ $canton->provincia->nombre ?? 'N/A' }}</p>
                            </div>

                            <div class="flex gap-3">
                                <form action="{{ route('jurisdiccion.update', $canton->id) }}" method="POST" class="save-scroll">
                                    @csrf
                                    <input type="hidden" name="ver_provincia" value="{{ $verProvincia ? '0' : '1' }}">
                                    <input type="hidden" name="ver_parroquias" value="{{ $verParroquias ? '1' : '0' }}">
                                    
                                    <button type="submit" class="w-40 p-3 rounded-xl border-2 transition-all {{ $verProvincia ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-rose-50 border-rose-500 text-rose-700' }}">
                                        <span class="text-[9px] font-black uppercase">Resultados Prefecto</span><br>
                                        <span class="text-xs font-bold">{{ $verProvincia ? '✅ HABILITADO' : '🚫 DESHABILITADO' }}</span>
                                    </button>
                                </form>

                                <form action="{{ route('jurisdiccion.update', $canton->id) }}" method="POST" class="save-scroll">
                                    @csrf
                                    <input type="hidden" name="ver_provincia" value="{{ $verProvincia ? '1' : '0' }}">
                                    <input type="hidden" name="ver_parroquias" value="{{ $verParroquias ? '0' : '1' }}">
                                    
                                    <button type="submit" class="w-40 p-3 rounded-xl border-2 transition-all {{ $verParroquias ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-rose-50 border-rose-500 text-rose-700' }}">
                                        <span class="text-[9px] font-black uppercase">Resultados Vocales</span><br>
                                        <span class="text-xs font-bold">{{ $verParroquias ? '✅ HABILITADO' : '🚫 DESHABILITADO' }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Recuperar la posición guardada
            const scrollPos = localStorage.getItem("scrollPosition");
            if (scrollPos) {
                window.scrollTo(0, scrollPos);
                localStorage.removeItem("scrollPosition");
            }

            // Guardar la posición antes de enviar cualquier formulario con la clase 'save-scroll'
            const forms = document.querySelectorAll(".save-scroll");
            forms.forEach(form => {
                form.addEventListener("submit", function() {
                    localStorage.setItem("scrollPosition", window.scrollY);
                });
            });
        });
    </script>
</x-app-layout>