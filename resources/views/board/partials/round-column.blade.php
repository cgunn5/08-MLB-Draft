@php
    $isPassColumn = str_ends_with($boardRoundKey, '-pass');
@endphp
<div
    @class([
        'working-board-round-column flex shrink-0 flex-col overflow-hidden rounded-md border bg-white shadow-sm',
        'working-board-round-column--pass' => $isPassColumn,
    ])
    data-round="{{ $boardRoundKey }}"
    data-board-type="{{ $boardType }}"
>
    <div class="working-board-round-header relative shrink-0 border-b border-[#3d4f68] bg-[#475b78] px-2 py-1.5 text-center">
        <span class="working-board-round-label text-[10px] font-bold tracking-wide text-white">
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
                <tr class="border-b border-slate-300 text-[9px] font-bold tracking-wide text-slate-900">
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
                @class([
                    'working-board-round-body bg-white',
                    'working-board-pass-body' => $isPassColumn,
                ])
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
                                : 'working-board-card-row border-b border-slate-200 bg-white text-center font-bold text-slate-900 hover:bg-slate-50/80',
                            !isRoundDivider(card) && isPassRoundKey('{{ $boardRoundKey }}')
                                ? 'working-board-non-target-player'
                                : '',
                            readOnly ? '' : 'cursor-grab active:cursor-grabbing',
                        ]"
                        :draggable="!readOnly"
                        :data-player-row="!isRoundDivider(card) ? true : null"
                        :data-tier-divider="isTierDivider(card) ? true : null"
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
                            x-show="!isRoundDivider(card)"
                            class="working-board-scale-cell working-board-col-scale border-r border-slate-200 p-0 align-middle normal-case"
                            :style="boardScaleFillStyle(card.confidence)"
                        >
                            <span
                                x-show="isPassRoundKey('{{ $boardRoundKey }}')"
                                class="block h-full w-full"
                                aria-hidden="true"
                            ></span>
                            <div
                                x-show="!isPassRoundKey('{{ $boardRoundKey }}')"
                                @click="openScaleSelect($event)"
                            >
                                <label class="sr-only">{{ __('Confidence') }}</label>
                                <select
                                    class="working-board-scale-select h-full w-full cursor-pointer border-0 bg-transparent text-transparent shadow-none focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500/50"
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
                            @click="!isPassRoundKey('{{ $boardRoundKey }}') && openScaleSelect($event)"
                        >
                            <span
                                x-show="isPassRoundKey('{{ $boardRoundKey }}')"
                                class="block h-full w-full"
                                aria-hidden="true"
                            ></span>
                            <div x-show="!isPassRoundKey('{{ $boardRoundKey }}')">
                                <label class="sr-only">{{ __('Risk') }}</label>
                                <select
                                    class="working-board-scale-select h-full w-full cursor-pointer border-0 bg-transparent text-transparent shadow-none focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500/50"
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
                            class="working-board-name-cell working-board-col-name border-r border-slate-200 bg-white px-1 align-middle leading-none"
                        >
                            <div class="flex items-center justify-center gap-0.5">
                                @if ($isPassColumn)
                                    <a
                                        class="working-board-player-name working-board-player-name--pass min-w-0"
                                        :href="playerUrl(card)"
                                        x-text="boardName(card)"
                                    ></a>
                                @else
                                    <a
                                        class="working-board-player-name min-w-0 text-black underline decoration-slate-400 decoration-1 underline-offset-2 hover:decoration-black"
                                        :href="playerUrl(card)"
                                        x-text="boardName(card)"
                                    ></a>
                                @endif
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
                    x-show="roundPlayerCount('{{ $boardType }}', '{{ $boardRoundKey }}') === 0"
                    x-cloak
                    class="working-board-empty bg-slate-50/80"
                >
                    <td colspan="7" class="px-2 py-4 text-center text-[10px] font-medium leading-snug text-slate-500 normal-case">
                        @if ($isPassColumn)
                            {{ __('Drop passed players here or drag from targets above.') }}
                        @else
                            {{ __('Drop players here or use the search box.') }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
