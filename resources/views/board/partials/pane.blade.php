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
                            type="search"
                            role="searchbox"
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
                            <div
                                x-show="completeAvailableCount() > 0 || availableCount() > 0"
                                x-cloak
                                class="space-y-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5"
                            >
                                <button
                                    type="button"
                                    x-show="completeAvailableCount() > 0"
                                    x-cloak
                                    class="working-board-picker-add-all w-full rounded border border-emerald-600 bg-emerald-600 px-2 py-1 text-center text-[10px] font-bold normal-case text-white shadow-sm transition hover:bg-emerald-700"
                                    @click="addAllComplete()"
                                >
                                    {{ __('Add all complete profiles to round') }}
                                    <span x-text="$root.roundLabel(round)"></span>
                                    (<span x-text="completeAvailableCount()"></span>)
                                </button>
                                <button
                                    type="button"
                                    x-show="availableCount() > 0"
                                    x-cloak
                                    class="working-board-picker-add-all w-full rounded border border-indigo-600 bg-indigo-600 px-2 py-1 text-center text-[10px] font-bold normal-case text-white shadow-sm transition hover:bg-indigo-700"
                                    @click="addAllAvailable()"
                                >
                                    {{ __('Add all players to round') }}
                                    <span x-text="$root.roundLabel(round)"></span>
                                    (<span x-text="availableCount()"></span>)
                                </button>
                            </div>
                            <ul
                                role="listbox"
                                aria-multiselectable="true"
                                class="max-h-[min(50vh,14rem)] overflow-y-auto overscroll-y-contain normal-case [-webkit-overflow-scrolling:touch]"
                                style="overscroll-behavior: contain"
                            >
                                <template x-for="p in filtered" :key="'pick-{{ $boardType }}-' + p.player_id">
                                    <li role="option" :aria-selected="isPlayerSelected(p.player_id)">
                                        <label
                                            class="working-board-picker-option flex w-full cursor-pointer items-center gap-2 px-2 py-0.5 text-left hover:bg-indigo-50 focus-within:bg-indigo-50"
                                            :class="{
                                                'bg-indigo-100': isPlayerSelected(p.player_id),
                                                'working-board-picker-option--complete': p.profile_complete,
                                            }"
                                        >
                                            <input
                                                type="checkbox"
                                                class="working-board-picker-checkbox shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30"
                                                :checked="isPlayerSelected(p.player_id)"
                                                @change="togglePlayer(p)"
                                            />
                                            <span
                                                class="min-w-0 flex-1 truncate text-[11px] font-bold leading-tight"
                                                :class="p.profile_complete ? 'text-emerald-700' : 'text-slate-900'"
                                                x-text="p.label || '—'"
                                            ></span>
                                        </label>
                                    </li>
                                </template>
                            </ul>
                            <div
                                x-show="selectedCount() > 0"
                                x-cloak
                                class="flex items-center justify-between gap-2 border-t border-slate-200 bg-indigo-50/60 px-2 py-1"
                            >
                                <span class="text-[10px] font-semibold text-indigo-900" x-text="`${selectedCount()} selected`"></span>
                                <button
                                    type="button"
                                    class="text-[10px] font-semibold text-indigo-700 hover:text-indigo-900"
                                    @click="clearSelection()"
                                >
                                    {{ __('Clear selection') }}
                                </button>
                            </div>
                            <p
                                x-show="availableCount() === 0"
                                x-cloak
                                class="px-3 py-2 text-center text-[10px] normal-case text-slate-500"
                            >
                                {{ __('All players are already on this board.') }}
                            </p>
                            <p
                                x-show="availableCount() > 0 && filtered.length === 0"
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
                        :disabled="selectedCount() === 0"
                        @click="addSelected()"
                    >
                        {{ __('Add selected to round') }}
                        <span x-text="$root.roundLabel(round)"></span>
                        <span x-show="selectedCount() > 0" x-cloak>(<span x-text="selectedCount()"></span>)</span>
                    </button>
                    </div>

                    <div class="working-board-round-row flex flex-wrap items-center justify-center gap-1.5">
                        @foreach ($boardRoundKeys as $boardRoundKey)
                            <button
                                type="button"
                                class="working-board-round-btn rounded border px-2 font-bold shadow-sm transition"
                                :class="round === '{{ $boardRoundKey }}'
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : 'border-slate-300 bg-white text-slate-800 hover:border-indigo-400 hover:bg-indigo-50'"
                                @click="round = '{{ $boardRoundKey }}'"
                            >{{ $boardRoundLabels[$boardRoundKey] ?? $boardRoundKey }}</button>
                        @endforeach
                    </div>

                    <p
                        x-show="selectedCount() > 0"
                        x-cloak
                        class="text-center text-[10px] font-semibold text-indigo-800"
                    >
                        <span x-text="`${selectedCount()} players selected`"></span>
                    </p>
                </div>
            @endif
        </section>
    @endunless

    <div class="working-board-columns-viewport min-h-0 flex-1">
        <div class="working-board-columns-scroll bg-slate-100">
        @foreach (WorkingBoardEntry::ROUND_ROW_GROUPS as $roundRowKeys)
            <div class="working-board-columns-row">
                @foreach ($roundRowKeys as $boardRoundKey)
                    @include('board.partials.round-column', [
                        'boardType' => $boardType,
                        'boardRoundKey' => $boardRoundKey,
                    ])
                @endforeach
            </div>
        @endforeach
        </div>
    </div>
</div>
