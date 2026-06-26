<div
    class="working-board-round-column flex shrink-0 flex-col overflow-hidden rounded-md border bg-white shadow-sm"
    data-round="{{ $boardRoundKey }}"
    data-board-type="{{ $boardType }}"
>
    <div class="working-board-round-header relative shrink-0 border-b border-gray-200 bg-[#e5e7eb] px-2 py-1.5 text-center">
        <span class="working-board-round-label text-[10px] font-bold tracking-widest text-black">
            {{ $boardRoundLabels[$boardRoundKey] ?? $boardRoundKey }}
        </span>
        @unless ($boardReadOnly)
            <button
                type="button"
                class="working-board-add-tier-btn"
                title="{{ __('Add tier separator') }}"
                @click="addTierDivider('{{ $boardType }}', '{{ $boardRoundKey }}')"
            >
                <span class="sr-only">{{ __('Add tier separator') }}</span>
                <span aria-hidden="true">+</span>
            </button>
        @endunless
    </div>

    <div class="working-board-round-table-wrap">
        <table class="working-board-table working-board-round-table w-full min-w-0 border-collapse text-center text-[10px]">
            <thead>
                <tr class="border-b border-[#3d4f68] text-[9px] font-bold tracking-wide text-white">
                    <th class="working-board-th working-board-col-scale px-0.5 py-1">{{ __('CONF') }}</th>
                    <th class="working-board-th working-board-col-scale px-0.5 py-1">{{ __('RISK') }}</th>
                    <th class="working-board-th working-board-col-grade px-0.5 py-1">{{ __('ROLE') }}</th>
                    <th class="working-board-th working-board-col-name px-1 py-1">{{ __('NAME') }}</th>
                    <th class="working-board-th working-board-col-pos px-0.5 py-1">{{ __('POS') }}</th>
                    <th class="working-board-th working-board-col-grade px-0.5 py-1">{{ __('BAT') }}</th>
                    <th class="working-board-th working-board-col-grade px-0.5 py-1">{{ __('Sw') }}</th>
                </tr>
            </thead>
            <tbody
                class="working-board-round-body bg-white"
                @dragover.prevent
                @drop.prevent="onRoundDrop($event, '{{ $boardType }}', '{{ $boardRoundKey }}')"
            >
                <template x-for="(card, idx) in roundCards('{{ $boardType }}', '{{ $boardRoundKey }}')" :key="roundRowKey('{{ $boardType }}', '{{ $boardRoundKey }}', card, idx)">
                    <tr
                        data-board-row
                        data-board-list-row
                        :class="[
                            isTierDivider(card)
                                ? 'working-board-tier-row border-b border-slate-200'
                                : isNonTargetDivider(card)
                                  ? 'working-board-non-target-row border-b border-slate-200'
                                  : 'working-board-card-row border-b border-slate-200 bg-white text-center font-bold text-slate-900 hover:bg-slate-50/80',
                            !isRoundDivider(card) && isBelowNonTargetDivider('{{ $boardType }}', '{{ $boardRoundKey }}', idx)
                                ? 'working-board-non-target-player'
                                : '',
                            readOnly ? '' : 'cursor-grab active:cursor-grabbing',
                        ]"
                        :draggable="!readOnly && !isNonTargetDivider(card)"
                        :data-player-row="!isRoundDivider(card) ? true : null"
                        :data-tier-divider="isTierDivider(card) ? true : null"
                        :data-non-target-divider="isNonTargetDivider(card) ? true : null"
                        @dragstart="onDragStart($event, '{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                    >
                        <td
                            x-show="isTierDivider(card)"
                            colspan="7"
                            class="working-board-tier-cell relative p-0"
                        >
                            <div class="working-board-tier-dashes-wrap">
                                <span class="working-board-tier-dashes" aria-hidden="true">
                                    @for ($dash = 0; $dash < 36; $dash++)
                                        <span>-</span>
                                    @endfor
                                </span>
                            </div>
                            @unless ($boardReadOnly)
                                <button
                                    type="button"
                                    class="working-board-remove-btn working-board-tier-remove-btn"
                                    title="{{ __('Remove tier separator') }}"
                                    draggable="false"
                                    @mousedown.stop
                                    @click.stop="removeFromRound('{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                                >
                                    <span class="sr-only">{{ __('Remove') }}</span>
                                    <span aria-hidden="true">×</span>
                                </button>
                            @endunless
                        </td>
                        <td
                            x-show="isNonTargetDivider(card)"
                            colspan="7"
                            class="working-board-non-target-cell relative p-0"
                        >
                            <div class="working-board-tier-dashes-wrap working-board-non-target-divider-wrap">
                                <div class="working-board-non-target-divider" aria-hidden="true">
                                    <span class="working-board-tier-dashes working-board-non-target-divider-dashes">
                                        @for ($dash = 0; $dash < 14; $dash++)
                                            <span>-</span>
                                        @endfor
                                    </span>
                                    <span class="working-board-non-target-divider-label">{{ __('Pass') }}</span>
                                    <span class="working-board-tier-dashes working-board-non-target-divider-dashes">
                                        @for ($dash = 0; $dash < 14; $dash++)
                                            <span>-</span>
                                        @endfor
                                    </span>
                                </div>
                            </div>
                            <span class="sr-only">{{ __('Pass divider') }}</span>
                        </td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-scale-cell working-board-col-scale border-r border-slate-200 p-0 align-middle normal-case"
                            :style="boardScaleFillStyle(card.confidence)"
                        >
                            <span
                                x-show="isBelowNonTargetDivider('{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                                class="block min-h-[1.75rem] w-full"
                                aria-hidden="true"
                            ></span>
                            <div
                                x-show="!isBelowNonTargetDivider('{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
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
                            </div>
                        </td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-scale-cell working-board-col-scale border-r border-slate-200 p-0 align-middle normal-case"
                            :style="boardScaleFillStyle(card.risk)"
                            @click="!isBelowNonTargetDivider('{{ $boardType }}', '{{ $boardRoundKey }}', idx) && openScaleSelect($event)"
                        >
                            <span
                                x-show="isBelowNonTargetDivider('{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                                class="block min-h-[1.75rem] w-full"
                                aria-hidden="true"
                            ></span>
                            <div x-show="!isBelowNonTargetDivider('{{ $boardType }}', '{{ $boardRoundKey }}', idx)">
                                <label class="sr-only">{{ __('Risk') }}</label>
                                <select
                                    class="working-board-scale-select h-full min-h-[1.75rem] w-full cursor-pointer border-0 bg-transparent text-transparent shadow-none focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500/50"
                                    x-model="card.risk"
                                    :disabled="readOnly"
                                    @change="scheduleSave()"
                                >
                                    <template x-for="ro in riskOptions" :key="'r-{{ $boardType }}-{{ $boardRoundKey }}-' + idx + '-' + String(ro)">
                                        <option :value="ro" x-text="riskLabel(ro)"></option>
                                    </template>
                                </select>
                            </div>
                        </td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-grade-cell working-board-col-grade border-r border-slate-200 p-0 align-middle font-bold text-black working-board-non-target-role-cell"
                            :style="roleCellStyle(card)"
                            x-text="gradeFmt(card.grade_role)"
                        ></td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-name-cell working-board-col-name border-r border-slate-200 bg-white px-1 py-0.5 align-middle leading-snug"
                        >
                            <div class="flex items-center justify-center gap-0.5">
                                <a
                                    class="working-board-player-name min-w-0 text-black underline decoration-slate-400 decoration-1 underline-offset-2 hover:decoration-black"
                                    :href="playerUrl(card)"
                                    x-text="boardName(card)"
                                ></a>
                                @unless ($boardReadOnly)
                                    <button
                                        type="button"
                                        class="working-board-remove-btn shrink-0"
                                        title="{{ __('Remove from board') }}"
                                        draggable="false"
                                        @mousedown.stop
                                        @click.stop="removeFromRound('{{ $boardType }}', '{{ $boardRoundKey }}', idx)"
                                    >
                                        <span class="sr-only">{{ __('Remove') }}</span>
                                        <span aria-hidden="true">×</span>
                                    </button>
                                @endunless
                            </div>
                        </td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-col-pos border-r border-slate-200 bg-white px-0.5 py-0.5 align-middle font-bold text-slate-800"
                            x-text="card.position || '—'"
                        ></td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-grade-cell working-board-col-grade border-r border-slate-200 p-0 align-middle font-bold text-black"
                            :style="batCellStyle(card)"
                            x-text="gradeFmt(batGrade(card))"
                        ></td>
                        <td
                            x-show="!isRoundDivider(card)"
                            class="working-board-grade-cell working-board-col-grade bg-white p-0 align-middle font-bold text-black"
                            :style="swingCellStyle(card)"
                            x-text="gradeFmt(card.grade_swing)"
                        ></td>
                    </tr>
                </template>
                <tr
                    x-show="nonTargetDividerListIndex('{{ $boardType }}', '{{ $boardRoundKey }}') !== -1"
                    x-cloak
                    data-board-row
                    data-board-drop-tail
                    class="working-board-drop-tail"
                    aria-hidden="true"
                >
                    <td colspan="7"></td>
                </tr>
                <tr
                    x-show="roundPlayerCount('{{ $boardType }}', '{{ $boardRoundKey }}') === 0"
                    x-cloak
                    class="working-board-empty bg-slate-50/80"
                >
                    <td colspan="7" class="px-2 py-4 text-center text-[10px] font-medium leading-snug text-slate-500 normal-case">
                        {{ __('Drop players above the Pass line or use the search box.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
