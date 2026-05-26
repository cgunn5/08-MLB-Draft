@php
    $blocks = $rangerSheet['ncaa_adjust_pitch'] ?? [];
@endphp
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :trim-header-top="true"
    :title="__('Adjustability')"
    :note="$player->note_pitch_coverage"
>
    <div class="flex min-w-0 flex-col gap-2.5 sm:gap-3">
        @foreach ($blocks as $block)
            @php
                $pitchLabel = $block['pitch'] ?? '—';
                $rows = $block['rows'] ?? [];
                $heats = $block['heat'] ?? [];
            @endphp
            <div class="min-w-0">
                <div class="min-w-0 overflow-x-auto">
                    <div class="ranger-traits-table-clip">
                        <table
                            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
                        >
                            <thead>
                                <tr class="bg-[#44546A] text-white">
                                    <th
                                        scope="col"
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        {{ $pitchLabel }}
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        P
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        BIPx
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        OPS
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        ISO
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        EV95
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        GB%
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        SwM%
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        IZSwM%
                                    </th>
                                    <th
                                        class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"
                                    >
                                        CH%
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white text-black">
                                @forelse ($rows as $ridx => $pr)
                                    @php($h = $heats[$ridx] ?? [])
                                    <tr>
                                        <th
                                            scope="row"
                                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                                        >
                                            {{ $pr['year'] ?? '—' }}
                                        </th>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['p'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['p'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['bipx'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['bipx'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['ops'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ \App\Support\HsRangerTraitsDisplay::formatThreeDecimals($pr['ops'] ?? null) }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['iso'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ \App\Support\HsRangerTraitsDisplay::formatThreeDecimals($pr['iso'] ?? null) }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['ev95'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['ev95'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['gb_pct'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['gb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['swm'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['swm'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['izswm'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['izswm'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                        <x-player.ranger-traits-hs.heat-td
                                            :heat="$h['ch_pct'] ?? null"
                                            class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                        >{{ $pr['ch_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                                    </tr>
                                @empty
                                    <tr>
                                        <th
                                            scope="row"
                                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                                        >
                                            —
                                        </th>
                                        @for ($i = 0; $i < 9; $i++)
                                            <td
                                                class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]"
                                            >
                                                —
                                            </td>
                                        @endfor
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-player.ranger-trait-block>
