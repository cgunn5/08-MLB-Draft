@props([
    'player',
    'ncaaPlayers' => null,
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Player>|null $ncaaPlayers */
    $playerList = $ncaaPlayers ?? collect();
    $listSummary = $player->listSummaryLine();
    $comboboxPlayers = $playerList
        ->map(
            fn ($p) => [
                'id' => $p->id,
                'label' => strtoupper($p->last_name).', '.strtoupper($p->first_name),
                'url' => route('ncaa.players.show', $p),
            ],
        )
        ->values()
        ->all();
@endphp

<div class="w-full min-w-0">
    {{-- Three equal columns; overflow-hidden on each track prevents bleed/overlap into neighbors. --}}
    <div
        {{ $attributes->merge([
            'class' =>
                'grid w-full min-w-0 grid-cols-3 items-stretch gap-2 sm:gap-3 md:gap-4 2xl:gap-5',
        ]) }}
    >
        {{-- Left: master note --}}
        <div class="relative z-0 flex min-h-0 min-w-0 flex-col overflow-hidden">
            <aside
                class="flex min-h-[2.7rem] w-full min-w-0 max-w-full flex-1 items-center justify-center self-stretch border border-gray-500 bg-[#f2f6f9] px-1 py-px sm:min-h-[3.15rem] sm:px-1 sm:py-0.5 md:min-h-0 md:px-1.5 md:py-1"
            >
                <p
                    class="max-w-full break-words text-center font-sans font-[700] leading-tight text-gray-700 text-[0.5rem] sm:text-[0.53125rem] md:text-[0.5625rem]"
                >
                    {{ filled($player->master_take) ? $player->master_take : '#N/A' }}
                </p>
            </aside>
        </div>

        {{-- Middle: same height as master take; summary lives inside the white panel under the select --}}
        <div class="relative z-0 flex h-full min-h-0 min-w-0 flex-col overflow-visible">
            <div
                class="flex min-h-[2.7rem] w-full min-w-0 max-w-full flex-1 flex-col border-2 border-red-600 bg-red-50/90 p-px sm:min-h-[3.15rem] sm:border-[3px] sm:p-0.5 md:min-h-0"
            >
                <div
                    class="flex min-h-0 flex-1 flex-col overflow-visible border border-gray-800 bg-white px-0.5 py-0.5 sm:px-1 sm:py-1 md:px-1.5 md:py-1.5"
                >
                    <div
                        class="flex min-h-0 flex-1 flex-col items-center justify-center gap-1 sm:gap-1.5"
                    >
                        @if ($playerList->isNotEmpty())
                            <div
                                class="relative z-30 w-full min-w-0 shrink-0"
                                x-data="ncaaPlayerCombobox({
                                    players: {{ \Illuminate\Support\Js::from($comboboxPlayers) }},
                                    selectedId: {{ $player->id }},
                                    selectedLabel: {{ \Illuminate\Support\Js::from(strtoupper($player->last_name).', '.strtoupper($player->first_name)) }},
                                })"
                                @click.outside="close()"
                                @keydown.escape.window="open && close()"
                            >
                                <label class="sr-only" for="profile-player-combobox-trigger">{{ __('NCAA / JUCO player') }}</label>
                                <button
                                    id="profile-player-combobox-trigger"
                                    type="button"
                                    class="flex w-full min-w-0 max-w-full cursor-pointer items-center justify-center gap-1 rounded-sm border-0 bg-white bg-[length:0.55rem] bg-[right_0.3rem_center] bg-no-repeat py-0.5 pl-1 pr-5 text-center font-sans font-[700] text-xs leading-tight tracking-wide text-red-600 shadow-none ring-0 sm:bg-[right_0.35rem_center] sm:py-1 sm:pr-5 sm:text-sm md:text-base 2xl:text-lg"
                                    style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke-width=%222%22 stroke=%22%23dc2626%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/%3E%3C/svg%3E')"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-haspopup="listbox"
                                >
                                    <span class="min-w-0 truncate" x-text="selectedLabel"></span>
                                </button>
                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition
                                    @click.stop
                                    class="absolute left-0 right-0 top-full z-50 mt-0.5 rounded border border-gray-300 bg-white py-1 shadow-lg ring-1 ring-black/5"
                                >
                                    <div class="border-b border-gray-100 px-1.5 pb-1.5 pt-0.5">
                                        <label class="sr-only" for="profile-player-combobox-filter">{{ __('Filter players') }}</label>
                                        <input
                                            id="profile-player-combobox-filter"
                                            x-ref="filterInput"
                                            x-model="query"
                                            type="text"
                                            autocomplete="off"
                                            class="w-full rounded border border-gray-200 px-1.5 py-0.5 font-sans text-[0.65rem] font-normal normal-case text-gray-900 placeholder:text-gray-400 focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400/40 sm:text-xs"
                                            placeholder="{{ __('Type to filter…') }}"
                                        />
                                    </div>
                                    <ul
                                        role="listbox"
                                        class="max-h-[min(55vh,12rem)] overflow-y-auto overscroll-contain font-sans text-[0.65rem] font-normal leading-tight text-gray-900 sm:max-h-[min(50vh,11rem)] sm:text-xs"
                                    >
                                        <template x-for="p in filtered" :key="p.id">
                                            <li role="option" :aria-selected="p.id === selectedId">
                                                <button
                                                    type="button"
                                                    class="flex w-full px-2 py-0.5 text-left hover:bg-red-50 focus:bg-red-50 focus:outline-none"
                                                    :class="p.id === selectedId ? 'bg-red-50/80 font-[700]' : 'font-normal'"
                                                    @click="choose(p)"
                                                    x-text="p.label"
                                                ></button>
                                            </li>
                                        </template>
                                    </ul>
                                    <p
                                        x-show="filtered.length === 0"
                                        x-cloak
                                        class="px-2 py-1.5 font-sans text-[0.65rem] font-normal text-gray-500 sm:text-xs"
                                    >
                                        {{ __('No matching players.') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <h1
                                class="flex min-w-0 max-w-full shrink-0 items-center justify-center text-center font-sans font-[700] text-sm leading-tight tracking-wide text-red-600 sm:text-base md:text-lg lg:text-xl"
                            >
                                {{ strtoupper($player->last_name) }}, {{ strtoupper($player->first_name) }}
                            </h1>
                        @endif
                        @if (filled($listSummary))
                            <p
                                class="max-w-full shrink-0 break-words px-0.5 text-center font-sans font-[700] leading-snug text-gray-700 text-[0.5rem] sm:text-[0.53125rem] md:text-[0.5625rem]"
                            >
                                {{ $listSummary }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: grades | radar | logo (contained to column 3) --}}
        <div class="relative z-0 flex min-h-0 min-w-0 flex-col overflow-hidden">
            <div
                class="flex min-h-[2.7rem] w-full min-w-0 max-w-full flex-1 flex-col items-center justify-center gap-0.5 sm:min-h-[3.15rem] sm:flex-row sm:items-stretch sm:justify-between sm:gap-1.5 md:min-h-0 md:gap-2 2xl:gap-3"
            >
                <div
                    class="flex h-full min-h-0 min-w-0 w-full shrink-0 items-stretch justify-center sm:flex-1 sm:min-w-0"
                >
                    <div class="flex h-full min-h-0 min-w-0 max-w-full items-stretch">
                        {{-- CSS Grid (not <table>): browsers don't distribute <tr> heights reliably; 7× minmax(0,1fr) rows are equal. --}}
                        <div
                            role="table"
                            aria-label="{{ __('Grade summary') }}"
                            class="grid h-full min-h-0 w-[4.125rem] grid-cols-[11fr_14fr] grid-rows-[repeat(7,minmax(0,1fr))] gap-px bg-gray-300 p-px font-sans font-[700] text-[0.328125rem] leading-none sm:w-[4.375rem] sm:text-[0.339844rem] md:w-[4.625rem] md:text-[0.351563rem] 2xl:w-[5.35rem] 2xl:text-[0.38rem]"
                        >
                            @foreach (\App\Models\Player::gradeRowDefinitions() as $label => $attribute)
                                <div role="row" class="contents">
                                    <div
                                        role="rowheader"
                                        class="flex min-h-0 min-w-0 items-center justify-center overflow-hidden bg-[#44546A] px-px py-0 text-center font-[700] text-white"
                                    >
                                        {{ $label }}
                                    </div>
                                    <div
                                        role="cell"
                                        class="flex min-h-0 min-w-0 items-center justify-center overflow-hidden bg-white px-px py-0 text-center font-[700] tabular-nums text-black"
                                    >
                                        {{ $player->gradeCellDisplay($attribute) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex h-full shrink-0 items-center justify-center self-stretch">
                    <x-player.radar-chart :player="$player" class="min-w-0 shrink-0" />
                </div>

                <div class="flex h-full shrink-0 items-center justify-center self-stretch py-px sm:py-0.5 sm:pl-px md:pl-0.5">
                    <img
                        src="{{ asset('images/mlb-draft-logo.png') }}"
                        alt="{{ __('MLB DRAFT') }}"
                        class="h-auto max-h-[2.35rem] w-auto max-w-[2.05rem] object-contain object-right sm:max-h-[3rem] sm:max-w-[2.6rem] md:max-h-[3.65rem] md:max-w-[3.15rem] lg:max-h-[4.1rem] lg:max-w-[3.5rem] 2xl:max-h-[5.75rem] 2xl:max-w-[5rem]"
                        width="160"
                        height="192"
                    />
                </div>
            </div>
        </div>
    </div>
</div>
