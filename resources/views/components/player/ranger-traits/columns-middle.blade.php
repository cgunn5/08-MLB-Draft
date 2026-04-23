<x-player.ranger-trait-block dense :tight-stack="true" :title="__('Approach / Miss')" :note="$player->note_approach_miss">
        <div class="min-w-0 overflow-x-auto">
            <div class="ranger-traits-table-clip">
            <table
                class="ncaa-ranger-traits-table w-full min-w-[44rem] table-fixed border-collapse border border-gray-800 text-center font-[700] sm:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                @include('components.player.ranger-traits.table-colgroup', ['cols' => 8])
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('NCAA') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">BB%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">K%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">Sw%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SwOOC</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">CH%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SwM%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">IZ SwM%</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @foreach (['2025', '2024', '2023'] as $yr)
                        <tr>
                            <th
                                scope="row"
                                class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                            >
                                {{ $yr }}
                            </th>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>

        <div class="min-w-0 overflow-x-auto">
            <div class="ranger-traits-table-clip">
            <table
                class="ncaa-ranger-traits-table w-full min-w-[36rem] table-fixed border-collapse border border-gray-800 text-center font-[700] sm:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                @include('components.player.ranger-traits.table-colgroup', ['cols' => 8])
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            2024
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">BB%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">K%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">Sw%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SwOOC</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">CH%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SwM%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">IZ SwM%</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                        >
                            {{ __('TUSA') }}
                        </th>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                    </tr>
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                        >
                            {{ __('CAPE') }}
                        </th>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </x-player.ranger-trait-block>

    <x-player.ranger-trait-block dense :tight-stack="true" :title="__('Left / Right')" :note="$player->note_left_right">
        <div class="min-w-0 overflow-x-auto">
            <div class="ranger-traits-table-clip">
            <table
                class="ncaa-ranger-traits-table w-full min-w-[40rem] table-fixed border-collapse border border-gray-800 text-center font-[700] sm:min-w-0 [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                @include('components.player.ranger-traits.table-colgroup', ['cols' => 8])
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('NCAA') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('OBP') }} Δ
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">OBP R</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">OBP L</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">ISO R</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">ISO L</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SwM R</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SwM L</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    @foreach (['2025', '2024', '2023'] as $yr)
                        <tr>
                            <th
                                scope="row"
                                class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                            >
                                {{ $yr }}
                            </th>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                            <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </x-player.ranger-trait-block>
