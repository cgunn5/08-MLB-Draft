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
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        FB
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">293</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">82</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">96.4</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">81</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">72</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">23</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">65</td>
                </tr>
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        BB
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">—</td>
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
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('OPS (R)') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">
                        {{ __('OPS (L)') }}
                    </th>
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
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">63</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">1.068</td>
                    <td class="cf-value-low border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.288</td>
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
