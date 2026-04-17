@props([
    'notesComboboxPlayers' => [],
    'selectedPlayer' => null,
])

@php
    $selectedIdJson = \Illuminate\Support\Js::from(optional($selectedPlayer)->getKey());
    $selectedLabelJson = \Illuminate\Support\Js::from(
        $selectedPlayer
            ? strtoupper($selectedPlayer->last_name).', '.strtoupper($selectedPlayer->first_name)
            : '',
    );
@endphp

@if ($notesComboboxPlayers !== [])
    <div
        class="relative z-40 w-full"
        x-data="notesPlayerCombobox({
            players: {{ \Illuminate\Support\Js::from($notesComboboxPlayers) }},
            selectedId: {{ $selectedIdJson }},
            selectedLabel: {{ $selectedLabelJson }},
            placeholderSelect: {{ \Illuminate\Support\Js::from(__('Select player…')) }},
            placeholderFilter: {{ \Illuminate\Support\Js::from(__('Type to filter…')) }},
        })"
        @click.outside="close()"
        @keydown.escape.window="open && close()"
    >
        <label class="sr-only" for="notes-player-combobox-input">{{ __('Player') }}</label>
        {{-- Grid overlay avoids transform on an ancestor; transform breaks position:fixed containing block and can clip the list. --}}
        <div class="relative w-full min-w-0 max-w-full">
            <div class="grid w-full grid-cols-1">
                <input
                    id="notes-player-combobox-input"
                    x-ref="comboboxInput"
                    type="text"
                    role="combobox"
                    autocomplete="off"
                    :aria-expanded="open"
                    aria-haspopup="listbox"
                    aria-controls="notes-player-combobox-listbox"
                    class="col-start-1 row-start-1 w-full min-h-[2.75rem] min-w-0 max-w-full rounded-md border border-[rgb(203_213_225)] bg-white py-2 pl-3 pr-10 text-left text-sm font-medium normal-case text-gray-900 shadow-sm placeholder:text-gray-400 hover:border-slate-400/90 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    :class="open ? 'cursor-text' : 'cursor-pointer'"
                    :value="open ? query : (selectedId != null ? selectedLabel : '')"
                    :placeholder="open ? placeholderFilter : (selectedId != null ? '' : placeholderSelect)"
                    @focus="onComboboxFocus()"
                    @input="onComboboxInput($event)"
                />
                <span
                    class="pointer-events-none col-start-1 row-start-1 justify-self-end self-center pr-3 text-gray-500"
                    aria-hidden="true"
                >
                    <svg
                        class="h-5 w-5 transition-transform duration-200 ease-out"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        :class="open ? 'rotate-180' : ''"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
            </div>
        </div>
        {{-- Same pattern as profile combobox: absolute under trigger + explicit max-h on list (no flex-1 / inherit collapse). --}}
        <div
            x-cloak
            x-show="open"
            x-transition
            @click.stop
            id="notes-player-combobox-listbox"
            class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden bg-white py-1 shadow-xl ring-1 ring-black/5 app-outline-soft"
        >
            <ul
                role="listbox"
                class="max-h-[min(55vh,18rem)] touch-pan-y overflow-y-auto overflow-x-hidden overscroll-y-contain text-sm normal-case text-gray-900 [-webkit-overflow-scrolling:touch]"
                style="overscroll-behavior: contain"
            >
                <template x-for="p in filtered" :key="p.id">
                    <li role="option" :aria-selected="p.id === selectedId">
                        <button
                            type="button"
                            class="flex w-full px-3 py-1.5 text-left hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                            :class="p.id === selectedId ? 'bg-indigo-50 font-semibold text-indigo-900' : 'font-normal'"
                            @click="choose(p)"
                            x-text="p.label"
                        ></button>
                    </li>
                </template>
            </ul>
            <p
                x-show="filtered.length === 0"
                x-cloak
                class="px-3 py-2 text-sm text-gray-500"
            >
                {{ __('No matching players.') }}
            </p>
        </div>
    </div>
@endif
