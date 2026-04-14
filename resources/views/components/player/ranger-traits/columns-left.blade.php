<x-player.ranger-trait-block :title="__('Performance')" :note="$player->note_performance">
        <div class="min-w-0 overflow-x-auto">
            <table
                class="w-full min-w-[36rem] table-fixed border-collapse border border-gray-800 text-center text-[calc(0.52rem/2)] font-[700] sm:min-w-0 sm:text-[calc(0.6rem/2)] [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                @include('components.player.ranger-traits.table-colgroup', ['cols' => 7])
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('NCAA') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">PA</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('xwOBA') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">xwOBAcon</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">OBP</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SLG</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">OPS</th>
                    </tr>
                </thead>
                <tbody class="bg-white text-black">
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                        >
                            2025
                        </th>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                    </tr>
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                        >
                            2024
                        </th>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                    </tr>
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                        >
                            2023
                        </th>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="min-w-0 overflow-x-auto">
            <table
                class="w-full min-w-[32rem] table-fixed border-collapse border border-gray-800 text-center text-[calc(0.52rem/2)] font-[700] sm:min-w-0 sm:text-[calc(0.6rem/2)] [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                @include('components.player.ranger-traits.table-colgroup', ['cols' => 7])
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            2024
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">G</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">PA</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">AVG</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">OBP</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">SLG</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">OPS</th>
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
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                    </tr>
                    <tr>
                        <th
                            scope="row"
                            class="border border-gray-800 bg-gray-200 px-0.5 py-0.5 font-[700] text-gray-900 sm:px-1 sm:py-1"
                        >
                            {{ __('CAPE') }}
                        </th>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        <td class="cf-value-high border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">—</td>
                        <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-player.ranger-trait-block>

    <x-player.ranger-trait-block :title="__('Engine')" :note="$player->note_engine">
        <div class="min-w-0 overflow-x-auto">
            <table
                class="w-full min-w-[40rem] table-fixed border-collapse border border-gray-800 text-center text-[calc(0.52rem/2)] font-[700] sm:min-w-0 sm:text-[calc(0.6rem/2)] [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
            >
                @include('components.player.ranger-traits.table-colgroup', ['cols' => 7])
                <thead>
                    <tr class="bg-[#44546A] text-white">
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('NCAA TM') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">HH%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">EV90</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('T# EV95') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">Barrel%</th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('BIP 110+') }}
                        </th>
                        <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                            {{ __('T# 105+ M') }}
                        </th>
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
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                            <td class="cf-value-low border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1">#N/A</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-player.ranger-trait-block>
