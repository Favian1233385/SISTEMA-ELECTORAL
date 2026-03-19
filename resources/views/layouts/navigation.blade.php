<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    {{-- Solo Admin General ve gestión de partidos y candidatos --}}
                    @if(auth()->user()->esAdmin())
                        <x-nav-link :href="route('partidos.index')" :active="request()->routeIs('partidos.*')">
                            {{ __('Partidos Políticos') }}
                        </x-nav-link>
                        <x-nav-link :href="route('candidatos.index')" :active="request()->routeIs('candidatos.*')">
                            {{ __('Candidatos') }}
                        </x-nav-link>
                    @endif

                    {{-- Solo el digitador tiene acceso visual directo a Actas / Votos --}}
                    @if(auth()->user()->esDigitador())
                        <x-nav-link :href="route('actas.create')" :active="request()->routeIs('actas.*')">
                            {{ __('Actas / Votos') }}
                        </x-nav-link>
                    @endif
                    
                    <x-nav-link :href="route('resultados.index')" :active="request()->routeIs('resultados.index')">
                        {{ __('Resultados') }}
                    </x-nav-link>

                    {{-- NUEVO: Usuarios visible para todos los niveles de administración --}}
                    @if(in_array(auth()->user()->role, ['admin', 'admin_provincial', 'admin_cantonal']))
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Usuarios') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- MENÚ RESPONSIVE (Móviles) --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if(auth()->user()->esAdmin())
                <x-responsive-nav-link :href="route('partidos.index')" :active="request()->routeIs('partidos.*')">
                    {{ __('Partidos Políticos') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('candidatos.index')" :active="request()->routeIs('candidatos.*')">
                    {{ __('Candidatos') }}
                </x-responsive-nav-link>
            @endif

            @if(auth()->user()->esDigitador())
                <x-responsive-nav-link :href="route('actas.create')" :active="request()->routeIs('actas.*')">
                    {{ __('Actas / Votos') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('resultados.index')" :active="request()->routeIs('resultados.index')">
                {{ __('Resultados') }}
            </x-responsive-nav-link>

            {{-- NUEVO: Usuarios en responsive para administradores territoriales --}}
            @if(in_array(auth()->user()->role, ['admin', 'admin_provincial', 'admin_cantonal']))
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Usuarios') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>