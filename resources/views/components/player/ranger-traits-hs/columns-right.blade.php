@php
    $adj = $rangerSheet['adjust_pitch'] ?? [];
    $ops = $rangerSheet['adjust_ops_split'] ?? [];
    $_ch = $rangerSheet['cell_heat'] ?? [];
    $cadj = $_ch['adjust_pitch'] ?? [];
    $cops = $_ch['adjust_ops_split'] ?? [];
@endphp
<div class="flex min-h-0 min-w-0 flex-1 flex-col gap-1.5 sm:gap-2 md:gap-2.5">
<div class="min-w-0 shrink-0">
<x-player.ranger-trait-block dense :title="__('Adjustability')" :note="$player->note_pitch_coverage">
    <div class="min-w-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]"></th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">P</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">xwOBAcon</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV95</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwM</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">IZSwM</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OwDec</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">BB%</th>
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
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['xwobacon'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['xwobacon'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['ev95'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['ev95'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['swm'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['swm'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['izswm'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['izswm'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['owdec'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['owdec'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$cadj[$idx]['bb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['bb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    </tr>
                @empty
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                        >
                            —
                        </th>
                        @for ($i = 0; $i < 7; $i++)
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
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['pa_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ops['pa_vs_r'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['ops_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ops['ops_vs_r'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['pa_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ops['pa_vs_l'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cops['ops_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ops['ops_vs_l'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
</div>

<div class="min-w-0 shrink-0">
<x-player.ranger-trait-block dense :title="__('Swing')" :note="$player->note_swing" />
</div>
</div>
