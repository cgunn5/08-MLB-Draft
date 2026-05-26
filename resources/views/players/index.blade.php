<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('PLAYER LIST') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="border-t border-gray-100 pt-4">
                        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3">{{ __('ADD PLAYER') }}</h3>
                        <form method="POST" action="{{ route('players.store') }}" class="space-y-3">
                            @csrf

                            <div class="grid grid-cols-1 gap-x-3 gap-y-3 sm:grid-cols-12 sm:items-end">
                                <div class="min-w-0 sm:col-span-5">
                                    <x-input-label for="last_name" :value="__('LAST NAME')" class="!text-xs" />
                                    <x-text-input id="last_name" class="block mt-1 w-full text-sm" type="text" name="last_name" :value="old('last_name')" required />
                                    <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                                </div>
                                <div class="min-w-0 sm:col-span-5">
                                    <x-input-label for="first_name" :value="__('FIRST NAME')" class="!text-xs" />
                                    <x-text-input id="first_name" class="block mt-1 w-full text-sm" type="text" name="first_name" :value="old('first_name')" required />
                                    <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                                </div>
                                <div class="sm:col-span-2 sm:max-w-[8.5rem]">
                                    <x-input-label for="player_pool" :value="__('POOL')" class="!text-xs" />
                                    <select id="player_pool" name="player_pool" class="mt-1 block w-full border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="ncaa" @selected(old('player_pool', 'ncaa') === 'ncaa')>{{ __('NCAA') }}</option>
                                        <option value="hs" @selected(old('player_pool') === 'hs')>{{ __('HS') }}</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('player_pool')" class="mt-1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-x-3 gap-y-3 sm:grid-cols-12 sm:items-end">
                                <div class="min-w-0 sm:col-span-5">
                                    <x-input-label for="school" :value="__('SCHOOL')" class="!text-xs" />
                                    <x-text-input id="school" class="block mt-1 w-full text-sm" type="text" name="school" :value="old('school')" />
                                    <x-input-error :messages="$errors->get('school')" class="mt-1" />
                                </div>
                                <div class="sm:col-span-2 sm:max-w-[5.5rem]">
                                    <x-input-label for="position" :value="__('POS')" class="!text-xs" />
                                    <x-text-input id="position" class="block mt-1 w-full text-sm" type="text" name="position" :value="old('position')" maxlength="32" />
                                    <x-input-error :messages="$errors->get('position')" class="mt-1" />
                                </div>
                                <div class="sm:col-span-2 sm:max-w-[6.5rem]">
                                    <x-input-label for="aggregate_rank" :value="__('AGG RK')" class="!text-xs" />
                                    <x-text-input id="aggregate_rank" class="block mt-1 w-full text-sm tabular-nums" type="number" name="aggregate_rank" :value="old('aggregate_rank')" min="1" />
                                    <x-input-error :messages="$errors->get('aggregate_rank')" class="mt-1" />
                                </div>
                                <div class="sm:col-span-3 sm:max-w-[8rem]">
                                    <x-input-label for="aggregate_score" :value="__('AGG')" class="!text-xs" />
                                    <x-text-input id="aggregate_score" class="block mt-1 w-full text-sm tabular-nums" type="text" inputmode="decimal" name="aggregate_score" :value="old('aggregate_score')" />
                                    <x-input-error :messages="$errors->get('aggregate_score')" class="mt-1" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-2 sm:grid-cols-6 sm:items-end">
                                <div class="min-w-0">
                                    <x-input-label for="source_mdl" :value="__('MDL')" class="!text-xs" />
                                    <x-text-input id="source_mdl" class="block mt-1 w-full text-sm tabular-nums" type="number" name="source_mdl" :value="old('source_mdl')" min="1" />
                                    <x-input-error :messages="$errors->get('source_mdl')" class="mt-1" />
                                </div>
                                <div class="min-w-0">
                                    <x-input-label for="source_mlb" :value="__('MLB')" class="!text-xs" />
                                    <x-text-input id="source_mlb" class="block mt-1 w-full text-sm tabular-nums" type="number" name="source_mlb" :value="old('source_mlb')" min="1" />
                                    <x-input-error :messages="$errors->get('source_mlb')" class="mt-1" />
                                </div>
                                <div class="min-w-0">
                                    <x-input-label for="source_espn" :value="__('ESPN')" class="!text-xs" />
                                    <x-text-input id="source_espn" class="block mt-1 w-full text-sm tabular-nums" type="number" name="source_espn" :value="old('source_espn')" min="1" />
                                    <x-input-error :messages="$errors->get('source_espn')" class="mt-1" />
                                </div>
                                <div class="min-w-0">
                                    <x-input-label for="source_law" :value="__('LAW')" class="!text-xs" />
                                    <x-text-input id="source_law" class="block mt-1 w-full text-sm tabular-nums" type="number" name="source_law" :value="old('source_law')" min="1" />
                                    <x-input-error :messages="$errors->get('source_law')" class="mt-1" />
                                </div>
                                <div class="min-w-0">
                                    <x-input-label for="source_fb" :value="__('FB')" class="!text-xs" />
                                    <x-text-input id="source_fb" class="block mt-1 w-full text-sm tabular-nums" type="number" name="source_fb" :value="old('source_fb')" min="1" />
                                    <x-input-error :messages="$errors->get('source_fb')" class="mt-1" />
                                </div>
                                <div class="min-w-0">
                                    <x-input-label for="source_ba" :value="__('BA')" class="!text-xs" />
                                    <x-text-input id="source_ba" class="block mt-1 w-full text-sm tabular-nums" type="number" name="source_ba" :value="old('source_ba')" min="1" />
                                    <x-input-error :messages="$errors->get('source_ba')" class="mt-1" />
                                </div>
                            </div>

                            <div>
                                <x-primary-button>{{ __('ADD TO LIST') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($players->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('NO PLAYERS ON THE LIST.') }}</p>
                    @else
                        <div
                            x-data="playerListTable({ rows: @js($tableRows), deleteConfirm: @js(__('Remove this player from the list?')), playersPatchBase: @js(rtrim(url('/players'), '/')) })"
                            class="space-y-3"
                        >
                            <div class="flex justify-end">
                                <x-text-input
                                    id="player_list_filter"
                                    class="block w-full max-w-xs sm:max-w-[14rem]"
                                    type="search"
                                    x-model.debounce.200ms="filterQuery"
                                    autocomplete="off"
                                    placeholder="{{ __('Name, school, pool, or position') }}"
                                    aria-label="{{ __('Filter by name, school, pool, or position') }}"
                                />
                            </div>

                            <template x-if="displayRows.length === 0">
                                <p class="text-sm text-gray-600">{{ __('NO ROWS MATCH THIS FILTER.') }}</p>
                            </template>

                            <div x-show="displayRows.length > 0" class="overflow-x-auto -mx-2 sm:mx-0">
                                <div
                                    class="inline-grid w-max min-w-full grid-cols-[minmax(1.75rem,2.35rem)_max-content_auto_max-content_auto_minmax(1.75rem,2.35rem)_minmax(1.75rem,2.35rem)_minmax(1.75rem,2.35rem)_minmax(1.75rem,2.35rem)_minmax(1.75rem,2.35rem)_minmax(1.75rem,2.35rem)_minmax(1.75rem,2.35rem)_minmax(2.25rem,3rem)_auto_auto] gap-y-1 pb-0.5"
                                >
                                    <div
                                        role="row"
                                        class="player-list-subgrid-row gap-x-1 items-center rounded-lg border border-[#364056] bg-[#44546A] px-2.5 py-2 text-center text-xs font-[700] uppercase tracking-wide text-white shadow-sm"
                                    >
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('rk')" :aria-sort="sortKey === 'rk' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('rk')">{{ __('RK') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('player')" :aria-sort="sortKey === 'player' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('player')">{{ __('PLAYER') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('pool')" :aria-sort="sortKey === 'pool' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('pool')">{{ __('POOL') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('school')" :aria-sort="sortKey === 'school' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('school')">{{ __('SCHOOL') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('pos')" :aria-sort="sortKey === 'pos' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('pos')">{{ __('POS') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('agg')" :aria-sort="sortKey === 'agg' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('agg')">{{ __('AGG') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('mdl')" :aria-sort="sortKey === 'mdl' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('mdl')">{{ __('MDL') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('mlb')" :aria-sort="sortKey === 'mlb' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('mlb')">{{ __('MLB') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('espn')" :aria-sort="sortKey === 'espn' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('espn')">{{ __('ESPN') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('law')" :aria-sort="sortKey === 'law' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('law')">{{ __('LAW') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('fb')" :aria-sort="sortKey === 'fb' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('fb')">{{ __('FB') }}</button>
                                        </div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('ba')" :aria-sort="sortKey === 'ba' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('ba')">{{ __('BA') }}</button>
                                        </div>
                                        <div class="min-w-0 text-white" role="columnheader">{{ __('EDIT') }}</div>
                                        <div class="min-w-0 rounded-sm px-0.5 py-0.5 transition-colors" role="columnheader" :class="sortHighlightHeader('profile')" :aria-sort="sortKey === 'profile' ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'">
                                            <button type="button" class="w-full text-white hover:text-gray-200" @click="sortBy('profile')">{{ __('PROFILE') }}</button>
                                        </div>
                                        <div class="min-w-0 text-white" role="columnheader">{{ __('DELETE') }}</div>
                                    </div>

                                    <template x-for="row in displayRows" :key="row.id">
                                        <article
                                            class="player-list-subgrid-row gap-x-1 items-stretch rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-center text-xs font-[700] text-gray-800 shadow-sm transition hover:border-[#44546A]/35 hover:shadow-md"
                                            x-bind:aria-label="row.name"
                                        >
                                            <template x-if="editingId !== row.id">
                                                <div class="contents">
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('rk')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums whitespace-nowrap px-0.5 py-px"
                                                            :style="cellHeatStyle('rk', row.aggregate_rank)"
                                                            x-text="formatRank(row.aggregate_rank)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex items-center justify-center px-1 transition-colors" :class="sortHighlightBody('player')">
                                                        <span class="whitespace-nowrap text-center text-gray-900" x-text="row.name"></span>
                                                    </div>
                                                    <div class="flex items-center justify-center whitespace-nowrap px-1 uppercase text-gray-700 transition-colors" :class="sortHighlightBody('pool')" x-text="row.player_pool"></div>
                                                    <div class="flex items-center justify-center px-1 transition-colors" :class="sortHighlightBody('school')">
                                                        <span class="whitespace-nowrap text-center" x-text="row.school ?? '—'"></span>
                                                    </div>
                                                    <div class="flex items-center justify-center whitespace-nowrap px-1 transition-colors" :class="sortHighlightBody('pos')" x-text="row.position ?? '—'"></div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('agg')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums whitespace-nowrap px-0.5 py-px"
                                                            :style="cellHeatStyle('agg', row.aggregate_score)"
                                                            x-text="formatAgg(row.aggregate_score)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('mdl')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums px-0.5 py-px"
                                                            :style="cellHeatStyle('mdl', row.mdl)"
                                                            x-text="formatRank(row.mdl)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('mlb')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums px-0.5 py-px"
                                                            :style="cellHeatStyle('mlb', row.mlb)"
                                                            x-text="formatRank(row.mlb)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('espn')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums px-0.5 py-px"
                                                            :style="cellHeatStyle('espn', row.espn)"
                                                            x-text="formatRank(row.espn)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('law')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums px-0.5 py-px"
                                                            :style="cellHeatStyle('law', row.law)"
                                                            x-text="formatRank(row.law)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('fb')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums px-0.5 py-px"
                                                            :style="cellHeatStyle('fb', row.fb)"
                                                            x-text="formatRank(row.fb)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex h-full min-h-0 min-w-0 items-center justify-center px-0.5 py-0.5 transition-colors" :class="sortHighlightBody('ba')">
                                                        <span
                                                            class="box-border flex h-[90%] min-h-[1.125rem] w-full max-w-full items-center justify-center rounded-sm tabular-nums px-0.5 py-px"
                                                            :style="cellHeatStyle('ba', row.ba)"
                                                            x-text="formatRank(row.ba)"
                                                        ></span>
                                                    </div>
                                                    <div class="flex min-w-0 items-center justify-center whitespace-nowrap transition-colors">
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center justify-center rounded p-1 text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                                            title="{{ __('Edit') }}"
                                                            aria-label="{{ __('Edit player') }}"
                                                            @click="startEdit(row)"
                                                        >
                                                            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                                                                <path d="M8 2l2 2M2 10l2.5-.5L10 3.5 8.5 2 2.5 9.5 2 10z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div class="flex min-w-0 items-center justify-center whitespace-nowrap transition-colors" :class="sortHighlightBody('profile')">
                                                        <a
                                                            x-show="row.profile_url"
                                                            x-cloak
                                                            :href="row.profile_url"
                                                            class="text-indigo-600 hover:text-indigo-800 font-[700]"
                                                        >{{ __('OPEN') }}</a>
                                                        <span x-show="!row.profile_url" class="text-gray-400">—</span>
                                                    </div>
                                                    <div class="flex min-w-0 items-center justify-center whitespace-nowrap transition-colors">
                                                        <form
                                                            method="post"
                                                            class="inline"
                                                            x-bind:action="'{{ route('players.index') }}/' + row.id"
                                                            @submit="confirmDelete($event)"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="inline-flex items-center justify-center rounded p-1 text-red-600 transition hover:bg-red-50 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500/40"
                                                                title="{{ __('Delete') }}"
                                                                aria-label="{{ __('Delete player') }}"
                                                            >
                                                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                                                                    <path d="M2.5 2.5l7 7M9.5 2.5l-7 7" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="editingId === row.id">
                                                <div
                                                    class="rounded-md border border-indigo-200 bg-indigo-50/50 px-3 py-2.5 text-left font-normal shadow-inner [grid-column:1/-1]"
                                                >
                                                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-700">{{ __('Edit name and source ranks') }}</p>
                                                    <div class="flex flex-wrap items-end gap-x-2 gap-y-2">
                                                        <div class="min-w-[6.5rem] flex-1">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600" for="edit_last_name">{{ __('LAST NAME') }}</label>
                                                            <input id="edit_last_name" type="text" x-model="editDraft.last_name" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autocomplete="off" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('last_name')"></p>
                                                        </div>
                                                        <div class="min-w-[6.5rem] flex-1">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600" for="edit_first_name">{{ __('FIRST NAME') }}</label>
                                                            <input id="edit_first_name" type="text" x-model="editDraft.first_name" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autocomplete="off" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('first_name')"></p>
                                                        </div>
                                                        <div class="w-[3.25rem] shrink-0">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('MDL') }}</label>
                                                            <input type="number" min="1" x-model="editDraft.mdl" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('source_mdl')"></p>
                                                        </div>
                                                        <div class="w-[3.25rem] shrink-0">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('MLB') }}</label>
                                                            <input type="number" min="1" x-model="editDraft.mlb" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('source_mlb')"></p>
                                                        </div>
                                                        <div class="w-[3.25rem] shrink-0">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('ESPN') }}</label>
                                                            <input type="number" min="1" x-model="editDraft.espn" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('source_espn')"></p>
                                                        </div>
                                                        <div class="w-[3.25rem] shrink-0">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('LAW') }}</label>
                                                            <input type="number" min="1" x-model="editDraft.law" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('source_law')"></p>
                                                        </div>
                                                        <div class="w-[3.25rem] shrink-0">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('FB') }}</label>
                                                            <input type="number" min="1" x-model="editDraft.fb" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('source_fb')"></p>
                                                        </div>
                                                        <div class="w-[3.25rem] shrink-0">
                                                            <label class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('BA') }}</label>
                                                            <input type="number" min="1" x-model="editDraft.ba" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                            <p class="mt-0.5 text-[10px] font-normal text-red-600" x-text="firstError('source_ba')"></p>
                                                        </div>
                                                    </div>
                                                    <p class="mt-2 text-[10px] font-normal text-gray-500">{{ __('Leave a rank blank to clear it. Save updates this row only.') }}</p>
                                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center rounded-md bg-indigo-600 px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 disabled:opacity-50"
                                                            @click="saveEdit(row.id)"
                                                            x-bind:disabled="saving"
                                                        >{{ __('Save') }}</button>
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400/40 disabled:opacity-50"
                                                            @click="cancelEdit()"
                                                            x-bind:disabled="saving"
                                                        >{{ __('Cancel') }}</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </article>
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
