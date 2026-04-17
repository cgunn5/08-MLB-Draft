<x-app-layout>
    <div class="flex max-h-[calc(100dvh-38px)] min-h-0 w-full flex-1 flex-col overflow-hidden xl:max-h-[calc(100dvh-43px)] 2xl:max-h-[calc(100dvh-48px)]">
        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden px-2 py-2 sm:px-3 sm:py-2">
            <div
                class="flex min-h-0 w-full flex-1 flex-col overflow-hidden bg-white shadow-sm sm:rounded-lg"
            >
                <div
                    class="flex min-h-0 w-full min-w-0 flex-1 flex-col gap-4 overflow-hidden p-2 pb-4 sm:gap-5 sm:p-2.5 sm:pb-5 md:gap-6"
                >
                    @if ($hsPlayers->isEmpty())
                        <p class="shrink-0 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            {{ __('NO HIGH SCHOOL PLAYERS YET. RUN') }}
                            <code class="rounded bg-amber-100/80 px-1 text-xs">php artisan migrate --seed</code>.
                        </p>
                    @endif
                    {{-- Extra flex basis: scale(1.15) paints past the pre-transform box; traits sat on the next flex row and covered the header bottom. --}}
                    <div
                        class="relative z-10 flex min-h-0 w-full min-w-0 flex-[0_0_calc(40.25%+0.625rem)] flex-col overflow-visible"
                    >
                        {{-- In-flow scale wrapper: an only-absolute child collapses height (100% → 0), hiding the header. --}}
                        <div
                            class="flex min-h-0 h-full min-w-0 w-full flex-1 items-start justify-start overflow-visible"
                        >
                            <div
                                class="h-[calc(100%/1.15)] w-[calc(100%/1.15)] min-h-0 min-w-0 shrink-0 origin-top-left scale-[1.15]"
                            >
                                <x-player.profile-top
                                    :comfortable="true"
                                    :omit-center-column="true"
                                    :player="$player"
                                    :profile-player-list="$hsPlayers"
                                    profile-route-name="hs.players.show"
                                    :grade-definitions="\App\Models\Player::gradeRowDefinitionsHs()"
                                    combobox-sr-label="{{ __('High school player') }}"
                                    class="h-full min-h-0 w-full min-w-0"
                                />
                            </div>
                        </div>
                    </div>
                    <x-player.ranger-traits-hs
                        :player="$player"
                        class="relative z-0 mt-0 flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden pt-4 sm:pt-5 md:pt-6"
                    />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
