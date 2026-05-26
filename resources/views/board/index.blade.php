<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('WORKING BOARD') }}
        </h2>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div
                class="working-board-shell overflow-hidden rounded-lg border border-slate-700 bg-slate-800 shadow-lg"
                x-data="workingBoard({
                    roundKeys: @json($boardRoundKeys),
                    confidenceOptions: @json($boardConfidenceOptions),
                    riskOptions: @json($boardRiskOptions),
                    initialRounds: @json($boardInitialRounds),
                    playerPool: @json($boardPlayerPool),
                    updateUrl: @json(route('board.update')),
                    hsPlayerBaseUrl: @json(url('/hs/players')),
                })"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-900 bg-slate-900 px-4 py-3 sm:px-5"
                >
                    <h1 class="text-sm font-bold tracking-widest text-white sm:text-base">
                        {{ __('HS BOARD') }}
                    </h1>
                    <div class="flex items-center gap-2 text-[10px] font-semibold text-slate-300 sm:text-xs">
                        <span x-show="saving" x-cloak class="normal-case">{{ __('Saving…') }}</span>
                        <span x-show="saveError" x-text="saveError" class="max-w-[200px] truncate text-red-300 normal-case"></span>
                    </div>
                </div>

                <div class="overflow-x-auto bg-slate-100">
                    <table class="working-board-table min-w-[920px] w-full border-collapse text-left text-[11px] sm:text-xs">
                        <thead>
                            <tr class="border-b border-slate-300 bg-slate-200 text-[10px] font-bold tracking-wide text-slate-900">
                                <th class="working-board-th w-[7.5rem] px-2 py-2">{{ __('CONF') }}</th>
                                <th class="working-board-th min-w-[11rem] px-2 py-2">{{ __('NAME') }}</th>
                                <th class="working-board-th w-14 px-2 py-2">{{ __('POS') }}</th>
                                <th class="working-board-th min-w-[14rem] px-2 py-2">{{ __('SCHOOL') }}</th>
                                <th class="working-board-th w-16 px-2 py-2">{{ __('ROLE') }}</th>
                                <th class="working-board-th w-16 px-2 py-2">{{ __('SWING') }}</th>
                                <th class="working-board-th w-[8.5rem] px-2 py-2">{{ __('RISK') }}</th>
                                <th class="working-board-th w-10 px-1 py-2"></th>
                            </tr>
                        </thead>
                        <template x-for="rk in roundKeys" :key="rk">
                            <tbody
                                class="working-board-round-body border-b border-slate-300 bg-white"
                                :data-round="rk"
                                @dragover.prevent
                                @drop.prevent="onRoundDrop($event, rk)"
                            >
                                <tr class="working-board-round-divider bg-slate-200/90">
                                    <td colspan="8" class="px-2 py-2">
                                        <div class="flex items-center gap-3">
                                            <span class="h-px flex-1 border-t border-dashed border-slate-500"></span>
                                            <span
                                                class="shrink-0 text-[11px] font-bold tracking-widest text-slate-700"
                                                x-text="rk"
                                            ></span>
                                            <span class="h-px flex-1 border-t border-dashed border-slate-500"></span>
                                        </div>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 normal-case">
                                            <label class="sr-only" x-text="'Add player to round ' + rk"></label>
                                            <select
                                                class="max-w-xs rounded border border-slate-300 bg-white py-1 pl-2 pr-6 text-[11px] font-medium text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/40"
                                                x-model="nextAddByRound[rk]"
                                                @change="addPlayerToRound(rk)"
                                            >
                                                <option value="">{{ __('Add player to this round…') }}</option>
                                                <template x-for="opt in poolOptions()" :key="String(opt.player_id)">
                                                    <option
                                                        :value="String(opt.player_id)"
                                                        x-text="opt.label"
                                                    ></option>
                                                </template>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                                <template x-for="(card, idx) in (rounds[rk] || [])" :key="'p-' + rk + '-' + card.player_id">
                                    <tr
                                        class="working-board-card-row cursor-grab border-b border-slate-200 text-slate-900 hover:bg-slate-50/80 active:cursor-grabbing"
                                        draggable="true"
                                        data-player-row
                                        @dragstart="onDragStart($event, rk, idx)"
                                    >
                                        <td class="border-r border-slate-200 px-1 py-1 align-middle normal-case">
                                            <select
                                                class="h-8 w-full min-w-0 rounded border border-slate-300 px-1 text-[10px] font-semibold shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30"
                                                :class="confidenceSelectClass(card.confidence)"
                                                x-model="card.confidence"
                                                @change="scheduleSave()"
                                            >
                                                <template x-for="co in confidenceOptions" :key="'c-' + rk + '-' + idx + '-' + String(co)">
                                                    <option :value="co" x-text="confidenceLabel(co)"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="border-r border-slate-200 px-2 py-1 align-middle font-bold leading-snug">
                                            <a
                                                class="text-indigo-900 underline decoration-indigo-300 decoration-1 underline-offset-2 hover:text-indigo-700"
                                                :href="hsPlayerUrl(card.player_id)"
                                                x-text="boardName(card)"
                                            ></a>
                                        </td>
                                        <td
                                            class="border-r border-slate-200 px-2 py-1 align-middle font-bold text-slate-800"
                                            x-text="card.position || '—'"
                                        ></td>
                                        <td
                                            class="border-r border-slate-200 px-2 py-1 align-middle text-[10px] font-semibold leading-snug text-slate-800 sm:text-[11px]"
                                            x-text="card.school || '—'"
                                        ></td>
                                        <td
                                            class="border-r border-slate-200 px-2 py-1 align-middle text-center font-bold"
                                            :class="gradeHeatClass(card.grade_role)"
                                            x-text="gradeFmt(card.grade_role)"
                                        ></td>
                                        <td
                                            class="border-r border-slate-200 px-2 py-1 align-middle text-center font-bold"
                                            :class="gradeHeatClass(card.grade_swing)"
                                            x-text="gradeFmt(card.grade_swing)"
                                        ></td>
                                        <td class="border-r border-slate-200 px-1 py-1 align-middle normal-case">
                                            <select
                                                class="h-8 w-full min-w-0 rounded border border-slate-300 px-1 text-[10px] font-bold shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500/30"
                                                :class="riskSelectClass(card.risk)"
                                                x-model="card.risk"
                                                @change="scheduleSave()"
                                            >
                                                <template x-for="ro in riskOptions" :key="'r-' + rk + '-' + idx + '-' + String(ro)">
                                                    <option :value="ro" x-text="riskLabel(ro)"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-1 py-1 align-middle text-center">
                                            <button
                                                type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-slate-300 bg-white text-slate-500 hover:border-red-300 hover:bg-red-50 hover:text-red-700"
                                                title="{{ __('Remove from board') }}"
                                                @click="removeFromRound(rk, idx)"
                                            >
                                                <span class="sr-only">{{ __('Remove') }}</span>
                                                <span aria-hidden="true" class="text-sm leading-none">×</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="(rounds[rk] || []).length === 0" class="working-board-empty bg-slate-50/80">
                                    <td colspan="8" class="px-3 py-4 text-center text-[11px] font-medium text-slate-500 normal-case">
                                        {{ __('Drop players here or add from the list above.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>
                <p class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-[10px] font-medium leading-relaxed text-slate-600 normal-case sm:text-[11px]">
                    {{ __('Drag the grip area (row) to reorder within a round or move to another round. Changes save automatically after a short pause.') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
