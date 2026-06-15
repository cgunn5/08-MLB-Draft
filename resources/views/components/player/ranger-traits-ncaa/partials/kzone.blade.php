@php
    $ancaRows = $rangerSheet['ncaa_approach_ncaa'] ?? [];
    if (! is_array($ancaRows)) {
        $ancaRows = [];
    }
    $huntRows = $rangerSheet['ncaa_hunt'] ?? [];
    if (! is_array($huntRows)) {
        $huntRows = [];
    }
    $_ch = $rangerSheet['cell_heat'] ?? [];
    $cancaRows = $_ch['ncaa_approach_ncaa'] ?? [];
    if (! is_array($cancaRows)) {
        $cancaRows = [];
    }
    $huntHeat = $_ch['ncaa_hunt'] ?? [];
    if (! is_array($huntHeat)) {
        $huntHeat = [];
    }
@endphp
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :trim-header-top="true"
    :title="__('K-Zone Control')"
    :note="$player->note_approach_miss"
>
    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
            <table class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]">
                <colgroup>
                    <col span="12" style="width: 8.333333%" />
                </colgroup>
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('Year') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">K%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">aK%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">BB%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">aBB%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">K/BB</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">aK/BB</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SW%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">CH%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwDec</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwM%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">IZSwM%</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @forelse ($ancaRows as $idx => $r)
                        <tr>
                            <th scope="row" class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]">{{ $r['year'] ?? '—' }}</th>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['k_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['k_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['ak_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['ak_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['bb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['bb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['abb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['abb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['k_bb'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['k_bb'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['ak_bb'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['ak_bb'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['sw_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['sw_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['ch_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['ch_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['swdec'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['swdec'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['swm_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['swm_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cancaRows[$idx]['iz_swm_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['iz_swm_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="border border-gray-800 px-3 py-4 text-center text-gray-500 italic normal-case">{{ __('No NCAA K-Zone rows from assigned data.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="min-w-0 shrink-0 overflow-x-auto mt-3">
        <div class="ranger-traits-table-clip">
            <table class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]">
                <colgroup>
                    <col span="7" style="width: 14.285714%" />
                </colgroup>
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('Year') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Cov%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Hunt%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">&lt;2K Hunt%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">NZ xOPS</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">oNZ xOPS</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Δ</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @forelse ($huntRows as $idx => $r)
                        <tr>
                            <th scope="row" class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]">{{ $r['year'] ?? '—' }}</th>
                            <x-player.ranger-traits-hs.heat-td :heat="$huntHeat[$idx]['cov_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['cov_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$huntHeat[$idx]['hunt_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['hunt_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$huntHeat[$idx]['lt2k_hunt_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['lt2k_hunt_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$huntHeat[$idx]['nz_xops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['nz_xops'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$huntHeat[$idx]['onz_xops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['onz_xops'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$huntHeat[$idx]['delta'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $r['delta'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-800 px-3 py-4 text-center text-gray-500 italic normal-case">{{ __('No Hunt% rows from assigned data.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-player.ranger-trait-block>
