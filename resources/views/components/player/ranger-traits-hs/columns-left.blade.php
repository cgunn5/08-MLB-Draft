@php
    $cl = $rangerSheet['circuit_lonestar'] ?? [];
    $ct = $rangerSheet['circuit_tusa'] ?? [];
    $cp = $rangerSheet['circuit_pg'] ?? [];
    $heat = $rangerSheet['cell_heat'] ?? [];
    $ccl = $heat['circuit_lonestar'] ?? [];
    $cct = $heat['circuit_tusa'] ?? [];
    $cpHeat = $heat['circuit_pg'] ?? [];
@endphp
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :title="__('Circuit Stats')"
    :note="$player->note_performance"
>
    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('Year') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">G</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PA</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">AVG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OBP</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SLG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OPS</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        {{ $cl['year'] ?? '—' }}
                    </th>
                    <x-player.ranger-traits-hs.heat-td :heat="$ccl['g'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $cl['g'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$ccl['pa'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $cl['pa'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$ccl['avg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $cl['avg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$ccl['obp'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $cl['obp'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$ccl['slg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $cl['slg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$ccl['ops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $cl['ops'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('TUSA') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PA</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">AVG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OBP</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SLG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OPS</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        {{ $ct['year'] ?? '—' }}
                    </th>
                    <x-player.ranger-traits-hs.heat-td :heat="$cct['pa'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ct['pa'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cct['avg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ct['avg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cct['obp'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ct['obp'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cct['slg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ct['slg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    <x-player.ranger-traits-hs.heat-td :heat="$cct['ops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $ct['ops'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
        <table
            class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse [&_th]:w-[1%] [&_td]:w-[1%] border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
        >
            <thead>
                <tr class="bg-[#44546A] text-white">
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PA</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OPS</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">AVG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OBP</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SLG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">ISO</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">BB%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">K%</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                @forelse ($cp as $idx => $pgRow)
                    @php
                        $rowHeat = is_array($cpHeat) && isset($cpHeat[$idx]) && is_array($cpHeat[$idx]) ? $cpHeat[$idx] : [];
                    @endphp
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                        >
                            {{ $pgRow['year'] ?? '—' }}
                        </th>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['pa'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['pa'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['ops'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['ops'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['avg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['avg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['obp'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['obp'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['slg'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['slg'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['iso'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['iso'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['bb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['bb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        <x-player.ranger-traits-hs.heat-td :heat="$rowHeat['k_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['k_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                    </tr>
                @empty
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                        >
                            —
                        </th>
                        @for ($i = 0; $i < 8; $i++)
                            <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                        @endfor
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
