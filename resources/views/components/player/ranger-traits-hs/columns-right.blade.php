@php
    $adj = $rangerSheet['adjust_pitch'] ?? [];
    $ops = $rangerSheet['adjust_ops_split'] ?? [];
    $_ch = $rangerSheet['cell_heat'] ?? [];
    $cadj = $_ch['adjust_pitch'] ?? [];
    $cops = $_ch['adjust_ops_split'] ?? [];
@endphp
<div class="flex min-h-0 min-w-0 flex-col gap-2 sm:gap-2.5 md:gap-3">
<div class="min-w-0 shrink-0">
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :title="__('Adjustability')"
    :note="$player->note_pitch_coverage"
>
    <div class="min-w-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"></th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">P</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">BIPx</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OPS</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">ISO</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV95</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">GB%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwM%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">IZSwM%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">CH%</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                @forelse ($adj as $idx => $pr)
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                        >
                            {{ $pr['pitch'] ?? '—' }}
                        </th>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['p'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['p'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['bipx'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['bipx'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['ops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ \App\Support\HsRangerTraitsDisplay::formatThreeDecimals($pr['ops'] ?? null) }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['iso'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ \App\Support\HsRangerTraitsDisplay::formatThreeDecimals($pr['iso'] ?? null) }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['ev95'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['ev95'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['gb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['gb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['swm'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['swm'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['izswm'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['izswm'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['ch_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['ch_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
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
                            <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                        @endfor
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="min-w-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('PA vs. R') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('OPS vs. R') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('PA vs. L') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('OPS vs. L') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['pa_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.204rem] sm:py-[0.396rem]">{{ $ops['pa_vs_r'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['ops_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.204rem] sm:py-[0.396rem]">{{ \App\Support\HsRangerTraitsDisplay::formatThreeDecimals($ops['ops_vs_r'] ?? null) }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['pa_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.204rem] sm:py-[0.396rem]">{{ $ops['pa_vs_l'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['ops_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.204rem] sm:py-[0.396rem]">{{ \App\Support\HsRangerTraitsDisplay::formatThreeDecimals($ops['ops_vs_l'] ?? null) }}</x-player.ranger-traits-hs.heat-td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
</div>

<div class="min-w-0 shrink-0">
<x-player.ranger-trait-block dense tightStack :wider-table-stack="true" :title="__('Swing')" :note="$player->note_swing" />
</div>
</div>
