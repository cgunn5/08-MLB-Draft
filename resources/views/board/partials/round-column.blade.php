@php
    use App\Models\WorkingBoardEntry;

    /** @var string $boardType */
    /** @var string $boardRoundKey */
@endphp
<div
    @class([
        'working-board-round-column flex shrink-0 flex-col border-r border-slate-300 bg-white',
        'working-board-round-column--coffin' => $boardRoundKey === WorkingBoardEntry::ROUND_COFFIN,
    ])
    data-round="{{ $boardRoundKey }}"
    data-board-type="{{ $boardType }}"
>
    <div class="working-board-round-header shrink-0 border-b border-slate-300 bg-[#e5e7eb] px-2 py-1.5 text-center">
        <span class="working-board-round-label text-[10px] font-bold tracking-widest text-black">
            {{ $boardRoundLabels[$boardRoundKey] ?? $boardRoundKey }}
        </span>
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
                    <th class="working-board-th working-board-col-grade px-0.5 py-1">{{ __('SWING') }}</th>
                </tr>
            </thead>
            <tbody
                class="working-board-round-body bg-white"
                @dragover.prevent
                @drop.prevent="onRoundDrop($event, '{{ $boardType }}', '{{ $boardRoundKey }}')"
            >
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
                        <td
                            class="working-board-scale-cell working-board-col-scale border-r border-slate-200 p-0 align-middle normal-case"
                            :style="boardScaleFillStyle(card.risk)"
                            @click="openScaleSelect($event)"
                        >
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
                        </td>
                        <td
                            class="working-board-grade-cell working-board-col-grade border-r border-slate-200 p-0 align-middle font-bold text-black"
                            :style="roleCellStyle(card)"
                            x-text="gradeFmt(card.grade_role)"
                        ></td>
                        <td class="working-board-name-cell working-board-col-name border-r border-slate-200 bg-white px-1 py-0.5 align-middle leading-snug">
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
                            class="working-board-col-pos border-r border-slate-200 bg-white px-0.5 py-0.5 align-middle font-bold text-slate-800"
                            x-text="card.position || '—'"
                        ></td>
                        <td
                            class="working-board-grade-cell working-board-col-grade border-r border-slate-200 p-0 align-middle font-bold text-black"
                            :style="batCellStyle(card)"
                            x-text="gradeFmt(batGrade(card))"
                        ></td>
                        <td
                            class="working-board-grade-cell working-board-col-grade bg-white p-0 align-middle font-bold text-black"
                            :style="swingCellStyle(card)"
                            x-text="gradeFmt(card.grade_swing)"
                        ></td>
                    </tr>
                </template>
                <tr x-show="roundCards('{{ $boardType }}', '{{ $boardRoundKey }}').length === 0" x-cloak class="working-board-empty bg-slate-50/80">
                    <td colspan="7" class="px-2 py-4 text-center text-[10px] font-medium leading-snug text-slate-500 normal-case">
                        {{ __('Drop players here or use the search box above.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
