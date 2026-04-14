<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ route('ncaa.index') }}"
                class="text-sm font-medium text-gray-600 hover:text-gray-900"
            >
                {{ __('← NCAA / JUCO') }}
            </a>
            <span class="text-gray-300" aria-hidden="true">|</span>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ strtoupper($player->last_name) }}, {{ strtoupper($player->first_name) }}
            </h2>
        </div>
    </x-slot>

    <div class="w-full py-4 sm:py-5">
        <div class="w-full px-3 sm:px-4 lg:px-6">
            <div class="w-full overflow-visible border border-gray-100 bg-white shadow-sm sm:rounded-lg">
                <div class="w-full min-w-0 p-2 sm:p-3 lg:p-4">
                    <x-player.profile-top :player="$player" :ncaa-players="$ncaaPlayers" />
                    <x-player.ranger-traits
                        :player="$player"
                        class="mt-4 border-t border-gray-200 pt-4 sm:mt-5 sm:pt-5"
                    />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
