@php
    use App\Models\WorkingBoardEntry;

    /** @var string $boardType */
    /** @var array{title: string, poolHint: string, emptyPoolHint: string, playerPool: list<array<string, mixed>>} $panel */
    $poolPlayers = $panel['playerPool'] ?? [];
    $hasPoolPlayers = count($poolPlayers) > 0;
    $boardHeaderImages = [
        WorkingBoardEntry::BOARD_HS => 'images/board-hs-header.png',
        WorkingBoardEntry::BOARD_MASTER => 'images/board-master-header.png',
        WorkingBoardEntry::BOARD_NCAA => 'images/board-ncaa-header.png',
    ];
    $boardHeaderImage = $boardHeaderImages[$boardType] ?? null;
@endphp
<div
    @class([
        'working-board-pane flex min-h-0 w-full min-w-[18rem] flex-1 flex-col overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm',
        'working-board-pane--' . $boardType => true,
    ])
    data-board-type="{{ $boardType }}"
>
    @if ($boardHeaderImage)
        <div class="working-board-pane-header working-board-pane-header--logo flex shrink-0 justify-center bg-transparent">
            <img
                src="{{ asset($boardHeaderImage) }}"
                alt="{{ $panel['title'] }}"
                class="working-board-pane-header-logo"
                decoding="async"
            />
        </div>
    @endif

    @unless ($boardReadOnly)
        <section
            class="working-board-picker-section relative z-50 shrink-0 bg-white px-3 normal-case shadow-sm"
            aria-label="{{ __('Add player') }} — {{ $panel['title'] }}"
        >
            @if (! $hasPoolPlayers)
                <p class="mt-1.5 text-center text-[10px] font-medium text-slate-500">
                    {{ $panel['emptyPoolHint'] }}
                </p>
            @else
                <div
                    class="space-y-2"
                    x-data="boardPlayerPicker({
                        boardType: @js($boardType),
                        players: @js($poolPlayers),
                        roundKeys: @js($boardRoundKeys),
                        readOnly: @json($boardReadOnly),
                    })"
                    @keydown.escape.window="open && close()"
                >
                    <div class="working-board-picker-row">
                    <div
                        class="working-board-picker-wrap relative z-50 min-w-0"
                        @click.outside="close()"
                    >
                        <label class="sr-only" for="board-picker-input-{{ $boardType }}">{{ __('Search players') }}</label>
                        <input
                            id="board-picker-input-{{ $boardType }}"
                            type="text"
                            role="combobox"
                            autocomplete="off"
                            spellcheck="false"
                            :aria-expanded="open"
                            aria-haspopup="listbox"
                            aria-controls="board-picker-listbox-{{ $boardType }}"
                            class="working-board-picker-input w-full rounded-md border-2 border-slate-300 bg-white pl-3 pr-7 text-left font-medium normal-case text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            placeholder="{{ __('Type player name…') }}"
                            :value="query"
                            @focus="onFocus()"
                            @input="onInput($event)"
                        />
                        <button
                            type="button"
                            class="working-board-picker-clear absolute inset-y-0 right-0 flex items-center justify-center text-slate-500 hover:text-slate-800"
                            tabindex="-1"
                            title="{{ __('Clear') }}"
                            @click="clear()"
                        >
                            <span class="text-lg leading-none" aria-hidden="true">×</span>
                        </button>

                        <div
                            x-cloak
                            x-show="open"
                            @click.stop
                            id="board-picker-listbox-{{ $boardType }}"
                            class="working-board-picker-list absolute left-0 right-0 top-full z-[200] mt-1 overflow-hidden rounded-md border border-slate-200 bg-white py-1 shadow-xl ring-1 ring-black/10"
                        >
                            <ul
                                role="listbox"
                                class="max-h-[min(50vh,14rem)] overflow-y-auto overscroll-y-contain normal-case [-webkit-overflow-scrolling:touch]"
                                style="overscroll-behavior: contain"
                            >
                                <template x-for="p in filtered" :key="'pick-{{ $boardType }}-' + p.player_id">
                                    <li role="option" :aria-selected="Number(p.player_id) === Number(selectedPlayerId)">
                                        <button
                                            type="button"
                                            class="flex w-full flex-col items-center gap-0.5 px-3 py-2 text-center hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"
                                            :class="Number(p.player_id) === Number(selectedPlayerId) ? 'bg-indigo-100' : ''"
                                            @click="choose(p)"
                                        >
                                            <span class="text-[11px] font-bold text-slate-900" x-text="p.label || '—'"></span>
                                            <span class="text-[10px] font-medium text-slate-600" x-text="pickerSubtitle(p)"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <p
                                x-show="filtered.length === 0"
                                x-cloak
                                class="px-3 py-2 text-center text-[10px] normal-case text-slate-500"
                            >
                                {{ __('No matching players.') }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="working-board-add-btn rounded-md border border-indigo-600 bg-indigo-600 text-center font-bold normal-case text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:border-slate-300 disabled:bg-slate-200 disabled:text-slate-500"
                        :disabled="!selectedPlayerId"
                        @click="addSelected()"
                    >
                        {{ __('Add selected player to round') }}
                        <span x-text="$root.roundLabel(round)"></span>
                    </button>
                    </div>

                    <div class="working-board-round-row flex flex-wrap items-center justify-center gap-1.5">
                        @foreach ($boardRoundKeys as $boardRoundKey)
                            <button
                                type="button"
                                class="working-board-round-btn min-w-[2.5rem] rounded border px-2 font-bold shadow-sm transition"
                                :class="round === '{{ $boardRoundKey }}'
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : 'border-slate-300 bg-white text-slate-800 hover:border-indigo-400 hover:bg-indigo-50'"
                                @click="round = '{{ $boardRoundKey }}'"
                            >{{ $boardRoundLabels[$boardRoundKey] ?? $boardRoundKey }}</button>
                        @endforeach
                    </div>

                    <p
                        x-show="selectedPlayerId"
                        x-cloak
                        class="text-center text-[10px] font-semibold text-indigo-800"
                    >
                        {{ __('Selected:') }}
                        <span x-text="selectedLabel"></span>
                    </p>
                </div>
            @endif
        </section>
    @endunless

    <div class="working-board-table-scroll min-h-0 flex-1 overflow-x-auto overflow-y-auto bg-slate-100">
        <table class="working-board-table w-full min-w-0 border-collapse text-center text-[10px]">
            <thead>
                <tr class="border-b border-[#3d4f68] text-[9px] font-bold tracking-wide text-white">
                    <th class="working-board-th working-board-col-scale px-0.5 py-1.5">{{ __('CONF') }}</th>
                    <th class="working-board-th min-w-[7rem] px-1 py-1.5">{{ __('NAME') }}</th>
                    <th class="working-board-th w-10 px-1 py-1.5">{{ __('POS') }}</th>
                    <th class="working-board-th w-11 px-1 py-1.5">{{ __('ROLE') }}</th>
                    <th class="working-board-th w-11 px-1 py-1.5">{{ __('BAT') }}</th>
                    <th class="working-board-th w-11 px-1 py-1.5">{{ __('SWING') }}</th>
                    <th class="working-board-th working-board-col-scale px-0.5 py-1.5">{{ __('RISK') }}</th>
                    <th class="working-board-th w-8 px-0.5 py-1.5"></th>
                </tr>
            </thead>
            @foreach ($boardRoundKeys as $boardRoundKey)
                <tbody
                    class="working-board-round-body border-b border-slate-300 bg-white"
                    data-round="{{ $boardRoundKey }}"
                    data-board-type="{{ $boardType }}"
                    @dragover.prevent
                    @drop.prevent="onRoundDrop($event, '{{ $boardType }}', '{{ $boardRoundKey }}')"
                >
                    <tr class="working-board-round-divider">
                        <td colspan="8" class="px-1.5 py-1.5">
                            <div class="flex items-center gap-2">
                                <span class="h-px flex-1 border-t border-dashed border-slate-600"></span>
                                <span class="working-board-round-label shrink-0 text-center text-[10px] font-bold tracking-widest text-black">
                                    {{ $boardRoundLabels[$boardRoundKey] ?? $boardRoundKey }}
                                </span>
                                <span class="h-px flex-1 border-t border-dashed border-slate-600"></span>
                            </div>
                        </td>
                    </tr>
                    <template x-for="(card, idx) in roundCards('{{ $boardType }}', '{{ $boardRoundKey }}')" :key="'p-{{ $boardType }}-{{ $boardRoundKey }}-' + card.player_id + '-' + idx">
                        <tr
                            class="working-board-card-row border-b border-slate-200 bg-white text-center font-bold text-slate-900 hover:bg-slate-50/80"
                            :class="readOnly ? '' : 'cursor-grab active:cursor-grabbing'"
                            :draggable="!readOnly"
                            data-player-row
                            @dragstart="onDragStart($event, '{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                        >
                            <td
                                class="working-board-scale-cell working-board-col-scale border-r border-slate-200 p-0 align-middle normal-case"
                                :style="boardScaleFillStyle(card.confidence)"
                                @click="openScaleSelect($event)"
                            >
                                <label class="sr-only">{{ __('Confidence') }}</label>
                                <select
                                    class="working-board-scale-select h-full min-h-[1.75rem] w-full cursor-pointer border-0 bg-transparent text-transparent shadow-none focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500/50"
                                    x-model="card.confidence"
                                    :disabled="readOnly"
                                    @change="scheduleSave()"
                                >
                                    <template x-for="co in confidenceOptions" :key="'c-{{ $boardType }}-{{ $boardRoundKey }}-' + idx + '-' + String(co)">
                                        <option :value="co" x-text="scaleLabel(co)"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="working-board-name-cell border-r border-slate-200 bg-white px-1.5 py-0.5 align-middle leading-snug">
                                <a
                                    class="working-board-player-name text-black underline decoration-slate-400 decoration-1 underline-offset-2 hover:decoration-black"
                                    :href="playerUrl(card)"
                                    x-text="boardName(card)"
                                ></a>
                            </td>
                            <td
                                class="border-r border-slate-200 bg-white px-1.5 py-0.5 align-middle font-bold text-slate-800"
                                x-text="card.position || '—'"
                            ></td>
                            <td
                                class="working-board-grade-cell border-r border-slate-200 p-0 align-middle font-bold text-black"
                                :style="roleCellStyle(card)"
                                x-text="gradeFmt(card.grade_role)"
                            ></td>
                            <td
                                class="working-board-grade-cell border-r border-slate-200 p-0 align-middle font-bold text-black"
                                :style="batCellStyle(card)"
                                x-text="gradeFmt(batGrade(card))"
                            ></td>
                            <td
                                class="working-board-grade-cell border-r border-slate-200 p-0 align-middle font-bold text-black"
                                :style="swingCellStyle(card)"
                                x-text="gradeFmt(card.grade_swing)"
                            ></td>
                            <td
                                class="working-board-scale-cell working-board-risk-cell working-board-col-scale relative border-r border-slate-200 bg-white p-0 align-middle normal-case"
                                @click="openScaleSelect($event)"
                            >
                                <label class="sr-only">{{ __('Risk') }}</label>
                                <span
                                    class="working-board-risk-value pointer-events-none absolute inset-0 flex items-center justify-center text-[11px] font-bold leading-none"
                                    :style="boardScaleTextStyle(card.risk)"
                                    x-text="riskLabel(card.risk)"
                                    aria-hidden="true"
                                ></span>
                                <select
                                    class="working-board-scale-select relative z-[1] h-full min-h-[1.75rem] w-full cursor-pointer border-0 bg-transparent text-transparent shadow-none focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500/50"
                                    x-model="card.risk"
                                    :disabled="readOnly"
                                    @change="scheduleSave()"
                                >
                                    <template x-for="ro in riskOptions" :key="'r-{{ $boardType }}-{{ $boardRoundKey }}-' + idx + '-' + String(ro)">
                                        <option :value="ro" x-text="riskLabel(ro)"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="px-0.5 py-0.5 align-middle">
                                @unless ($boardReadOnly)
                                    <button
                                        type="button"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded border border-slate-300 bg-white text-slate-500 hover:border-red-300 hover:bg-red-50 hover:text-red-700"
                                        title="{{ __('Remove from board') }}"
                                        @click="removeFromRound('{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                                    >
                                        <span class="sr-only">{{ __('Remove') }}</span>
                                        <span aria-hidden="true" class="text-sm leading-none">×</span>
                                    </button>
                                @endunless
                            </td>
                        </tr>
                    </template>
                    <tr x-show="roundCards('{{ $boardType }}', '{{ $boardRoundKey }}').length === 0" x-cloak class="working-board-empty bg-slate-50/80">
                        <td colspan="8" class="px-2 py-3 text-center text-[10px] font-medium text-slate-500 normal-case">
                            {{ __('Use the search box above to add a player to this round.') }}
                        </td>
                    </tr>
                </tbody>
            @endforeach
        </table>
    </div>
</div>
