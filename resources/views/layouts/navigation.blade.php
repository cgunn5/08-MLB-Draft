<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 xl:max-w-[90rem] 2xl:max-w-[110rem] 2xl:px-4">
        {{-- Bar + logo: .app-nav-row / .app-nav-logo in app.css (px + !important; beats preflight img + root rem scaling). --}}
        <div class="app-nav-row flex justify-between">
            <div class="flex min-h-0">
                <!-- Logo -->
                <div class="flex h-full min-h-0 shrink-0 items-center overflow-hidden">
                    <a href="{{ route('dashboard') }}" class="flex h-full min-h-0 max-w-[120px] items-center">
                        <img
                            src="{{ asset('images/texas-rangers-logo.png') }}"
                            alt="{{ __('Texas Rangers') }}"
                            class="app-nav-logo"
                            width="120"
                            height="40"
                            decoding="async"
                        />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-5 sm:-my-px sm:ms-6 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('HOME') }}
                    </x-nav-link>
                    <x-nav-link :href="route('board.index')" :active="request()->routeIs('board.*')">
                        {{ __('BOARD') }}
                    </x-nav-link>
                    <x-nav-link :href="route('players.index')" :active="request()->routeIs('players.*')">
                        {{ __('PLAYERS') }}
                    </x-nav-link>
                    <x-nav-link :href="route('ncaa.index')" :active="request()->routeIs('ncaa.*')">
                        {{ __('NCAA') }}
                    </x-nav-link>
                    <x-nav-link :href="route('hs.index')" :active="request()->routeIs('hs.*')">
                        {{ __('HS') }}
                    </x-nav-link>
                    <x-nav-link :href="route('notes.index')" :active="request()->routeIs('notes.*')">
                        {{ __('Notes/Grades') }}
                    </x-nav-link>
                    <x-nav-link :href="route('data-sources.index')" :active="request()->routeIs('data-sources.*')">
                        {{ __('DATA') }}
                    </x-nav-link>
                    @if (Auth::user()->is_admin)
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">
                            {{ __('USERS') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-1.5 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-[0.9rem] w-[0.9rem]" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-1 pb-2 space-y-0.5">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('HOME') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('board.index')" :active="request()->routeIs('board.*')">
                {{ __('BOARD') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('players.index')" :active="request()->routeIs('players.*')">
                {{ __('PLAYERS') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ncaa.index')" :active="request()->routeIs('ncaa.*')">
                {{ __('NCAA') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('hs.index')" :active="request()->routeIs('hs.*')">
                {{ __('HS') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notes.index')" :active="request()->routeIs('notes.*')">
                {{ __('Notes/Grades') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('data-sources.index')" :active="request()->routeIs('data-sources.*')">
                {{ __('DATA') }}
            </x-responsive-nav-link>
            @if (Auth::user()->is_admin)
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">
                    {{ __('USERS') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-2 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-sm text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-xs text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-2 space-y-0.5">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
