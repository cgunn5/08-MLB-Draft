@php
    $cl = $rangerSheet['circuit_lonestar'] ?? [];
    $cp = $rangerSheet['circuit_pg'] ?? [];
    $ccl = ($rangerSheet['cell_heat'] ?? [])['circuit_lonestar'] ?? [];
@endphp
<x-player.ranger-trait-block
    class="flex min-h-0 min-w-0 flex-1 flex-col"
    dense
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
                        —
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
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
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">G</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PA</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">AVG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OBP</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SLG</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">OPS</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                @forelse ($cp as $pgRow)
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                        >
                            {{ $pgRow['year'] ?? '—' }}
                        </th>
                        <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['g'] ?? '—' }}</td>
                        <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['pa'] ?? '—' }}</td>
                        <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['avg'] ?? '—' }}</td>
                        <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['obp'] ?? '—' }}</td>
                        <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['slg'] ?? '—' }}</td>
                        <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pgRow['ops'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                        >
                            —
                        </th>
                        @for ($i = 0; $i < 6; $i++)
                            <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
                        @endfor
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
