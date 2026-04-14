@php
    $pitchCols = ['P', 'xwOBAcon', 'OBP', 'CH%', 'SwM%', 'IZ SwM%', 'BB%'];
@endphp

<x-player.ranger-trait-block :title="__('Pitch Coverage')" :note="$player->note_pitch_coverage">
        @foreach (['FB', 'SL', 'CH'] as $pitchLabel)
            <div class="min-w-0 overflow-x-auto">
                <table
                    class="w-full min-w-[36rem] table-fixed border-collapse border border-gray-800 text-center text-[calc(0.52rem/2)] font-[700] sm:min-w-0 sm:text-[calc(0.6rem/2)] [&_th]:align-middle [&_th]:text-center [&_th]:font-[700] [&_td]:align-middle [&_td]:text-center [&_td]:font-[700]"
                >
                    @include('components.player.ranger-traits.table-colgroup', ['cols' => 1 + count($pitchCols)])
                    <thead>
                        <tr class="bg-[#44546A] text-white">
                            <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                                {{ $pitchLabel }}
                            </th>
                            @foreach ($pitchCols as $col)
                                <th class="border border-gray-800 px-0.5 py-0.5 font-[700] sm:px-1 sm:py-1">
                                    {{ $col }}
                                </th>
                            @endforeach
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
                                @foreach (range(1, count($pitchCols)) as $i)
                                    @php
                                        $heat =
                                            ($pitchLabel === 'FB' && $i === 2) || ($pitchLabel === 'CH' && $i === 5)
                                                ? 'cf-value-high'
                                                : (($pitchLabel === 'SL' && $i === 4) || ($pitchLabel === 'FB' && $i === 6)
                                                    ? 'cf-value-low'
                                                    : null);
                                    @endphp
                                    <td
                                        class="border border-gray-800 px-0.5 py-0.5 sm:px-1 sm:py-1{{ $heat ? ' '.$heat : '' }}"
                                    >
                                        #N/A
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </x-player.ranger-trait-block>

    <x-player.ranger-trait-block :title="__('Swing')" :note="$player->note_swing" />
