<x-app-layout>
    <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden">
        <div class="flex min-h-0 w-full flex-1 flex-col overflow-hidden px-2 py-2 sm:px-3 sm:py-2">
            <div
                class="flex min-h-0 w-full flex-1 flex-col overflow-hidden bg-white shadow-sm sm:rounded-lg"
            >
                <div
                    class="flex min-h-0 w-full min-w-0 flex-1 flex-col gap-y-0 overflow-hidden p-2 pb-2 sm:p-2.5 sm:pb-3"
                >
                    @if ($hsPlayers->isEmpty())
                        <p class="mb-3 shrink-0 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 sm:mb-4">
                            {{ __('NO HIGH SCHOOL PLAYERS YET. RUN') }}
                            <code class="rounded bg-amber-100/80 px-1 text-xs">php artisan migrate --seed</code>.
                        </p>
                    @endif
                    {{-- Header: content-sized; comp-heat lives in its own full-width band below. --}}
                    <div
                        class="relative z-10 flex min-h-0 w-full min-w-0 shrink-0 flex-col overflow-visible pb-2 sm:pb-3"
                    >
                        <div
                            class="flex min-h-0 min-w-0 w-full shrink-0 items-start justify-start overflow-visible"
                        >
                            <div
                                class="w-[calc(100%/1.15)] min-h-0 min-w-0 shrink-0 origin-top-left scale-[1.15]"
                            >
                                <x-player.profile-top
                                    :comfortable="true"
                                    :omit-center-column="true"
                                    :player="$player"
                                    :profile-player-list="$hsPlayers"
                                    profile-route-name="hs.players.show"
                                    :profile-route-query="$hsProfileRouteQuery"
                                    :grade-definitions="\App\Models\Player::gradeRowDefinitionsHs()"
                                    :ranger-sheet="$rangerSheet"
                                    combobox-sr-label="{{ __('High school player') }}"
                                    class="min-h-0 w-full min-w-0"
                                />
                            </div>
                        </div>
                    </div>
                    @if (! $hsPlayers->isEmpty())
                        @php
                            $hsCompHeatRoutePlayer = $player->exists ? $player : $hsPlayers->first();
                        @endphp
                        {{-- Full-width horizontal band inside the card: separates profile header from traits. --}}
                        <section
                            class="relative z-20 mt-4 w-full min-w-0 shrink-0 rounded-md border border-gray-200 bg-gray-50 px-3 py-4 normal-case shadow-sm sm:mt-5 sm:px-4 sm:py-5 md:mt-6 md:py-6"
                            aria-label="{{ __('Draft comp bucket for table heat') }}"
                        >
                            <nav class="flex w-full flex-wrap justify-center gap-2 gap-y-2">
                                @foreach (\App\Support\HsCompHeatScope::uiOptions() as $opt)
                                    @php
                                        $isActive = ($hsCompHeatScope ?? null) === ($opt['value'] ?? null);
                                        $href = route('hs.players.show', $hsCompHeatRoutePlayer);
                                        if (($opt['value'] ?? null) !== null) {
                                            $href .= '?'.http_build_query([\App\Support\HsCompHeatScope::QUERY_KEY => $opt['value']]);
                                        }
                                    @endphp
                                    <a
                                        href="{{ $href }}"
                                        @class([
                                            'rounded-md border px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wide shadow-sm transition sm:text-xs',
                                            'border-indigo-600 bg-indigo-50 text-indigo-900' => $isActive,
                                            'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50' => ! $isActive,
                                        ])
                                    >{{ $opt['label'] }}</a>
                                @endforeach
                            </nav>
                        </section>
                    @endif
                    <x-player.ranger-traits-hs
                        :player="$player"
                        :ranger-sheet="$rangerSheet"
                        @class([
                            'relative z-0 flex min-h-0 min-w-0 flex-1 basis-0 flex-col overflow-hidden',
                            'mt-4 sm:mt-5 md:mt-6' => ! $hsPlayers->isEmpty(),
                            'mt-0' => $hsPlayers->isEmpty(),
                        ])
                    />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
