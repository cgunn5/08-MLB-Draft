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
                        {{ __('LONESTAR') }}
                    </th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">G</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">PA</th>
                    <th class="border border-gray-800 px-0.5 py-[0.102rem] font-[700] sm:py-[0.198rem]">xwOBAcon</th>
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
                        2024
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">26</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">79</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">99</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.427</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.389</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.815</td>
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
                        2024
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
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        2024
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">26</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">78</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.418</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.586</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.642</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">1.148</td>
                </tr>
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        2023
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">36</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">102</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.379</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.482</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.690</td>
                    <td class="cf-value-high border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">1.171</td>
                </tr>
                <tr>
                    <th
                        scope="row"
                        class="border border-gray-800 bg-gray-200 px-0.5 py-[0.204rem] font-[700] text-gray-900 sm:px-1 sm:py-[0.396rem]"
                    >
                        2022
                    </th>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">12</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">25</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.385</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.556</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">.462</td>
                    <td class="border border-gray-800 px-0.5 py-[0.102rem] sm:py-[0.198rem]">1.017</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</x-player.ranger-trait-block>
