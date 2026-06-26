@php
    use App\Models\WorkingBoardEntry;

    $defaultActiveBoard = WorkingBoardEntry::BOARD_MASTER;

    $workingBoardJsConfig = [
        'boardTypes' => $boardTypes,
        'visibleBoardTypes' => $boardVisibleTypes,
        'defaultActiveBoard' => $defaultActiveBoard,
        'roundKeys' => $boardRoundKeys,
        'roundLabels' => $boardRoundLabels,
        'confidenceOptions' => $boardConfidenceOptions,
        'riskOptions' => $boardRiskOptions,
        'riskLabels' => $boardRiskLabels,
        'boards' => $boardAlpineBoards,
        'batGradeBounds' => $boardBatGradeBounds,
        'updateUrl' => route('board.update'),
        'hsPlayerBaseUrl' => url('/hs/players'),
        'ncaaPlayerBaseUrl' => url('/ncaa/players'),
        'readOnly' => $boardReadOnly,
    ];
@endphp
<x-app-layout>
    <div class="flex min-h-0 flex-1 flex-col py-3 sm:py-4">
        <div class="mx-auto flex min-h-0 w-full min-w-0 flex-1 flex-col px-2 sm:px-3 lg:px-4">
            <script id="working-boards-config" type="application/json">
                {!! json_encode($workingBoardJsConfig, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
            </script>

            <div class="working-boards-page relative flex min-h-0 min-w-0 flex-1 flex-col" x-data="workingBoards()">
                <div class="relative z-10 shrink-0 pb-3">
                    <div
                        class="pointer-events-none absolute right-0 top-0 flex items-center gap-2 text-[10px] font-semibold text-slate-600 normal-case sm:text-xs"
                    >
                        <span x-show="saving" x-cloak class="pointer-events-auto rounded bg-white/90 px-2 py-0.5 shadow-sm">{{ __('Saving…') }}</span>
                        <span
                            x-show="statusMessage"
                            x-text="statusMessage"
                            x-cloak
                            class="pointer-events-auto max-w-[16rem] rounded px-2 py-0.5 shadow-sm"
                            :class="statusIsError ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-800'"
                        ></span>
                    </div>

                </div>

                <div class="working-boards-row flex min-h-0 min-w-0 flex-1 flex-col pb-1">
                    @foreach ($boardPanelOrder as $boardType)
                        <div
                            class="flex min-h-0 min-w-0 flex-1 flex-col"
                            style="display: {{ $boardType === $defaultActiveBoard ? 'flex' : 'none' }};"
                            x-show="activeBoard === '{{ $boardType }}'"
                            role="tabpanel"
                        >
                            @include('board.partials.pane', [
                                'boardType' => $boardType,
                                'panel' => $boardPanels[$boardType],
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
