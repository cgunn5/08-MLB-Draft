@php
    $ap = $rangerSheet['approach_lonestar'] ?? [];
    $im = $rangerSheet['impact'] ?? [];
    $ibb = $rangerSheet['impact_batted_ball'] ?? [];
    $_ch = $rangerSheet['cell_heat'] ?? [];
    $cap = $_ch['approach_lonestar'] ?? [];
    $cim = $_ch['impact'] ?? [];
    $cibb = $_ch['impact_batted_ball'] ?? [];
@endphp
<div class="flex min-h-0 min-w-0 flex-1 flex-col gap-1.5 sm:gap-2 md:gap-2.5">
<div class="min-w-0 shrink-0">
<x-player.ranger-trait-block dense :title="__('Approach & Miss')" :note="$player->note_approach_miss">
    <div class="min-w-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('Year') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">BB%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">K%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Sw%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwDec</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Ch%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PPA</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwM%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">IZ SwM%</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        {{ $ap['year'] ?? '—' }}
                    </th>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['bb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['bb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['k_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['k_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['sw_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['sw_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['swdec'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['swdec'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['ch_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['ch_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['ppa'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['ppa'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['swm_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['swm_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cap['iz_swm_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ap['iz_swm_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
</div>

<div class="min-w-0 shrink-0">
<x-player.ranger-trait-block dense :title="__('Impact / Damage')" :note="$player->note_engine">
    <div class="min-w-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('YEAR') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">ISO</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV70</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV95</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">MaxEV</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('BIP 100+') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('BIP 105+') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('Nitro%') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('TX Brl%') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        {{ $im['year'] ?? '—' }}
                    </th>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['iso'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['iso'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['ev70'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['ev70'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['ev95'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['ev95'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['max_ev'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['max_ev'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['bip100'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['bip100'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['bip105'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['bip105'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['barrel_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['barrel_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cim['tx_barrel_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $im['tx_barrel_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                </tr>
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
                        {{ __('YEAR') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">GB%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">FB%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">LD%</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        {{ $ibb['year'] ?? '—' }}
                    </th>
                    <x-player.ranger-traits-hs.heat-td :heat="$cibb['gb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ibb['gb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cibb['fb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ibb['fb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cibb['ld_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ibb['ld_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
</div>
</div>
