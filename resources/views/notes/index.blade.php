<x-app-layout>
    @if (session('status'))
        <div
            x-cloak
            x-data="{ show: true }"
            x-init="setTimeout(() => { show = false }, 4000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-4 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-none fixed end-4 top-16 z-50 max-w-sm sm:end-6 sm:top-20"
            role="status"
            aria-live="polite"
        >
            <div
                class="pointer-events-auto rounded-lg border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-normal normal-case leading-snug text-green-900 shadow-lg ring-1 ring-black/5"
            >
                {{ session('status') }}
            </div>
        </div>
    @endif

    <div
        class="notes-grades-page-shell flex min-h-0 w-full flex-1 flex-col overflow-hidden pt-4 pb-4 sm:pt-5 sm:pb-5"
    >
        <div
            class="mx-auto flex min-h-0 w-full max-w-7xl flex-1 flex-col gap-4 px-4 sm:px-6 xl:max-w-[90rem] 2xl:max-w-[110rem] 2xl:px-4"
        >
            @if ($errors->any())
                <div class="shrink-0 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                class="relative flex min-h-0 flex-1 flex-col overflow-visible rounded-lg bg-white shadow-sm sm:rounded-lg"
            >
                <div
                    class="shrink-0 border-b border-gray-100 px-4 py-4 text-gray-900 normal-case sm:px-5 sm:py-5"
                >
                    <div class="notes-grades-top-bar">
                        <div class="min-w-0" aria-hidden="true"></div>
                        <div class="min-w-0 w-full">
                            <x-notes.player-combobox
                                :notes-combobox-players="$notesComboboxPlayers"
                                :selected-player="$selectedPlayer"
                            />
                        </div>
                        <div class="flex min-w-0 justify-end pr-3 sm:pr-4">
                            @if ($selectedPlayer)
                                <form
                                    id="notes-bulk-save"
                                    method="POST"
                                    action="{{ route('notes.update-all') }}"
                                    class="shrink-0"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="player_id" value="{{ $selectedPlayer->id }}" />
                                    <input type="hidden" name="player_pool" value="{{ $selectedPlayer->player_pool }}" />
                                    <x-primary-button type="submit">{{ __('Save notes') }}</x-primary-button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($selectedPlayer)
                    @php
                        $isNcaaNotes = strtolower((string) $selectedPlayer->player_pool) === 'ncaa';
                    @endphp

                    <div
                        @class([
                            'flex min-h-0 flex-1 flex-col px-4 pb-4 pt-2 text-gray-900 normal-case sm:px-5 sm:pb-5 sm:pt-3',
                            'overflow-y-auto' => $isNcaaNotes,
                            'overflow-hidden' => ! $isNcaaNotes,
                        ])
                    >
                        @if ($isNcaaNotes)
                            @php
                                $byKey = collect($noteSections)->keyBy('key');
                                $ncaaSix = [
                                    $byKey->get('master_take'),
                                    $byKey->get('note_engine'),
                                    $byKey->get('note_performance'),
                                    $byKey->get('note_left_right'),
                                    $byKey->get('note_approach_miss'),
                                    $byKey->get('note_swing'),
                                ];
                                $ncaaPitch = $byKey->get('note_pitch_coverage');
                            @endphp
                            {{-- 2×3 equal-height rows use flex-1; scroll for pitch coverage row below. --}}
                            <div class="notes-ncaa-six-grid">
                                @foreach ($ncaaSix as $section)
                                    @if ($section)
                                        <div class="min-h-0 min-w-0">
                                            @include('notes.partials.section-editor', [
                                                'section' => $section,
                                                'selectedPlayer' => $selectedPlayer,
                                                'fillGridCell' => true,
                                            ])
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <div
                                class="notes-ncaa-tail-row mt-4 shrink-0 border-t border-gray-100 pt-4 sm:mt-5 sm:pt-5"
                            >
                                @if ($ncaaPitch)
                                    <div class="min-w-0">
                                        @include('notes.partials.section-editor', [
                                            'section' => $ncaaPitch,
                                            'selectedPlayer' => $selectedPlayer,
                                            'belowFold' => true,
                                        ])
                                    </div>
                                @endif
                                <div class="min-w-0" aria-hidden="true"></div>
                            </div>
                        @else
                            @php
                                $notesSplitAt = (int) ceil(count($noteSections) / 2);
                                $noteSectionColumns = [
                                    array_slice($noteSections, 0, $notesSplitAt),
                                    array_slice($noteSections, $notesSplitAt),
                                ];
                            @endphp
                            <div class="notes-grades-columns">
                                @foreach ($noteSectionColumns as $columnSections)
                                    <div class="notes-grades-column flex min-h-0 min-w-0 flex-1 flex-col gap-2">
                                        @foreach ($columnSections as $section)
                                            @include('notes.partials.section-editor', [
                                                'section' => $section,
                                                'selectedPlayer' => $selectedPlayer,
                                            ])
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
