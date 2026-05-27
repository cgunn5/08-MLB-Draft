<x-app-layout>
    @php($rangerSheet = $rangerSheet ?? [])
    <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden">
        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden px-2 pt-2 pb-1.5 sm:px-3 sm:pt-2 sm:pb-1.5">
            <div
                class="flex min-h-0 w-full flex-1 flex-col overflow-hidden bg-white shadow-sm sm:rounded-lg"
            >
                <div
                    class="flex min-h-0 w-full min-w-0 flex-1 flex-col gap-4 overflow-hidden p-2 pb-2 sm:gap-5 sm:p-2.5 sm:pb-3 md:gap-6"
                >
                    @if ($ncaaPlayers->isEmpty())
                        <p class="shrink-0 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            {{ __('NO COLLEGIATE PLAYERS YET. RUN') }}
                            <code class="rounded bg-amber-100/80 px-1 text-xs">php artisan migrate --seed</code>.
                        </p>
                    @endif
                    {{-- Same flex basis + scale as HS so the profile strip matches that page. --}}
                    <div
                        class="relative z-10 flex min-h-0 w-full min-w-0 flex-[0_0_calc(40.25%+0.625rem)] flex-col overflow-visible"
                    >
                        <div
                            class="flex min-h-0 h-full min-w-0 w-full flex-1 items-start justify-start overflow-visible"
                        >
                            <div
                                class="h-[calc(100%/1.15)] w-[calc(100%/1.15)] min-h-0 min-w-0 shrink-0 origin-top-left scale-[1.15]"
                            >
                                <x-player.profile-top
                                    :comfortable="true"
                                    :grade-definitions="\App\Models\Player::gradeRowDefinitionsNcaa()"
                                    :omit-center-column="true"
                                    :player="$player"
                                    :profile-player-list="$ncaaPlayers"
                                    profile-route-name="ncaa.players.show"
                                    :ranger-sheet="$rangerSheet"
                                    combobox-sr-label="{{ __('NCAA / JUCO player') }}"
                                    class="h-full min-h-0 w-full min-w-0"
                                />
                            </div>
                        </div>
                    </div>
                    <x-player.ranger-traits-ncaa
                        :player="$player"
                        :ranger-sheet="$rangerSheet"
                        class="relative z-0 flex min-h-0 min-w-0 flex-1 basis-0 flex-col overflow-x-hidden pt-4 sm:pt-5 md:pt-6"
                    />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
