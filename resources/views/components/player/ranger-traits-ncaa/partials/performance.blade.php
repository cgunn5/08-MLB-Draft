@php
    $rows = $rangerSheet['ncaa_perf_ncaa'] ?? [];
    if (! is_array($rows)) {
        $rows = [];
    }
    $heat = $rangerSheet['cell_heat'] ?? [];
    $rowHeat = $heat['ncaa_perf_ncaa'] ?? [];
@endphp
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :trim-header-top="true"
    :title="__('Performance')"
    :note="$player->note_performance"
>
    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
            <table
                class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                <colgroup>
                    <col span="9" style="width: 11.111111%" />
                </colgroup>
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('Year') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PA</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">AVG</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SLG</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">ISO</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OPS</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">xwOBA</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">wOBA</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">awOBA</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @forelse ($rows as $idx => $r)
                        <tr>
                            <th scope="row" class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]">{{ $r['year'] ?? '—' }}</th>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['pa'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['pa'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['avg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['avg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['slg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['slg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['iso'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['iso'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['ops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['ops'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['xwoba'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['xwoba'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['woba'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['woba'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$rowHeat[$idx]['awoba'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['awoba'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="border border-gray-800 px-3 py-4 text-center text-gray-500 italic normal-case">{{ __('No NCAA performance rows from assigned data.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-player.ranger-trait-block>
