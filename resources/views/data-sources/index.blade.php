<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('DATA SOURCES') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-100">
                    {{ session('status') }}
                </div>
            @endif

            <div
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                x-data="csvUploadPreview({ oldName: @json(old('name')), maxPreviewRows: 15, maxFileBytes: 10485760 })"
            >
                <div class="p-6 text-gray-900 space-y-6">
                    <p class="text-sm text-gray-600">
                        {{ __('Choose a CSV, review the preview, enter a source name, then save. The full file is stored only when you click save.') }}
                    </p>

                    <div class="border-t border-gray-100 pt-4">
                        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-3">{{ __('UPLOAD CSV') }}</h3>
                        <form
                            method="POST"
                            action="{{ route('data-sources.store') }}"
                            enctype="multipart/form-data"
                            class="space-y-4"
                            @submit="if (!canSave) { $event.preventDefault(); }"
                        >
                            @csrf

                            <div class="grid grid-cols-1 gap-x-3 gap-y-3 sm:grid-cols-12 sm:items-end">
                                <div class="min-w-0 sm:col-span-5">
                                    <x-input-label for="upload_name" :value="__('SOURCE NAME')" class="!text-xs" />
                                    <x-text-input
                                        id="upload_name"
                                        class="block mt-1 w-full text-sm"
                                        type="text"
                                        name="name"
                                        x-model="sourceName"
                                        autocomplete="off"
                                    />
                                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                </div>
                                <div class="min-w-0 sm:col-span-7">
                                    <x-input-label for="csv_file" :value="__('CSV FILE')" class="!text-xs" />
                                    <input
                                        id="csv_file"
                                        x-ref="csvFile"
                                        type="file"
                                        name="file"
                                        accept=".csv,text/csv,text/plain"
                                        @change="onFileChange($event)"
                                        class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Maximum size 10 MB. First row must be column headers.') }}</p>
                                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                                </div>
                            </div>

                            <template x-if="previewError">
                                <div class="rounded-md bg-red-50 p-3 text-sm text-red-800 border border-red-100" x-text="previewError"></div>
                            </template>

                            <template x-if="previewNotice && !previewError">
                                <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-900 border border-amber-100" x-text="previewNotice"></div>
                            </template>

                            <div x-show="previewHeaders.length > 0 && !previewError" x-cloak class="space-y-2">
                                <h4 class="text-xs font-semibold text-gray-800 uppercase tracking-wide">{{ __('PREVIEW') }}</h4>
                                <div class="overflow-x-auto rounded-md border border-gray-200 -mx-2 sm:mx-0">
                                    <table class="min-w-full text-xs text-left">
                                        <thead class="bg-gray-50 text-gray-600 uppercase tracking-wide border-b border-gray-200">
                                            <tr>
                                                <template x-for="(h, idx) in previewHeaders" :key="idx">
                                                    <th class="px-3 py-2 font-semibold whitespace-nowrap border-r border-gray-100 last:border-r-0" x-text="h || '—'"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 text-gray-800 bg-white">
                                            <template x-if="previewRows.length === 0">
                                                <tr>
                                                    <td class="px-3 py-3 text-gray-500 italic" :colspan="Math.max(previewHeaders.length, 1)">
                                                        {{ __('No data rows in this portion of the file (header only or empty). You can still save if that is expected.') }}
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-for="(row, rIdx) in previewRows" :key="rIdx">
                                                <tr>
                                                    <template x-for="(h, cIdx) in previewHeaders" :key="cIdx">
                                                        <td class="px-3 py-2 border-r border-gray-50 last:border-r-0 max-w-[14rem] truncate" :title="String(row[cIdx] ?? '')">
                                                            <span x-text="row[cIdx] !== undefined && row[cIdx] !== null && String(row[cIdx]) !== '' ? row[cIdx] : '—'"></span>
                                                        </td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <x-primary-button
                                    x-bind:disabled="!canSave"
                                    class="disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ __('SAVE UPLOAD') }}
                                </x-primary-button>
                                <button
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    @click="clearFile()"
                                    x-show="fileLabel"
                                    x-cloak
                                >
                                    {{ __('CLEAR FILE') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if ($uploads->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 text-sm text-gray-600 normal-case sm:p-6">
                        {{ __('No saved datasets yet. Upload a CSV above.') }}
                    </div>
                </div>
            @else
                @php
                    $dataSourceLibraryConfig = [
                        'initialActiveId' => $initialActiveId,
                        'blankGroupTabLabel' => __('(blank)'),
                        'tableDataBase' => '/data-sources/uploads',
                        'readOnlyById' => $uploads->mapWithKeys(static fn (\App\Models\DataSourceUpload $u): array => [
                            (string) $u->id => $u->isCareerPgMaster(),
                        ])->all(),
                        'uploadSummaries' => $uploads->map(static function ($u) use ($uploads) {
                            $browse = $u->dataset_browse_settings;

                            return [
                                'id' => $u->id,
                                'name' => $u->name,
                                'upload_kind' => $u->upload_kind ?? \App\Models\DataSourceUpload::UPLOAD_KIND_FILE,
                                'dataset_read_only' => $u->isCareerPgMaster(),
                                'career_pg_source_upload_id' => $u->career_pg_source_upload_id,
                                'hs_profile_feed_slots' => $u->resolvedHsProfileFeedSlotsForUi($uploads),
                                'dataset_browse_settings' => is_array($browse) ? $browse : null,
                            ];
                        })->values()->all(),
                    ];
                @endphp
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    x-data="dataSourceLibrary(@js($dataSourceLibraryConfig))"
                >
                    <div class="border-b border-gray-200 px-4 pt-3 pb-3 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <nav class="-mb-px flex min-w-0 flex-1 gap-1 overflow-x-auto" aria-label="{{ __('Saved CSV datasets') }}">
                                @foreach ($uploads as $u)
                                    <button
                                        type="button"
                                        @click="selectUpload({{ $u->id }})"
                                        class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition text-left"
                                        :class="activeId === {{ $u->id }}
                                            ? 'border-indigo-600 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-800'"
                                    >
                                        <span class="max-w-[14rem] truncate inline-block align-bottom" title="{{ $u->name }}">{{ $u->name }}</span>
                                    </button>
                                @endforeach
                            </nav>
                            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-900 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    x-bind:disabled="loading || !activeId || activeUploadReadOnly || headers.length === 0"
                                    @click="scrollToAppendRow()"
                                    title="{{ __('Scroll to the form below to add a new row to this CSV') }}"
                                >
                                    {{ __('Append row') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-900 hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    x-bind:disabled="loading || !activeId"
                                    @click="saveDataset()"
                                >
                                    {{ __('Save dataset') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-red-200 bg-red-50 px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-red-800 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    x-bind:disabled="activeUploadReadOnly"
                                    @click="deleteActiveUpload()"
                                >
                                    {{ __('Delete dataset') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-100 bg-emerald-50/50 px-4 py-2 sm:px-6"
                        x-show="activeId && !activeUploadReadOnly"
                        x-cloak
                    >
                        <p class="text-[11px] font-medium normal-case text-gray-700">
                            {{ __('Append new rows below, or use the Append row button above after the table loads.') }}
                        </p>
                        <button
                            type="button"
                            class="shrink-0 rounded border border-emerald-300 bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-900 shadow-sm hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
                            x-bind:disabled="loading || headers.length === 0"
                            @click="scrollToAppendRow()"
                        >
                            {{ __('Jump to append form') }}
                        </button>
                    </div>

                    <div class="space-y-6 p-4 sm:p-6">
                        <template x-if="loading">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('LOADING…') }}</p>
                        </template>
                        <template x-if="loadError">
                            <div class="rounded-md bg-red-50 p-3 text-sm text-red-800 border border-red-100" x-text="loadError"></div>
                        </template>

                        <div x-show="!loading && !loadError" x-cloak class="space-y-6">
                            {{-- Flex (not arbitrary grid) so side-by-side layout always compiles; sm: = 640px+ --}}
                            <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-stretch sm:gap-5 lg:gap-6 xl:gap-8">
                                {{-- Left: HS profile tables only --}}
                                <div class="min-w-0 w-full flex-1 rounded-lg border border-gray-200 bg-gray-50/70 p-3 shadow-sm sm:p-4 sm:h-full">
                                    <div class="flex min-w-0 w-full flex-row items-start gap-x-2 sm:gap-x-4 md:gap-x-6 lg:gap-x-8">
                                        @foreach (\App\Support\HsRangerTraitsSheetLayout::hsProfileFeedUiGroups() as $group)
                                            <div class="flex min-w-0 flex-1 flex-col gap-2.5">
                                                <p class="border-b border-gray-200/90 pb-1.5 !text-xs !font-semibold !text-gray-800 !normal-case">
                                                    {{ $group['section'] }}
                                                </p>
                                                <div class="flex flex-col gap-2">
                                                    @foreach ($group['tables'] as $t)
                                                        <label class="flex cursor-pointer items-center gap-2 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                            <input
                                                                type="checkbox"
                                                                class="shrink-0 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50"
                                                                value="{{ $t['key'] }}"
                                                                x-model="hsProfileFeedDraft"
                                                                x-bind:disabled="activeUploadReadOnly"
                                                            />
                                                            <span class="leading-tight">{{ $t['label'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Right: Filter Players, Set Thresholds, and Group By in one pane --}}
                                <div
                                    class="relative z-30 flex min-h-0 w-full min-w-0 flex-col rounded-lg border border-gray-200 bg-white p-3 shadow-sm sm:h-full sm:w-96 sm:flex-shrink-0 lg:w-[28rem]"
                                    @click.outside="playerPickerOpen = false"
                                >
                                    <div class="relative shrink-0">
                                            <x-input-label for="dataset_player_filter" :value="__('Filter Players')" class="!text-xs !font-semibold !text-gray-800" />
                                            <div class="relative mt-2">
                                                <div
                                                    class="flex min-h-[2.5rem] flex-wrap items-center gap-1 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm shadow-sm focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/25"
                                                >
                                                    <template x-for="name in selectedPlayers" :key="name">
                                                        <span
                                                            class="inline-flex max-w-full items-center gap-0.5 rounded-md bg-indigo-50 pl-2 pr-0.5 py-0.5 text-xs font-medium text-indigo-900 ring-1 ring-inset ring-indigo-200/80"
                                                        >
                                                            <span class="min-w-0 truncate" x-text="name"></span>
                                                            <button
                                                                type="button"
                                                                class="shrink-0 rounded p-0.5 text-indigo-700 hover:bg-indigo-100/80 focus:outline-none focus-visible:ring-1 focus-visible:ring-indigo-500"
                                                                :aria-label="'{{ __('Remove') }} ' + name"
                                                                @click="removeSelectedPlayer(name)"
                                                            >
                                                                <svg class="h-3.5 w-3.5" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                                    <path stroke-linecap="round" d="M3 3l6 6M9 3L3 9" />
                                                                </svg>
                                                            </button>
                                                        </span>
                                                    </template>
                                                    <input
                                                        id="dataset_player_filter"
                                                        type="search"
                                                        class="min-w-[10rem] flex-1 border-0 bg-transparent py-0.5 text-sm normal-case text-gray-900 placeholder:text-gray-400 focus:ring-0"
                                                        placeholder="{{ __('Type to search players…') }}"
                                                        x-model="playerPickerQuery"
                                                        autocomplete="off"
                                                        role="combobox"
                                                        :aria-expanded="playerPickerOpen"
                                                        aria-haspopup="listbox"
                                                        @focus="playerPickerOpen = true"
                                                        @input="playerPickerOpen = true"
                                                        @keydown.escape.prevent="playerPickerOpen = false"
                                                    />
                                                </div>
                                                <div
                                                    x-cloak
                                                    x-show="playerPickerOpen && filteredPlayerPickerOptions.length > 0"
                                                    x-transition
                                                    class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5"
                                                >
                                                    <ul
                                                        role="listbox"
                                                        class="max-h-[min(50vh,16rem)] overflow-y-auto overscroll-contain text-sm normal-case text-gray-900"
                                                    >
                                                        <template x-for="opt in filteredPlayerPickerOptions" :key="opt">
                                                            <li role="option">
                                                                <button
                                                                    type="button"
                                                                    class="flex w-full px-3 py-1.5 text-left hover:bg-gray-50 focus:bg-gray-50 focus:outline-none"
                                                                    @click="selectPlayerFromPicker(opt)"
                                                                    x-text="opt"
                                                                ></button>
                                                            </li>
                                                        </template>
                                                    </ul>
                                                </div>
                                                <p
                                                    x-cloak
                                                    x-show="playerPickerOpen && playerPickerQuery.trim() !== '' && filteredPlayerPickerOptions.length === 0"
                                                    class="absolute left-0 right-0 top-full z-50 mt-1 rounded-md border border-gray-100 bg-white px-3 py-2 text-sm text-gray-500 shadow-sm"
                                                >
                                                    {{ __('No matching players.') }}
                                                </p>
                                            </div>
                                    </div>

                                    <div class="relative z-10 mt-4 shrink-0 border-t border-gray-200 pt-4">
                                        <x-input-label class="!text-xs !font-semibold !text-gray-800" :value="__('Min PA for column colors')" />
                                        <p class="mt-1 text-[10px] font-medium normal-case leading-snug text-gray-500">
                                            {{ __('Enter a minimum PA, then click Apply. Rows below that PA stay unshaded on heat columns. Leave blank and apply to color everyone.') }}
                                        </p>
                                        <input
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="mt-1.5 w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm normal-case text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30"
                                            x-model="heatMinPaDraft"
                                            autocomplete="off"
                                            placeholder="—"
                                        />
                                        <button
                                            type="button"
                                            class="mt-2 w-full rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-indigo-900 shadow-sm hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50"
                                            x-bind:disabled="loading || !activeId"
                                            @click="applyHeatPaCutoff()"
                                        >
                                            {{ __('Apply PA cutoff to colors') }}
                                        </button>
                                    </div>

                                    <div class="relative z-10 mt-4 flex min-h-0 min-w-0 flex-1 flex-col border-t border-gray-200 pt-4">
                                        <details class="flex min-h-0 flex-1 flex-col rounded-md border border-gray-100 bg-gray-50/50 px-2 py-1.5 sm:px-3 sm:py-2 [&[open]]:min-h-0 [&[open]]:flex-1">
                                            <summary class="shrink-0 cursor-pointer list-none select-none text-xs font-semibold text-gray-800 [&::-webkit-details-marker]:hidden">
                                                {{ __('Set Thresholds') }}
                                            </summary>
                                            <div class="mt-3 flex min-h-0 flex-1 flex-col gap-2">
                                                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                                                    <template x-for="(h, thIdx) in headers" :key="'ct-'+thIdx">
                                                        <div
                                                            x-show="thIdx > 0"
                                                            class="grid grid-cols-1 items-end gap-2 rounded-md border border-gray-100 bg-white/90 px-2 py-1.5 sm:grid-cols-[1fr_minmax(0,7rem)_minmax(0,7rem)]"
                                                        >
                                                            <div class="min-w-0">
                                                                <span
                                                                    class="block truncate text-[10px] font-semibold uppercase tracking-wide text-gray-700"
                                                                    x-text="h !== '' ? h : '—'"
                                                                ></span>
                                                            </div>
                                                            <div>
                                                                <label class="block text-[9px] font-medium uppercase tracking-wide text-gray-500">{{ __('Min') }}</label>
                                                                <input
                                                                    type="number"
                                                                    step="any"
                                                                    class="mt-0.5 w-full rounded border border-gray-300 px-1.5 py-1 text-xs normal-case text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30"
                                                                    x-model="thresholdDraft[thIdx].min"
                                                                    @input.debounce.400ms="onThresholdInputsChanged()"
                                                                    autocomplete="off"
                                                                />
                                                            </div>
                                                            <div>
                                                                <label class="block text-[9px] font-medium uppercase tracking-wide text-gray-500">{{ __('Max') }}</label>
                                                                <input
                                                                    type="number"
                                                                    step="any"
                                                                    class="mt-0.5 w-full rounded border border-gray-300 px-1.5 py-1 text-xs normal-case text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30"
                                                                    x-model="thresholdDraft[thIdx].max"
                                                                    @input.debounce.400ms="onThresholdInputsChanged()"
                                                                    autocomplete="off"
                                                                />
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="flex shrink-0 flex-wrap gap-2">
                                                    <button
                                                        type="button"
                                                        class="rounded border border-gray-300 bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-700 hover:bg-gray-50"
                                                        @click="clearColumnThresholds()"
                                                    >
                                                        {{ __('Clear thresholds') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </details>
                                    </div>

                                    <div class="mt-4 shrink-0 border-t border-gray-200 pt-4">
                                        <x-input-label for="dataset_group_column" :value="__('Group By')" class="!text-xs !font-semibold !text-gray-800" />
                                        <div x-show="headers.length > 0" x-cloak class="mt-2 space-y-2">
                                            {{-- Browsers only allow <option>/<optgroup> inside <select>; <template x-for> is invalid and breaks the control. Options are synced in JS. --}}
                                            <select
                                                id="dataset_group_column"
                                                x-ref="groupColumnSelect"
                                                x-model="groupByColumnRaw"
                                                @change="onGroupByColumnChanged($event)"
                                                class="block w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm normal-case text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30"
                                            >
                                                <option value="">{{ __('No grouping') }}</option>
                                            </select>
                                            <div
                                                x-show="groupByColumnRaw !== '' && groupValues.length > 0"
                                                x-cloak
                                                class="rounded-md border border-gray-200 bg-gray-50/80 px-1 pt-1 pb-0"
                                            >
                                                <nav
                                                    role="tablist"
                                                    class="-mb-px flex flex-wrap gap-1 overflow-x-auto overflow-y-visible normal-case text-gray-900"
                                                    aria-label="{{ __('Group values') }}"
                                                >
                                                    <a
                                                        role="tab"
                                                        href="#"
                                                        class="dataset-group-tab-item shrink-0 whitespace-nowrap border-b-2 border-solid border-transparent bg-transparent px-2.5 py-1.5 text-left font-semibold tracking-wide transition hover:border-gray-300"
                                                        :class="activeGroupValue === null ? '!border-indigo-600' : ''"
                                                        :data-active="activeGroupValue === null ? 'true' : 'false'"
                                                        :aria-selected="activeGroupValue === null ? 'true' : 'false'"
                                                        @click.prevent="selectGroupTab(null)"
                                                        @keydown.enter.prevent="selectGroupTab(null)"
                                                        @keydown.space.prevent="selectGroupTab(null)"
                                                    >
                                                        <span class="dataset-group-tab-label">{{ __('All') }}</span>
                                                    </a>
                                                    <template x-for="(gv, gvIdx) in groupValues" :key="'gv-'+gvIdx+'-'+(gv === '' ? 'e' : gv)">
                                                        <a
                                                            role="tab"
                                                            href="#"
                                                            class="dataset-group-tab-item shrink-0 whitespace-nowrap border-b-2 border-solid border-transparent bg-transparent px-2.5 py-1.5 text-left font-semibold tracking-wide transition hover:border-gray-300"
                                                            :class="activeGroupValue === gv ? '!border-indigo-600' : ''"
                                                            :data-active="activeGroupValue === gv ? 'true' : 'false'"
                                                            :aria-selected="activeGroupValue === gv ? 'true' : 'false'"
                                                            @click.prevent="selectGroupTab(gv)"
                                                            @keydown.enter.prevent="selectGroupTab(gv)"
                                                            @keydown.space.prevent="selectGroupTab(gv)"
                                                        >
                                                            <span
                                                                class="dataset-group-tab-label"
                                                                x-text="gv === '' ? blankGroupTabLabel : gv"
                                                            ></span>
                                                        </a>
                                                    </template>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                id="dataset-add-row"
                                x-show="activeId && !activeUploadReadOnly"
                                x-cloak
                                class="rounded-lg border border-emerald-100 bg-emerald-50/40 p-3 shadow-sm ring-1 ring-emerald-100/80"
                            >
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-800">
                                    {{ __('Add row') }}
                                </h4>
                                <p class="mt-1 text-[11px] font-normal normal-case leading-snug text-gray-500">
                                    {{ __('Enter values for each column as they appear in the table. The player column cannot be empty.') }}
                                </p>
                                <p
                                    x-show="headers.length === 0"
                                    class="mt-2 text-[11px] font-normal normal-case text-amber-800"
                                >
                                    {{ __('Columns are still loading. If this persists, reload the page.') }}
                                </p>
                                <div class="mt-2 max-w-full overflow-x-auto pb-1" x-show="headers.length > 0" x-cloak>
                                    <div class="flex min-w-min gap-2">
                                        <template x-for="(h, addIdx) in headers" :key="'add-col-' + addIdx">
                                            <label class="flex w-[7.5rem] shrink-0 flex-col gap-0.5">
                                                <span
                                                    class="truncate text-[10px] font-semibold uppercase tracking-wide text-gray-600"
                                                    :title="String(h ?? '')"
                                                    x-text="h !== '' ? h : '—'"
                                                ></span>
                                                <input
                                                    type="text"
                                                    class="w-full rounded border border-gray-300 px-1.5 py-1 text-[11px] !normal-case text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                    :aria-label="String(h ?? '')"
                                                    x-model="newRowCells[addIdx]"
                                                    autocomplete="off"
                                                />
                                            </label>
                                        </template>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-950 shadow-sm hover:bg-emerald-200/90 disabled:cursor-not-allowed disabled:opacity-50"
                                        x-bind:disabled="appendRowBusy || loading || headers.length === 0"
                                        @click="appendDatasetRow()"
                                    >
                                        {{ __('Append to CSV') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Grid rows share column tracks; first column sticky for horizontal scroll. --}}
                            <div class="rounded-lg border border-gray-200 bg-gray-50/50">
                                <div class="max-h-[70vh] overflow-auto">
                                    <div
                                        class="dataset-csv-grid text-xs text-gray-800 normal-case"
                                        :style="{ minWidth: datasetGridMinWidth }"
                                    >
                                        <div class="sticky top-0 z-20 border-b border-gray-200 bg-gray-100 shadow-sm">
                                            <div
                                                class="grid w-full gap-0 text-gray-700"
                                                :style="datasetGridStyle"
                                            >
                                                <template x-for="(h, hIdx) in headers" :key="hIdx">
                                                    <div
                                                        class="relative min-w-0 border-r border-gray-300 last:border-r-0 bg-gray-100"
                                                        :class="[
                                                            hIdx === 0
                                                                ? 'sticky left-0 top-0 z-50 bg-gray-100 shadow-[4px_0_14px_-6px_rgba(0,0,0,0.2)]'
                                                                : '',
                                                            columnDragOver === hIdx && hIdx > 0 ? 'ring-2 ring-inset ring-indigo-400 bg-indigo-50/50' : '',
                                                        ]"
                                                        @dragover.prevent="onColumnDragOver(hIdx, $event)"
                                                        @dragleave="onColumnDragLeave(hIdx)"
                                                        @drop.prevent="onColumnDrop(hIdx, $event)"
                                                    >
                                                        <div
                                                            class="flex min-h-[1.375rem] w-full items-center py-0.5"
                                                            :class="hIdx === 0 ? 'relative justify-center px-4 pr-7' : 'justify-center pl-0.5 pr-0.5'"
                                                        >
                                                            <template x-if="hIdx === 0">
                                                                <span
                                                                    class="min-w-0 max-w-full truncate text-center text-[10px] font-semibold uppercase tracking-wide text-gray-800"
                                                                    :title="String(h ?? '')"
                                                                    x-text="h !== '' ? h : '—'"
                                                                ></span>
                                                            </template>
                                                            <template x-if="hIdx === 0">
                                                                <button
                                                                    type="button"
                                                                    class="absolute right-0.5 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded text-gray-500 outline-none hover:bg-gray-200/90 hover:text-indigo-700 focus-visible:ring-1 focus-visible:ring-indigo-500"
                                                                    :class="sortColumn === hIdx ? 'text-indigo-600' : ''"
                                                                    :title="sortControlTitle(hIdx)"
                                                                    :aria-label="sortControlTitle(hIdx)"
                                                                    @click.stop="toggleSortColumn(hIdx)"
                                                                >
                                                                    <span
                                                                        class="text-[10px] font-semibold leading-none"
                                                                        x-text="sortColumn === hIdx ? (sortDirection === 'asc' ? '▲' : '▼') : '↕'"
                                                                    ></span>
                                                                </button>
                                                            </template>
                                                            <template x-if="hIdx > 0">
                                                                <div class="flex max-w-full min-w-0 items-center gap-px">
                                                                    <span
                                                                        class="shrink-0 cursor-grab active:cursor-grabbing rounded p-px text-gray-400 hover:text-gray-700 hover:bg-gray-200/80"
                                                                        draggable="true"
                                                                        title="{{ __('Drag to reorder column') }}"
                                                                        @dragstart.stop="onColumnDragStart(hIdx, $event)"
                                                                        @dragend="onColumnDragEnd()"
                                                                    >
                                                                        <svg class="h-3 w-2.5" viewBox="0 0 8 14" aria-hidden="true" fill="currentColor">
                                                                            <circle cx="2.5" cy="2.5" r="1" />
                                                                            <circle cx="2.5" cy="7" r="1" />
                                                                            <circle cx="2.5" cy="11.5" r="1" />
                                                                            <circle cx="6.5" cy="2.5" r="1" />
                                                                            <circle cx="6.5" cy="7" r="1" />
                                                                            <circle cx="6.5" cy="11.5" r="1" />
                                                                        </svg>
                                                                    </span>
                                                                    <span
                                                                        class="min-w-0 shrink truncate px-px text-center text-[10px] font-semibold uppercase tracking-wide text-gray-800"
                                                                        :title="String(h ?? '')"
                                                                        x-text="h !== '' ? h : '—'"
                                                                    ></span>
                                                                    <div class="relative shrink-0 flex items-center gap-0.5">
                                                                        <button
                                                                            type="button"
                                                                            class="relative h-2.5 w-2.5 shrink-0 rounded-sm border border-gray-300 shadow-sm flex items-center justify-center outline-none focus-visible:ring-1 focus-visible:ring-indigo-500"
                                                                            :class="heatMenuForIdx === hIdx ? 'ring-1 ring-indigo-400' : ''"
                                                                            :style="heatButtonSurface(h)"
                                                                            :title="heatRuleTitle(h)"
                                                                            :aria-label="heatRuleTitle(h)"
                                                                            aria-haspopup="true"
                                                                            :aria-expanded="heatMenuForIdx === hIdx"
                                                                            @click.stop="toggleHeatMenu(hIdx)"
                                                                        >
                                                                            <span class="pointer-events-none text-[6px] leading-none text-gray-400/90" x-show="!heatIsOn(h)">·</span>
                                                                        </button>
                                                                        <div
                                                                            x-show="heatMenuForIdx === hIdx"
                                                                            x-cloak
                                                                            x-transition
                                                                            @click.outside="closeHeatMenu()"
                                                                            class="absolute right-0 top-full z-[50] mt-0.5 w-44 rounded-md border border-gray-200 bg-white py-1 text-left shadow-lg"
                                                                        >
                                                                            <button
                                                                                type="button"
                                                                                class="block w-full px-3 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                                                                                @click.stop="pickHeatRule(h, 'off')"
                                                                            >
                                                                                {{ __('Off (no shading)') }}
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                class="block w-full px-3 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                                                                                @click.stop="pickHeatRule(h, 'high')"
                                                                            >
                                                                                {{ __('Red = high values') }}
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                class="block w-full px-3 py-1.5 text-left text-[11px] text-gray-800 hover:bg-gray-50"
                                                                                @click.stop="pickHeatRule(h, 'low')"
                                                                            >
                                                                                {{ __('Red = low values') }}
                                                                            </button>
                                                                        </div>
                                                                        <button
                                                                            type="button"
                                                                            class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-gray-500 outline-none hover:bg-gray-200/90 hover:text-indigo-700 focus-visible:ring-1 focus-visible:ring-indigo-500"
                                                                            :class="sortColumn === hIdx ? 'text-indigo-600' : ''"
                                                                            :title="sortControlTitle(hIdx)"
                                                                            :aria-label="sortControlTitle(hIdx)"
                                                                            @click.stop="toggleSortColumn(hIdx)"
                                                                        >
                                                                            <span
                                                                                class="text-[10px] font-semibold leading-none"
                                                                                x-text="sortColumn === hIdx ? (sortDirection === 'asc' ? '▲' : '▼') : '↕'"
                                                                            ></span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <template x-if="rows.length === 0">
                                            <div class="px-3 py-8 text-center text-gray-500 italic bg-white border-b border-gray-100 normal-case">
                                                {{ __('NO DATA ROWS IN THIS FILE.') }}
                                            </div>
                                        </template>
                                        <template x-for="(row, rIdx) in rows" :key="rIdx">
                                            <div
                                                class="group grid w-full gap-0 border-b border-gray-100 bg-white hover:bg-gray-50/90 tabular-nums text-[11px] leading-none"
                                                :style="datasetGridStyle"
                                            >
                                                <template x-for="(h, cIdx) in headers" :key="cIdx">
                                                    <div
                                                        class="flex min-h-[1.375rem] min-w-0 items-center border-r border-gray-100 py-0.5 leading-none last:border-r-0"
                                                        :class="[
                                                            cIdx === 0
                                                                ? 'sticky left-0 z-10 justify-start bg-white pl-4 pr-1.5 shadow-[4px_0_14px_-6px_rgba(0,0,0,0.12)] group-hover:bg-gray-50'
                                                                : 'justify-center px-0.5 text-center',
                                                        ]"
                                                        :style="datasetCellStyle(h, row[cIdx], row, rIdx)"
                                                    >
                                                        <template x-if="cIdx === 0">
                                                            <div class="flex w-full min-w-0 items-center">
                                                                <template x-if="rowOrdinalAt(rIdx) !== null && editingOrdinal === rowOrdinalAt(rIdx)">
                                                                    <div class="flex w-full max-w-[18rem] flex-col gap-0.5">
                                                                        <input type="text" class="h-5 w-full rounded border border-gray-300 px-1 text-[11px] leading-tight !normal-case" x-model="editPlayerDraft" />
                                                                        <div class="flex justify-center gap-1">
                                                                            <button type="button" class="rounded bg-gray-800 px-1.5 py-px text-[10px] leading-tight text-white" @click="saveEditPlayer()">{{ __('Save') }}</button>
                                                                            <button type="button" class="rounded border border-gray-300 bg-white px-1.5 py-px text-[10px] leading-tight" @click="cancelEditPlayer()">{{ __('Cancel') }}</button>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                                <template x-if="rowOrdinalAt(rIdx) !== null && editingOrdinal !== rowOrdinalAt(rIdx)">
                                                                    <div class="flex max-w-full min-w-0 items-center justify-start gap-px">
                                                                        <span class="min-w-0 shrink truncate font-medium text-gray-900" :title="String(row[0] ?? '')" x-text="row[0] !== undefined && row[0] !== null && String(row[0]) !== '' ? row[0] : '—'"></span>
                                                                        <template x-if="!activeUploadReadOnly">
                                                                            <button
                                                                                type="button"
                                                                                class="shrink-0 rounded p-0.5 text-gray-500 hover:bg-gray-200 hover:text-gray-900"
                                                                                title="{{ __('Edit player') }}"
                                                                                aria-label="{{ __('Edit player') }}"
                                                                                @click="startEditPlayer(rowOrdinalAt(rIdx), row[0])"
                                                                            >
                                                                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                                                </svg>
                                                                            </button>
                                                                        </template>
                                                                        <template x-if="!activeUploadReadOnly">
                                                                            <button
                                                                                type="button"
                                                                                class="shrink-0 rounded p-0.5 text-red-500 hover:bg-red-50 hover:text-red-700"
                                                                                title="{{ __('Remove row from CSV') }}"
                                                                                aria-label="{{ __('Remove row from CSV') }}"
                                                                                @click="removePlayer(rowOrdinalAt(rIdx))"
                                                                            >
                                                                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                                </svg>
                                                                            </button>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                                <template x-if="rowOrdinalAt(rIdx) === null">
                                                                    <span class="text-amber-700 text-[10px]" title="{{ __('Reload the table if actions are missing.') }}">{{ __('—') }}</span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <template x-if="cIdx !== 0">
                                                            <span class="block w-full min-w-0 truncate leading-none" :title="String(row[cIdx] ?? '')" x-text="row[cIdx] !== undefined && row[cIdx] !== null && String(row[cIdx]) !== '' ? row[cIdx] : '—'"></span>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-xs text-gray-600 uppercase tracking-wide">
                                <p class="tabular-nums">
                                    <span x-show="totalRows === 0">{{ __('NO ROWS TO DISPLAY.') }}</span>
                                    <span x-show="totalRows > 0">
                                        {{ __('SHOWING ROWS') }}
                                        <span x-text="Number(from).toLocaleString()"></span>–<span x-text="Number(to).toLocaleString()"></span>
                                        {{ __('OF') }}
                                        <span x-text="Number(totalRows).toLocaleString()"></span>
                                    </span>
                                </p>
                                <div class="flex flex-wrap items-center gap-2" x-show="lastPage > 1">
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                        x-bind:disabled="page <= 1"
                                        @click="loadPage(page - 1)"
                                    >
                                        {{ __('PREVIOUS') }}
                                    </button>
                                    <span class="tabular-nums text-gray-500">{{ __('PAGE') }} <span x-text="page"></span> {{ __('OF') }} <span x-text="lastPage"></span></span>
                                    <button
                                        type="button"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
                                        x-bind:disabled="page >= lastPage"
                                        @click="loadPage(page + 1)"
                                    >
                                        {{ __('NEXT') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
