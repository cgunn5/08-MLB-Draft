@php
    $engineRows = $rangerSheet['ncaa_engine_ncaa'] ?? [];
    if (! is_array($engineRows)) {
        $engineRows = [];
    }
    $heat = $rangerSheet['cell_heat'] ?? [];
    $engineHeat = $heat['ncaa_engine_ncaa'] ?? [];
    if (! is_array($engineHeat)) {
        $engineHeat = [];
    }
@endphp
<x-player.ranger-trait-block
    dense
    tightStack
    :wider-table-stack="true"
    :trim-header-top="true"
    :title="__('Engine / Impact')"
    :note="$player->note_engine"
>
    <div class="min-w-0 shrink-0 overflow-x-auto">
        <div class="ranger-traits-table-clip">
            <table
                class="hs-ranger-traits-table w-full min-w-0 table-fixed border-collapse border border-gray-800 text-center font-[700] leading-none [&_th]:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:min-w-0 [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                <colgroup>
                    <col span="8" style="width: 12.5%" />
                </colgroup>
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">{{ __('Year') }}</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV70</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV95</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">MEV</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">110+</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Brl%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">GB%</th>
                        <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">LD%</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @forelse ($engineRows as $idx => $er)
                        <tr>
                            <th scope="row" class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]">{{ $er['year'] ?? '—' }}</th>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['ev70'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['ev70'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['ev95'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['ev95'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['max_ev'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['max_ev'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['bip110'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['bip110'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['barrel_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['barrel_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['gb_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['gb_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                            <x-player.ranger-traits-hs.heat-td :heat="$engineHeat[$idx]['ld_pct'] ?? null" class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">{{ $er['ld_pct'] ?? '—' }}</x-player.ranger-traits-hs.heat-td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border border-gray-800 px-3 py-4 text-center text-gray-500 italic normal-case">{{ __('No NCAA engine rows from assigned data.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-player.ranger-trait-block>
