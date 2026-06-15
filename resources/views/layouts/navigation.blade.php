@php
    $navItems = \App\Support\AppNavigation::items();
    $currentNavLabel = \App\Support\AppNavigation::currentLabel();
@endphp
<nav x-data="{ open: false, navSelectOpen: false }" class="app-navigation relative z-[120] bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="app-nav-inner mx-auto w-full">
        {{-- Bar + logo: .app-nav-row / .app-nav-logo in app.css (px + !important; beats preflight img + root rem scaling). --}}
        <div class="app-nav-row relative flex items-center justify-between">
            <!-- Page select (left) -->
            <div class="app-nav-select-wrap hidden shrink-0 items-center sm:flex">
                <div
                    class="app-nav-select relative"
                    @click.outside="navSelectOpen = false"
                >
                    <button
                        type="button"
                        class="app-nav-select-trigger"
                        :aria-expanded="navSelectOpen"
                        aria-haspopup="listbox"
                        @click="navSelectOpen = ! navSelectOpen"
                    >
                        <span class="app-nav-select-trigger-label">{{ __($currentNavLabel) }}</span>
                        <svg class="app-nav-select-caret" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-show="navSelectOpen"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="app-nav-select-menu"
                        role="listbox"
                        style="display: none;"
                        @click="navSelectOpen = false"
                    >
                        @foreach ($navItems as $item)
                            <a
                                href="{{ $item['href'] }}"
                                role="option"
                                @class(['app-nav-select-option', 'is-active' => $item['active']])
                                @if ($item['active']) aria-selected="true" @endif
                            >
                                <span class="app-nav-select-option-check" aria-hidden="true">
                                    @if ($item['active'])
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </span>
                                <span class="app-nav-select-option-label">{{ __($item['label']) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Logos (center) -->
            <div class="app-nav-logo-wrap pointer-events-none absolute inset-x-0 flex items-center justify-center">
                <div class="app-nav-logos pointer-events-auto flex items-center gap-2 sm:gap-2.5">
                    <a href="{{ url(auth()->user()->applicationHomePath()) }}" class="flex h-full min-h-0 items-center">
                        <img
                            src="{{ asset('images/texas-rangers-logo.png') }}"
                            alt="{{ __('Texas Rangers') }}"
                            class="app-nav-logo"
                            width="120"
                            height="40"
                            decoding="async"
                        />
                    </a>
                    <img
                        src="{{ asset('images/mlb-draft-logo.png') }}"
                        alt="{{ __('MLB Draft') }}"
                        class="app-nav-logo app-nav-draft-logo"
                        width="120"
                        height="40"
                        decoding="async"
                    />
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="app-nav-user-wrap relative z-[130] hidden sm:flex sm:items-center">
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
                        <x-dropdown-link :href="url('/profile')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ url('/logout') }}">
                            @csrf

                            <x-dropdown-link :href="url('/logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="relative z-[130] -me-2 flex items-center sm:hidden">
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
        <div class="px-4 pt-2 pb-1 text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ __($currentNavLabel) }}
        </div>
        <div class="pt-1 pb-2 space-y-0.5">
            @foreach ($navItems as $item)
                <x-responsive-nav-link :href="$item['href']" :active="$item['active']">
                    {{ __($item['label']) }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-2 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-sm text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-xs text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-2 space-y-0.5">
                <x-responsive-nav-link :href="url('/profile')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="url('/logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
