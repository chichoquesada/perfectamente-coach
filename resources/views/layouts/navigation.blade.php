<nav x-data="{ open: false }" class="bg-bg/80 backdrop-blur border-b border-white/[0.06] sticky top-0 z-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="font-serif italic text-base">
                    Perfecta<span class="text-gold">MENTE</span>
                </a>

                <div class="hidden sm:flex items-center gap-6">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Hoy
                    </x-nav-link>
                    <x-nav-link :href="route('plan.show')" :active="request()->routeIs('plan.show')">
                        Mi plan
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-text-secondary hover:text-text-primary transition">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="ms-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Salir
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-text-secondary hover:text-text-primary transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-white/[0.06]">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Hoy
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('plan.show')" :active="request()->routeIs('plan.show')">
                Mi plan
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-white/[0.06]">
            <div class="px-4">
                <div class="font-medium text-base text-text-primary">{{ Auth::user()->name }}</div>
                <div class="text-sm text-text-secondary">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Salir
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
