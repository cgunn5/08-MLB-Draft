@php
    $pltRows = $rangerSheet['ncaa_platoon'] ?? [];
    if (! is_array($pltRows)) {
        $pltRows = [];
    }
    $_ch = $rangerSheet['cell_heat'] ?? [];
    $cpltRows = $_ch['ncaa_platoon'] ?? [];
    if (! is_array($cpltRows)) {
        $cpltRows = [];
    }
@endphp
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :trim-header-top="true"
    :title="__('Platoon')"
    :note="$player->note_left_right"
>
    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
            <table
                class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                <colgroup>
                    <col span="7" style="width: 14.285714%" />
                </colgroup>
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('Year') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('OPS vs. R') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('ISO vs. R') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('K/BB vs. R') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('OPS vs. L') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('ISO vs. L') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('K/BB vs. L') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @forelse ($pltRows as $idx => $pr)
                        <tr>
                            <th scope="row" class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]">{{ $pr['year'] ?? '—' }}</th>
                            <x-player.ranger-traits-hs.heat-td :heat="$cpltRows[$idx]['ops_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['ops_vs_r'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cpltRows[$idx]['iso_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['iso_vs_r'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cpltRows[$idx]['k_bb_vs_r'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['k_bb_vs_r'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cpltRows[$idx]['ops_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['ops_vs_l'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cpltRows[$idx]['iso_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['iso_vs_l'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$cpltRows[$idx]['k_bb_vs_l'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $pr['k_bb_vs_l'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="border border-gray-800 px-3 py-4 text-center text-gray-500 italic normal-case">{{ __('No NCAA platoon rows from assigned data.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-player.ranger-trait-block>
