<x-app-layout>
    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-100">
                    {{ session('status') }}
                </div>
            @endif

            @unless ($playersReadOnly)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div>
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
                            </div>

                            <div>
                                <x-primary-button>{{ __('ADD TO LIST') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endunless

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($players->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('NO PLAYERS ON THE LIST.') }}</p>
                    @else
                        @php
                            $playerListJsConfig = [
                                'rows' => $tableRows,
                                'readOnly' => $playersReadOnly,
                                'deleteConfirm' => __('Remove this player from the list?'),
                                'playersPatchBase' => rtrim(url('/players'), '/'),
                                'gradeMin' => $gradeBounds['min'],
                                'gradeMax' => $gradeBounds['max'],
                                'boardScaleMin' => 1,
                                'boardScaleMax' => 5,
                            ];
                        @endphp
                        <script id="player-list-config" type="application/json">
                            {!! json_encode($playerListJsConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
                        </script>

                        <div x-data="playerListTable()" class="space-y-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-700 shadow-sm transition hover:bg-gray-50 normal-case"
                                        @click="advancedOpen = ! advancedOpen"
                                        x-bind:aria-expanded="advancedOpen"
                                    >
                                        {{ __('Filters & sort') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-600 shadow-sm transition hover:bg-gray-100"
                                        x-show="hasActiveThresholds()"
                                        x-cloak
                                        @click="clearAdvancedFilters()"
                                    >{{ __('Clear filters') }}</button>
                                </div>
                                <x-text-input
                                    id="player_list_filter"
                                    class="block w-full max-w-xs sm:max-w-[14rem]"
                                    type="search"
                                    x-model.debounce.200ms="filterQuery"
                                    autocomplete="off"
                                    placeholder="{{ __('Name, school, or pool') }}"
                                    aria-label="{{ __('Filter by name, school, or pool') }}"
                                />
                            </div>

                            <div
                                x-show="advancedOpen"
                                x-cloak
                                x-transition
                                class="rounded-lg border border-gray-200 bg-gray-50/80 p-3 sm:p-4"
                            >
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,14rem)] lg:items-end">
                                    <div>
                                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-700">{{ __('Minimum grade thresholds') }}</p>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5">
                                            <template x-for="key in thresholdKeys" :key="key">
                                                <div class="min-w-0">
                                                    <label class="block text-[10px] font-semibold uppercase text-gray-600" x-bind:for="'threshold-' + key">
                                                        <span x-text="thresholdLabels[key]"></span>
                                                        <span class="font-normal normal-case text-gray-500"> ≥</span>
                                                    </label>
                                                    <input
                                                        class="mt-0.5 block w-full rounded-md border-gray-300 text-xs tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        type="number"
                                                        x-bind:id="'threshold-' + key"
                                                        x-bind:min="thresholdBounds(key).min"
                                                        x-bind:max="thresholdBounds(key).max"
                                                        x-bind:step="thresholdBounds(key).step"
                                                        x-model="thresholdFilters[key]"
                                                        placeholder="—"
                                                    />
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">
                                        <div>
                                            <label class="block text-[10px] font-semibold uppercase text-gray-600" for="player_list_pool_filter">{{ __('Pool') }}</label>
                                            <select id="player_list_pool_filter" x-model="poolFilter" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="all">{{ __('All pools') }}</option>
                                                <option value="ncaa">{{ __('NCAA') }}</option>
                                                <option value="hs">{{ __('HS') }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold uppercase text-gray-600" for="player_list_sort_key">{{ __('Sort by') }}</label>
                                            <select id="player_list_sort_key" x-model="sortKey" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <template x-for="opt in sortOptions" :key="opt.key">
                                                    <option x-bind:value="opt.key" x-text="opt.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('Direction') }}</span>
                                            <div class="mt-0.5 flex gap-1">
                                                <button
                                                    type="button"
                                                    class="flex-1 rounded-md border px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
                                                    x-bind:class="sortDir === 'asc' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                                    @click="sortDir = 'asc'"
                                                >{{ __('Asc') }}</button>
                                                <button
                                                    type="button"
                                                    class="flex-1 rounded-md border px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
                                                    x-bind:class="sortDir === 'desc' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                                    @click="sortDir = 'desc'"
                                                >{{ __('Desc') }}</button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold uppercase text-gray-600" for="player_list_sort_key_2">{{ __('Then by') }}</label>
                                            <select id="player_list_sort_key_2" x-model="sortKey2" class="mt-0.5 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <option value="">{{ __('None') }}</option>
                                                <template x-for="opt in sortOptions" :key="'then-' + opt.key">
                                                    <option x-bind:value="opt.key" x-text="opt.label" x-bind:disabled="opt.key === sortKey"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div x-show="sortKey2" x-cloak>
                                            <span class="block text-[10px] font-semibold uppercase text-gray-600">{{ __('Then direction') }}</span>
                                            <div class="mt-0.5 flex gap-1">
                                                <button
                                                    type="button"
                                                    class="flex-1 rounded-md border px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
                                                    x-bind:class="sortDir2 === 'asc' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                                    @click="sortDir2 = 'asc'"
                                                >{{ __('Asc') }}</button>
                                                <button
                                                    type="button"
                                                    class="flex-1 rounded-md border px-2 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
                                                    x-bind:class="sortDir2 === 'desc' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                                    @click="sortDir2 = 'desc'"
                                                >{{ __('Desc') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p class="mt-3 text-[10px] font-normal normal-case text-gray-500">
                                    {{ __('Set any minimum thresholds to narrow the list. Click a column header to sort; use Then by for a secondary sort when values tie.') }}
                                </p>
                            </div>

                            <template x-if="displayRows.length === 0">
                                <p class="text-sm text-gray-600">{{ __('NO ROWS MATCH THIS FILTER.') }}</p>
                            </template>

                            <div x-show="displayRows.length > 0" class="overflow-x-auto -mx-2 sm:mx-0">
                                <table class="player-list-table border-collapse text-center text-xs font-[700] leading-none">
                                    <colgroup>
                                        <col class="player-list-col-player" />
                                        <col class="player-list-col-pool" />
                                        <col class="player-list-col-school" />
                                        @for ($i = 0; $i < 10; $i++)
                                            <col class="player-list-grade-col" />
                                        @endfor
                                        <col class="player-list-col-action" />
                                        @unless ($playersReadOnly)
                                            <col class="player-list-col-action" />
                                        @endunless
                                    </colgroup>
                                    <thead>
                                        <tr class="bg-[#44546A] text-white">
                                            @foreach ([
                                                ['key' => 'player', 'label' => __('PLAYER'), 'grade' => false],
                                                ['key' => 'pool', 'label' => __('POOL'), 'grade' => false],
                                                ['key' => 'school', 'label' => __('SCHOOL'), 'grade' => false],
                                                ['key' => 'role', 'label' => __('ROLE'), 'grade' => true],
                                                ['key' => 'conf', 'label' => __('CONF'), 'grade' => true],
                                                ['key' => 'risk', 'label' => __('RISK'), 'grade' => true],
                                                ['key' => 'bat', 'label' => __('BAT'), 'grade' => true],
                                                ['key' => 'perf', 'label' => __('PERF'), 'grade' => true],
                                                ['key' => 'k_zone', 'label' => __('K-ZONE'), 'grade' => true],
                                                ['key' => 'damage', 'label' => __('DAMAGE'), 'grade' => true],
                                                ['key' => 'adj', 'label' => __('ADJ'), 'grade' => true],
                                                ['key' => 'platoon', 'label' => __('L/R'), 'grade' => true],
                                                ['key' => 'swing', 'label' => __('SWING'), 'grade' => true],
                                            ] as $column)
                                                <th
                                                    scope="col"
                                                    @class([
                                                        'border border-[#364056] px-1 py-2 uppercase tracking-wide transition-colors',
                                                        'player-list-grade-col' => $column['grade'],
                                                        'player-list-col-player' => $column['key'] === 'player',
                                                        'player-list-col-pool' => $column['key'] === 'pool',
                                                        'player-list-col-school' => $column['key'] === 'school',
                                                    ])
                                                    :class="sortHighlightHeader('{{ $column['key'] }}')"
                                                    :aria-sort="ariaSort('{{ $column['key'] }}')"
                                                >
                                                    <button type="button" class="inline-flex w-full items-center justify-center gap-0.5 text-white hover:text-gray-200" @click.prevent="sortBy('{{ $column['key'] }}')">
                                                        <span>{{ $column['label'] }}</span>
                                                        <span class="text-[10px] leading-none" x-text="sortIndicator('{{ $column['key'] }}')" aria-hidden="true"></span>
                                                    </button>
                                                </th>
                                            @endforeach
                                            <th
                                                scope="col"
                                                class="border border-[#364056] px-2 py-2 uppercase tracking-wide transition-colors"
                                                :class="sortHighlightHeader('profile')"
                                                :aria-sort="ariaSort('profile')"
                                            >
                                                <button type="button" class="inline-flex w-full items-center justify-center gap-0.5 text-white hover:text-gray-200" @click.prevent="sortBy('profile')">
                                                    <span>{{ __('PROFILE') }}</span>
                                                    <span class="text-[10px] leading-none" x-text="sortIndicator('profile')" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            @unless ($playersReadOnly)
                                                <th scope="col" class="border border-[#364056] px-2 py-2 uppercase tracking-wide">{{ __('DELETE') }}</th>
                                            @endunless
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white text-gray-800">
                                        <template x-for="row in displayRows" :key="row.id">
                                            <tr
                                                class="border-b border-gray-200 transition hover:bg-gray-50/80"
                                                x-bind:aria-label="row.name"
                                            >
                                                <td class="player-list-col-player border border-gray-200 px-1 py-1 text-center transition-colors" :class="sortHighlightBody('player')">
                                                    @if ($playersReadOnly)
                                                        <span class="min-w-0 whitespace-nowrap text-gray-900" x-text="row.name"></span>
                                                    @else
                                                    <template x-if="editingId !== row.id">
                                                        <div class="flex items-center justify-center gap-1">
                                                            <button
                                                                type="button"
                                                                class="inline-flex shrink-0 items-center justify-center rounded p-1 text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                                                title="{{ __('Edit') }}"
                                                                aria-label="{{ __('Edit player') }}"
                                                                @click.prevent="startEdit(row)"
                                                            >
                                                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                                                                    <path d="M8 2l2 2M2 10l2.5-.5L10 3.5 8.5 2 2.5 9.5 2 10z" />
                                                                </svg>
                                                            </button>
                                                            <span class="min-w-0 whitespace-nowrap text-gray-900" x-text="row.name"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="editingId === row.id">
                                                        <div class="space-y-1 font-normal">
                                                            <input
                                                                type="text"
                                                                x-model="editDraft.last_name"
                                                                class="block w-full rounded border-gray-300 px-1.5 py-0.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="{{ __('Last name') }}"
                                                                autocomplete="off"
                                                            />
                                                            <input
                                                                type="text"
                                                                x-model="editDraft.first_name"
                                                                class="block w-full rounded border-gray-300 px-1.5 py-0.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="{{ __('First name') }}"
                                                                autocomplete="off"
                                                            />
                                                            <p class="text-[10px] font-normal text-red-600" x-text="firstError('last_name') || firstError('first_name')"></p>
                                                        </div>
                                                    </template>
                                                    @endif
                                                </td>
                                                <td class="border border-gray-200 px-1 py-1 whitespace-nowrap uppercase transition-colors" :class="sortHighlightBody('pool')">
                                                    @if ($playersReadOnly)
                                                        <span class="text-gray-700" x-text="row.player_pool"></span>
                                                    @else
                                                    <template x-if="editingId !== row.id">
                                                        <span class="text-gray-700" x-text="row.player_pool"></span>
                                                    </template>
                                                    <template x-if="editingId === row.id">
                                                        <select x-model="editDraft.player_pool" class="block w-full rounded border-gray-300 px-1 py-0.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="ncaa">{{ __('NCAA') }}</option>
                                                            <option value="hs">{{ __('HS') }}</option>
                                                        </select>
                                                        <p class="text-[10px] font-normal text-red-600" x-text="firstError('player_pool')"></p>
                                                    </template>
                                                    @endif
                                                </td>
                                                <td class="border border-gray-200 px-1 py-1 transition-colors" :class="sortHighlightBody('school')">
                                                    @if ($playersReadOnly)
                                                        <span class="whitespace-nowrap" x-text="row.school ?? '—'"></span>
                                                    @else
                                                    <template x-if="editingId !== row.id">
                                                        <span class="whitespace-nowrap" x-text="row.school ?? '—'"></span>
                                                    </template>
                                                    <template x-if="editingId === row.id">
                                                        <div class="space-y-1 font-normal">
                                                            <input
                                                                type="text"
                                                                x-model="editDraft.school"
                                                                class="block w-full rounded border-gray-300 px-1.5 py-0.5 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="{{ __('School') }}"
                                                                autocomplete="off"
                                                            />
                                                            <p class="text-[10px] font-normal text-red-600" x-text="firstError('school')"></p>
                                                            <div class="flex flex-wrap gap-1">
                                                                <button
                                                                    type="button"
                                                                    class="rounded bg-indigo-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white hover:bg-indigo-700 disabled:opacity-50"
                                                                    @click.prevent="saveEdit(row.id)"
                                                                    x-bind:disabled="saving"
                                                                >{{ __('Save') }}</button>
                                                                <button
                                                                    type="button"
                                                                    class="rounded border border-gray-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                                                    @click.prevent="cancelEdit()"
                                                                    x-bind:disabled="saving"
                                                                >{{ __('Cancel') }}</button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    @endif
                                                </td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="[sortHighlightBody('role'), row.role_display === '-' ? '' : '']" :style="row.role_display === '-' ? null : row.role_style" x-text="row.role_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell player-list-metric-emphasis border border-gray-200 transition-colors" :class="sortHighlightBody('conf')" :style="row.conf_style" x-text="row.conf_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell player-list-metric-emphasis border border-gray-200 transition-colors" :class="sortHighlightBody('risk')" :style="row.risk_style" x-text="row.risk_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell player-list-metric-emphasis border border-gray-200 transition-colors" :class="sortHighlightBody('bat')" :style="row.bat_style" x-text="row.bat_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="sortHighlightBody('perf')" :style="row.perf_style" x-text="row.perf_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="sortHighlightBody('k_zone')" :style="row.k_zone_style" x-text="row.k_zone_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="sortHighlightBody('damage')" :style="row.damage_style" x-text="row.damage_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="sortHighlightBody('adj')" :style="row.adj_style" x-text="row.adj_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="sortHighlightBody('platoon')" :style="row.platoon_style" x-text="row.platoon_display"></td>
                                                <td class="player-list-grade-col player-list-filled-cell border border-gray-200 transition-colors" :class="sortHighlightBody('swing')" :style="row.swing_style" x-text="row.swing_display"></td>
                                                <td class="border border-gray-200 px-2 py-1.5 transition-colors" :class="sortHighlightBody('profile')">
                                                    <a
                                                        x-show="row.profile_url"
                                                        x-cloak
                                                        :href="row.profile_url"
                                                        class="inline-flex items-center gap-1 font-[700]"
                                                        x-bind:class="{
                                                            'player-list-profile-open--complete': row.profile_complete,
                                                            'text-indigo-600 hover:text-indigo-800': !row.profile_complete,
                                                        }"
                                                        x-bind:style="row.profile_complete ? { color: '#059669', WebkitTextFillColor: '#059669' } : null"
                                                    >
                                                        <span>{{ __('OPEN') }}</span>
                                                        <svg
                                                            x-show="row.profile_complete"
                                                            x-cloak
                                                            class="h-3.5 w-3.5 shrink-0"
                                                            viewBox="0 0 12 12"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            aria-hidden="true"
                                                        >
                                                            <path d="M2.5 6.5l2.5 2.5 4.5-5" />
                                                        </svg>
                                                    </a>
                                                    <span x-show="!row.profile_url" class="text-gray-400">—</span>
                                                </td>
                                                @unless ($playersReadOnly)
                                                <td class="border border-gray-200 px-2 py-1.5">
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
                                                </td>
                                                @endunless
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
