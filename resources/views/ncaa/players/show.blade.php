<x-app-layout>
    @php($rangerSheet = $rangerSheet ?? [])
    <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden">
        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden px-2 pt-2 pb-1.5 sm:px-3 sm:pt-2 sm:pb-1.5">
            <div
                class="flex min-h-0 w-full flex-1 flex-col overflow-hidden bg-white shadow-sm sm:rounded-lg"
            >
                @php($ncaaCompHeatNav = ($ncaaCompHeatRoutePlayer ?? null) instanceof \App\Models\Player)
                <div
                    @class([
                        'flex min-h-0 w-full min-w-0 flex-1 flex-col overflow-hidden p-2 pb-2 sm:p-2.5 sm:pb-3',
                        'gap-2 sm:gap-2.5 md:gap-3' => $ncaaCompHeatNav,
                        'gap-4 sm:gap-5 md:gap-6' => ! $ncaaCompHeatNav,
                    ])
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
                                    :omit-center-column="true"
                                    :player="$player"
                                    :profile-player-list="$ncaaPlayers"
                                    profile-route-name="ncaa.players.show"
                                    :profile-route-query="$ncaaProfileRouteQuery ?? []"
                                    :ranger-sheet="$rangerSheet"
                                    combobox-sr-label="{{ __('NCAA / JUCO player') }}"
                                    class="h-full min-h-0 w-full min-w-0"
                                />
                            </div>
                        </div>
                    </div>
                    @if ($ncaaCompHeatNav)
                        {{-- mt + vertical padding: clears overlap with profile; symmetric py centers chips in the channel. Traits gets matching -mt so headers/tables stay fixed. --}}
                        <div
                            class="z-20 flex w-full min-w-0 shrink-0 justify-center px-2 py-2 mt-3 sm:mt-4 sm:px-3 sm:py-2.5"
                        >
                            @include('components.player.ranger-traits-ncaa.partials.comp-heat-nav', [
                                'compHeatScope' => $ncaaCompHeatScope ?? null,
                                'compHeatRoutePlayer' => $ncaaCompHeatRoutePlayer,
                            ])
                        </div>
                    @endif
                    <x-player.ranger-traits-ncaa
                        :player="$player"
                        :ranger-sheet="$rangerSheet"
                        class="relative z-0 flex min-h-0 min-w-0 flex-1 basis-0 flex-col overflow-x-hidden mt-0 {{ $ncaaCompHeatNav ? '-mt-7 pt-0 sm:-mt-9 sm:pt-0.5 md:pt-1' : 'pt-4 sm:pt-5 md:pt-6' }}"
                    />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
