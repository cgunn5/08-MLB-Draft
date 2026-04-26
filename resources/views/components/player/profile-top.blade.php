@props([
    'player',
    'ncaaPlayers' => null,
    'profilePlayerList' => null,
    'profileRouteName' => 'ncaa.players.show',
    'profileRouteQuery' => [],
    'gradeDefinitions' => null,
    'comboboxSrLabel' => null,
    'comfortable' => false,
    'compactProfile' => false,
    'omitCenterColumn' => false,
    'rangerSheet' => null,
])

@php
    $compactProfile = filter_var($compactProfile, FILTER_VALIDATE_BOOLEAN);
    $omitCenterColumn = filter_var($omitCenterColumn ?? false, FILTER_VALIDATE_BOOLEAN);
    $hsGradesRadarRowGrid = $omitCenterColumn && ! $compactProfile;
    $fillHeaderRadar = ! $compactProfile;
    /* HS omit: same 3-col + gaps as ranger-traits-hs so middle column matches Approach / Miss & Impact/Damage. */
    $profileTopGridColsClass = $omitCenterColumn
        ? 'grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)]'
        : 'grid-cols-3';
    $profileTopGridGapClass = $omitCenterColumn
        ? 'gap-1.5 sm:gap-2 md:gap-3 lg:gap-4 2xl:gap-5'
        : 'gap-0.5 sm:gap-1 md:gap-1.5 2xl:gap-2';
    /** @var \Illuminate\Support\Collection<int, \App\Models\Player>|null $legacyList */
    $legacyList = $ncaaPlayers ?? null;
    /** @var \Illuminate\Support\Collection<int, \App\Models\Player> $playerList */
    $playerList = $profilePlayerList ?? $legacyList ?? collect();
    $isProfilePlaceholder = ! $player->exists;
    $comboboxSelectedLabel = $isProfilePlaceholder
        ? __('SELECT PLAYER…')
        : strtoupper($player->last_name).', '.strtoupper($player->first_name);
    $comboboxSelectedIdJson = \Illuminate\Support\Js::from($player->getKey());
    $comboboxSelectedLabelJson = \Illuminate\Support\Js::from($comboboxSelectedLabel);
    $gradeDefsForGrid = $gradeDefinitions ?? \App\Models\Player::gradeRowDefinitions();
    $gradeRowCount = count($gradeDefsForGrid);
    $listSummary = $player->listSummaryLine();
    $rangerSheet = is_array($rangerSheet) ? $rangerSheet : [];
    $overallDemographics = $rangerSheet['overall_demographics'] ?? null;
    $overallDemographics = is_array($overallDemographics) ? $overallDemographics : null;
    $aggregateRankBoardHeatStyle = (! $compactProfile && $omitCenterColumn)
        ? $player->aggregateRankBoardHeatStyle()
        : null;
    $modelDraftListRankBoardHeatStyle = (! $compactProfile && $omitCenterColumn)
        ? $player->modelDraftListRankBoardHeatStyle()
        : null;
    $profileRouteQuery = is_array($profileRouteQuery) ? $profileRouteQuery : [];
    $comboboxPlayers = $playerList
        ->map(
            function ($p) use ($profileRouteName, $profileRouteQuery) {
                $url = route($profileRouteName, $p);
                if ($profileRouteQuery !== []) {
                    $url .= '?'.http_build_query($profileRouteQuery);
                }

                return [
                    'id' => $p->id,
                    'label' => strtoupper($p->last_name).', '.strtoupper($p->first_name),
                    'url' => $url,
                ];
            },
        )
        ->values()
        ->all();
    $comboboxAccessibleLabel = $comboboxSrLabel ?? __('NCAA / JUCO player');

    if ($compactProfile) {
        $summaryText =
            'line-clamp-1 max-h-[1.1em] text-[0.3rem] sm:text-[0.32rem] leading-none';
        $comboBtn =
            'bg-[length:0.38rem] py-0 pr-4 text-[0.52rem] leading-none sm:bg-[right_0.22rem_center] sm:py-0 sm:pr-4 sm:text-[0.55rem]';
        $comboInput = 'text-[0.48rem] sm:text-[0.5rem]';
        $comboList = 'text-[0.48rem] sm:text-[0.5rem]';
        $fallbackName = 'text-[0.55rem] leading-none sm:text-[0.58rem]';
        $gradeGridClass =
            'w-[2.9375rem] text-[0.14rem] sm:w-[3.125rem] sm:text-[0.15rem] md:w-[3.3125rem] md:text-[0.16rem]';
        $logoClass = 'max-h-[1.442rem] max-w-[1.24rem] sm:max-h-[1.594rem] sm:max-w-[1.366rem]';
    } elseif ($comfortable) {
        $summaryText = 'text-[0.625rem] sm:text-[0.75rem] md:text-[0.8125rem]';
        $comboBtn =
            'text-sm sm:text-base md:text-lg 2xl:text-xl bg-[length:0.65rem] sm:bg-[length:0.72rem]';
        $comboInput = 'text-xs sm:text-sm';
        $comboList = 'text-xs sm:text-sm';
        $fallbackName = 'text-base sm:text-lg md:text-xl lg:text-2xl';
        $gradeGridClass =
            'w-[6.0625rem] text-[0.42rem] sm:w-[6.6875rem] sm:text-[0.46rem] md:w-[7.3125rem] md:text-[0.5rem] lg:w-[7.9375rem] lg:text-[0.54rem] 2xl:w-[8.5625rem] 2xl:text-[0.58rem]';
        /* 2xl max-h capped below comfortable radar (9.35rem) so row height stays radar-driven. */
        $logoClass =
            'max-h-[4.326rem] max-w-[3.795rem] sm:max-h-[5.541rem] sm:max-w-[4.782rem] md:max-h-[6.603rem] md:max-w-[5.693rem] lg:max-h-[7.362rem] lg:max-w-[6.3rem] 2xl:max-h-[9.33rem] 2xl:max-w-[8.577rem]';
    } else {
        $summaryText = 'text-[0.5rem] sm:text-[0.53125rem] md:text-[0.5625rem]';
        $comboBtn =
            'text-xs sm:text-sm md:text-base 2xl:text-lg bg-[length:0.55rem]';
        $comboInput = 'text-[0.65rem] sm:text-xs';
        $comboList = 'text-[0.65rem] sm:text-xs';
        $fallbackName = 'text-sm sm:text-base md:text-lg lg:text-xl';
        $gradeGridClass =
            'w-[5.15625rem] text-[0.328125rem] sm:w-[5.46875rem] sm:text-[0.339844rem] md:w-[5.78125rem] md:text-[0.351563rem] 2xl:w-[6.6875rem] 2xl:text-[0.38rem]';
        /* 2xl max-h capped under default radar (8rem). */
        $logoClass =
            'max-h-[3.567rem] max-w-[3.112rem] sm:max-h-[4.554rem] sm:max-w-[3.947rem] md:max-h-[5.541rem] md:max-w-[4.782rem] lg:max-h-[6.224rem] lg:max-w-[5.313rem] 2xl:max-h-[7.9rem] 2xl:max-w-[7.59rem]';
    }
@endphp

<div
    {{ $attributes->merge([
        'class' =>
            'w-full min-w-0 '.
            ($compactProfile
                ? 'profile-top-compact-shell profile-top--compact'
                : 'flex h-full min-h-0 flex-col').
            ($comfortable && ! $compactProfile ? ' profile-top--comfortable' : ''),
    ]) }}
>
    {{-- Three equal columns; overflow-hidden on each track prevents bleed/overlap into neighbors. --}}
    <div
        @class([
            'grid w-full min-w-0 items-stretch',
            $profileTopGridGapClass,
            $profileTopGridColsClass,
            'shrink-0 profile-top-compact-grid' => $compactProfile,
            'h-full min-h-0 flex-1' => ! $compactProfile,
        ])
    >
        @if ($omitCenterColumn)
            {{-- HS: same 3×1fr + gaps as ranger-traits-hs — select column width matches Approach / Miss & Impact/Damage --}}
            <aside
                @class([
                    'profile-master-take-aside flex min-h-0 min-w-0 h-full items-center justify-center self-stretch bg-[#f2f6f9]',
                    'app-outline-soft' => true,
                    'px-1 py-px sm:px-1 sm:py-0.5 md:px-1.5 md:py-1' => ! $compactProfile && ! $comfortable,
                    'px-1.5 py-0.5 sm:px-2 sm:py-1 md:px-2.5 md:py-1' => ! $compactProfile && $comfortable,
                    'px-px py-0' => $compactProfile,
                    'min-h-[2.7rem] sm:min-h-[3.15rem] md:min-h-0' => ! $compactProfile,
                    'min-h-0 max-h-full' => $compactProfile,
                ])
            >
                <p
                    class="profile-master-take-text max-w-full break-words text-center font-sans font-[700] text-black"
                >
                    {{ filled($player->master_take) ? $player->master_take : '-' }}
                </p>
            </aside>
            <div
                @class([
                    'relative z-0 flex min-h-0 min-w-0 w-full max-w-full flex-col items-center justify-center self-stretch bg-white',
                    'app-outline-soft' => true,
                    'px-px py-0' => $compactProfile,
                    'px-0.5 py-0.5 sm:px-1 sm:py-1 md:px-1.5 md:py-1.5' => ! $compactProfile,
                ])
            >
                <div
                    @class([
                        'flex min-h-0 w-full min-w-0 flex-col items-center justify-center',
                        'gap-0 sm:gap-px' => $compactProfile,
                        'gap-1 sm:gap-1.5' => ! $compactProfile,
                    ])
                >
                        @if ($playerList->isNotEmpty())
                            <div
                                class="relative z-30 w-full min-w-0 shrink-0"
                                x-data="ncaaPlayerCombobox({
                                    players: {{ \Illuminate\Support\Js::from($comboboxPlayers) }},
                                    selectedId: {{ $comboboxSelectedIdJson }},
                                    selectedLabel: {{ $comboboxSelectedLabelJson }},
                                })"
                                @click.outside="close()"
                                @keydown.escape.window="open && close()"
                            >
                                <label class="sr-only" for="profile-player-combobox-trigger">{{ $comboboxAccessibleLabel }}</label>
                                <button
                                    id="profile-player-combobox-trigger"
                                    type="button"
                                    class="flex w-full min-w-0 max-w-full cursor-pointer items-center justify-center gap-0.5 rounded-sm border-0 bg-white bg-[right_0.3rem_center] bg-no-repeat py-0 pl-0.5 text-center font-sans leading-none tracking-wide shadow-none ring-0 sm:bg-[right_0.35rem_center] sm:pl-1 {{ $comboBtn }}"
                                    style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke-width=%222%22 stroke=%22%230c2340%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/%3E%3C/svg%3E')"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-haspopup="listbox"
                                >
                                    <span class="rangers-wordmark-text min-w-0 truncate" x-text="selectedLabel"></span>
                                </button>
                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition
                                    @click.stop
                                    class="absolute left-0 right-0 top-full z-50 mt-0.5 bg-white py-1 shadow-lg ring-1 ring-black/5 app-outline-soft"
                                >
                                    <div class="border-b border-gray-100 px-1.5 pb-1.5 pt-0.5">
                                        <label class="sr-only" for="profile-player-combobox-filter">{{ __('Filter players') }}</label>
                                        <input
                                            id="profile-player-combobox-filter"
                                            x-ref="filterInput"
                                            x-model="query"
                                            type="text"
                                            autocomplete="off"
                                            class="w-full rounded-md border border-[rgb(203_213_225)] px-1.5 py-0.5 font-sans font-normal normal-case text-gray-900 placeholder:text-gray-400 focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400/40 {{ $comboInput }}"
                                            placeholder="{{ __('Type to filter…') }}"
                                        />
                                    </div>
                                    <ul
                                        role="listbox"
                                        class="max-h-[min(55vh,12rem)] overflow-y-auto overscroll-contain font-sans font-normal leading-tight text-gray-900 sm:max-h-[min(50vh,11rem)] {{ $comboList }}"
                                    >
                                        <template x-for="p in filtered" :key="p.id">
                                            <li role="option" :aria-selected="p.id === selectedId">
                                                <button
                                                    type="button"
                                                    class="rangers-wordmark-text flex w-full px-2 py-0.5 text-left hover:bg-red-50 focus:bg-red-50 focus:outline-none"
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
                                        class="px-2 py-1.5 font-sans font-normal text-gray-500 {{ $comboList }}"
                                    >
                                        {{ __('No matching players.') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <h1
                                class="rangers-wordmark-text flex min-w-0 max-w-full shrink-0 items-center justify-center text-center font-sans leading-tight tracking-wide {{ $fallbackName }}"
                            >
                                {{ $comboboxSelectedLabel }}
                            </h1>
                        @endif
                        @if (! $compactProfile)
                            <div
                                class="flex max-w-full shrink-0 flex-col items-center gap-1 px-0.5 text-center font-sans font-[700] leading-snug text-gray-700 {{ $summaryText }}"
                            >
                                <p class="max-w-full break-words">
                                    {{ $player->profileHeaderBioLine($overallDemographics) }}
                                </p>
                                <div
                                    class="flex max-w-full flex-wrap items-center justify-center gap-x-2 gap-y-0.5 sm:gap-x-3"
                                >
                                    <span class="inline-flex items-center gap-x-1 tabular-nums">
                                        <span>RK</span>
                                        <span
                                            class="box-border inline-flex min-h-[1.125rem] min-w-[1.25rem] items-center justify-center rounded-sm px-0.5 py-px font-[700]"
                                            @if ($aggregateRankBoardHeatStyle !== null)
                                                style="{{ $aggregateRankBoardHeatStyle }}"
                                            @endif
                                        >{{ $player->aggregate_rank ?? '-' }}</span>
                                    </span>
                                    <span class="text-gray-300" aria-hidden="true">·</span>
                                    <span class="inline-flex items-center gap-x-1 tabular-nums">
                                        <span>MDL</span>
                                        <span
                                            class="box-border inline-flex min-h-[1.125rem] min-w-[1.25rem] items-center justify-center rounded-sm px-0.5 py-px font-[700]"
                                            @if ($modelDraftListRankBoardHeatStyle !== null)
                                                style="{{ $modelDraftListRankBoardHeatStyle }}"
                                            @endif
                                        >{{ ($player->modelDraftListRank()) ?? '-' }}</span>
                                    </span>
                                    <span class="text-gray-300" aria-hidden="true">·</span>
                                    <span
                                        class="rounded-md border border-dashed border-gray-300 bg-gray-50/90 px-2 py-0.5 tabular-nums text-gray-800"
                                        title="{{ __('Personal rank') }}"
                                    >
                                        CG {{ $player->personal_rank ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        @endif
                </div>
            </div>
        @else
            {{-- Left: master note --}}
            <div class="relative z-0 flex min-h-0 min-w-0 flex-col overflow-hidden">
                <aside
                    @class([
                        'profile-master-take-aside flex w-full min-w-0 max-w-full flex-1 items-center justify-center self-stretch bg-[#f2f6f9] app-outline-soft',
                        'px-1 py-px sm:px-1 sm:py-0.5 md:px-1.5 md:py-1' => ! $compactProfile && ! $comfortable,
                        'px-1.5 py-0.5 sm:px-2 sm:py-1 md:px-2.5 md:py-1' => ! $compactProfile && $comfortable,
                        'px-px py-0' => $compactProfile,
                        'min-h-[2.7rem] sm:min-h-[3.15rem] md:min-h-0' => ! $compactProfile,
                        'min-h-0 max-h-full py-0' => $compactProfile,
                    ])
                >
                    <p
                        class="profile-master-take-text max-w-full break-words text-center font-sans font-[700] text-black"
                    >
                        {{ filled($player->master_take) ? $player->master_take : '-' }}
                    </p>
                </aside>
            </div>

            {{-- Middle: same height as master take; summary lives inside the white panel under the select --}}
            <div class="relative z-0 flex h-full min-h-0 min-w-0 flex-col overflow-visible">
            <div
                @class([
                    'flex w-full min-w-0 max-w-full flex-1 flex-col overflow-hidden rounded-md border-red-600 bg-red-50/90',
                    'border-2 p-px sm:border-[3px] sm:p-0.5' => ! $compactProfile,
                    'border p-px' => $compactProfile,
                    'min-h-[2.7rem] sm:min-h-[3.15rem] md:min-h-0' => ! $compactProfile,
                    'min-h-0 max-h-full' => $compactProfile,
                ])
            >
                <div
                    @class([
                        'flex min-h-0 max-h-full flex-1 flex-col bg-white app-outline-soft',
                        'px-0.5 py-0.5 sm:px-1 sm:py-1 md:px-1.5 md:py-1.5' => ! $compactProfile,
                        'px-px py-0' => $compactProfile,
                        'overflow-hidden' => ! $compactProfile,
                        'overflow-visible' => $compactProfile,
                    ])
                >
                    <div
                        @class([
                            'flex min-h-0 flex-1 flex-col items-center justify-center',
                            'gap-1 sm:gap-1.5' => ! $compactProfile,
                            'gap-0 sm:gap-px' => $compactProfile,
                        ])
                    >
                        @if ($playerList->isNotEmpty())
                            <div
                                class="relative z-30 w-full min-w-0 shrink-0"
                                x-data="ncaaPlayerCombobox({
                                    players: {{ \Illuminate\Support\Js::from($comboboxPlayers) }},
                                    selectedId: {{ $comboboxSelectedIdJson }},
                                    selectedLabel: {{ $comboboxSelectedLabelJson }},
                                })"
                                @click.outside="close()"
                                @keydown.escape.window="open && close()"
                            >
                                <label class="sr-only" for="profile-player-combobox-trigger">{{ $comboboxAccessibleLabel }}</label>
                                <button
                                    id="profile-player-combobox-trigger"
                                    type="button"
                                    class="flex w-full min-w-0 max-w-full cursor-pointer items-center justify-center gap-0.5 rounded-sm border-0 bg-white bg-[right_0.3rem_center] bg-no-repeat py-0 pl-0.5 text-center font-sans leading-none tracking-wide shadow-none ring-0 sm:bg-[right_0.35rem_center] sm:pl-1 {{ $comboBtn }}"
                                    style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke-width=%222%22 stroke=%22%230c2340%22%3E%3Cpath stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19.5 8.25l-7.5 7.5-7.5-7.5%22/%3E%3C/svg%3E')"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-haspopup="listbox"
                                >
                                    <span class="rangers-wordmark-text min-w-0 truncate" x-text="selectedLabel"></span>
                                </button>
                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition
                                    @click.stop
                                    class="absolute left-0 right-0 top-full z-50 mt-0.5 bg-white py-1 shadow-lg ring-1 ring-black/5 app-outline-soft"
                                >
                                    <div class="border-b border-gray-100 px-1.5 pb-1.5 pt-0.5">
                                        <label class="sr-only" for="profile-player-combobox-filter">{{ __('Filter players') }}</label>
                                        <input
                                            id="profile-player-combobox-filter"
                                            x-ref="filterInput"
                                            x-model="query"
                                            type="text"
                                            autocomplete="off"
                                            class="w-full rounded-md border border-[rgb(203_213_225)] px-1.5 py-0.5 font-sans font-normal normal-case text-gray-900 placeholder:text-gray-400 focus:border-red-400 focus:outline-none focus:ring-1 focus:ring-red-400/40 {{ $comboInput }}"
                                            placeholder="{{ __('Type to filter…') }}"
                                        />
                                    </div>
                                    <ul
                                        role="listbox"
                                        class="max-h-[min(55vh,12rem)] overflow-y-auto overscroll-contain font-sans font-normal leading-tight text-gray-900 sm:max-h-[min(50vh,11rem)] {{ $comboList }}"
                                    >
                                        <template x-for="p in filtered" :key="p.id">
                                            <li role="option" :aria-selected="p.id === selectedId">
                                                <button
                                                    type="button"
                                                    class="rangers-wordmark-text flex w-full px-2 py-0.5 text-left hover:bg-red-50 focus:bg-red-50 focus:outline-none"
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
                                        class="px-2 py-1.5 font-sans font-normal text-gray-500 {{ $comboList }}"
                                    >
                                        {{ __('No matching players.') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <h1
                                class="rangers-wordmark-text flex min-w-0 max-w-full shrink-0 items-center justify-center text-center font-sans leading-tight tracking-wide {{ $fallbackName }}"
                            >
                                {{ $comboboxSelectedLabel }}
                            </h1>
                        @endif
                        @if (filled($listSummary))
                            <p
                                class="max-w-full shrink-0 break-words px-0.5 text-center font-sans font-[700] leading-snug text-gray-700 {{ $summaryText }}"
                            >
                                {{ $listSummary }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Right: grades | radar | logo (contained to column 3) --}}
        <div
            @class([
                'relative z-0 flex min-h-0 min-w-0 flex-col overflow-hidden',
                'pl-0 pr-[calc(0.375rem+1px)] sm:pr-[calc(0.5rem+1px)]' => $omitCenterColumn && ! $compactProfile,
            ])
        >
            <div
                @class([
                    'w-full min-w-0 max-w-full flex-1',
                    'grid grid-cols-[auto_minmax(0,1fr)_auto] items-stretch gap-2 md:gap-3 2xl:gap-4' => $hsGradesRadarRowGrid,
                    'flex flex-col items-center justify-center gap-0.5 sm:flex-row sm:items-stretch md:gap-2 2xl:gap-3' => ! $hsGradesRadarRowGrid,
                    'sm:justify-between' => ! $hsGradesRadarRowGrid && (! $omitCenterColumn || $compactProfile),
                    'sm:justify-start sm:gap-2 md:gap-3 2xl:gap-4' => ! $hsGradesRadarRowGrid && $omitCenterColumn && ! $compactProfile,
                    'min-h-[2.7rem] sm:min-h-[3.15rem] sm:gap-1.5 md:min-h-0' => ! $hsGradesRadarRowGrid && ! $compactProfile,
                    'min-h-0 max-h-full gap-0 sm:gap-0.5' => $compactProfile,
                ])
            >
                <div
                    @class([
                        'flex min-h-0 min-w-0 shrink-0 items-stretch',
                        'h-full w-full sm:flex-1 sm:min-w-0' => ! $hsGradesRadarRowGrid,
                        'h-full w-fit max-w-full' => $hsGradesRadarRowGrid,
                        'justify-center' => ! $omitCenterColumn || $compactProfile,
                        'justify-start' => $omitCenterColumn && ! $compactProfile,
                    ])
                >
                    <div class="flex h-full min-h-0 min-w-0 max-w-full items-stretch">
                        {{-- CSS Grid (not <table>): browsers don't distribute <tr> heights reliably; 7× minmax(0,1fr) rows are equal. --}}
                        <div
                            role="table"
                            aria-label="{{ __('Grade summary') }}"
                            class="profile-grades-grid grid h-full min-h-0 grid-cols-[11fr_14fr] gap-px p-px font-sans font-[700] leading-none {{ $gradeGridClass }}"
                            style="grid-template-rows: repeat({{ $gradeRowCount }}, minmax(0, 1fr))"
                        >
                            @foreach ($gradeDefsForGrid as $label => $attribute)
                                <div role="row" class="contents">
                                    <div
                                        role="rowheader"
                                        class="flex min-h-0 min-w-0 items-center justify-center overflow-hidden bg-[#44546A] px-px py-0 text-center font-[700] text-white"
                                    >
                                        {{ $label }}
                                    </div>
                                    <div
                                        role="cell"
                                        class="flex min-h-0 min-w-0 items-center justify-center overflow-hidden px-px py-0 text-center font-[700] tabular-nums"
                                        style="{{ $player->gradeCellSummaryStyle($attribute) }}"
                                    >
                                        {{ $player->gradeCellDisplay($attribute) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div
                    @class([
                        'flex min-h-0 min-w-0 shrink-0 justify-center',
                        'items-center' => $compactProfile,
                        'h-full max-h-full items-stretch' => $fillHeaderRadar,
                    ])
                >
                    <x-player.radar-chart
                        :compact="$compactProfile"
                        :comfortable="$comfortable && ! $compactProfile && ! $fillHeaderRadar"
                        :fill-height="$fillHeaderRadar"
                        :show-legend="false"
                        :radar="$rangerSheet['radar'] ?? null"
                        :player="$player"
                        class="min-w-0 shrink-0"
                    />
                </div>

                <div class="mr-1 flex h-full shrink-0 items-center justify-center self-stretch py-px sm:mr-1.5 sm:py-0.5 sm:pl-px md:mr-2 md:pl-0.5">
                    <img
                        src="{{ asset('images/mlb-draft-logo.png') }}"
                        alt="{{ __('MLB DRAFT') }}"
                        class="h-auto w-auto object-contain object-right {{ $logoClass }}"
                        width="160"
                        height="192"
                    />
                </div>
            </div>
        </div>
    </div>
</div>
