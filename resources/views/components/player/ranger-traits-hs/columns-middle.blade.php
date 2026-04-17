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
                        {{ __('LONESTAR') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">BB%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">K%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Sw%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">SwDec</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">Ch%</th>
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
                        2024
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">12.9%</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">10.3%</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">20.2%</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">20</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">65</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">95</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">81</td>
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
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">xwOBAcon</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV75</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">EV95</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('BIP 100+') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('BIP 105+') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">NITRO%</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">HH%</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black">
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        2024
                    </th>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">99</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">85</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">96.3</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">0</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">0</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">0</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">63</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
</div>
</div>
