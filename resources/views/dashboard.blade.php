<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('HOME') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ route('board.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('WORKING BOARD') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('CARDS, GROUPS, SORT, FILTER') }}</p>
                </a>
                <a href="{{ route('players.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('PLAYER LIST') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('MASTER ROSTER & AGGREGATE RANKS') }}</p>
                </a>
                <a href="{{ route('ncaa.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('NCAA / JUCO') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('COLLEGIATE DASHBOARD') }}</p>
                </a>
                <a href="{{ route('hs.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('HIGH SCHOOL') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('HS DASHBOARD') }}</p>
                </a>
                <a href="{{ route('notes.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('NOTE INPUT') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('SKILL-BASED NOTES') }}</p>
                </a>
                <a href="{{ route('data-sources.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                    <h3 class="font-semibold text-gray-900">{{ __('DATA SOURCES') }}</h3>
                    <p class="mt-2 text-sm text-gray-600">{{ __('UPLOADS & PROFILES') }}</p>
                </a>
                @if (Auth::user()->is_admin)
                    <a href="{{ route('admin.users.index') }}" class="block p-6 bg-white shadow-sm sm:rounded-lg border border-gray-100 hover:border-gray-300 transition">
                        <h3 class="font-semibold text-gray-900">{{ __('USER ACCOUNTS') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('INVITE STAKEHOLDERS') }}</p>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
