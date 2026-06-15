@php
    $ncaaPlayers = $ncaaPlayers ?? collect();
    $hardContactVisualsByPlayerId = $hardContactVisualsByPlayerId ?? collect();
@endphp

<div class="space-y-4 p-4 sm:p-6 text-gray-900">
    <p class="text-sm text-gray-600 normal-case">
        {{ __('Upload plate heat map and strike-zone hard contact screenshots per NCAA player. Images appear on that player\'s NCAA profile header when you change the selected name.') }}
    </p>

    @if ($ncaaPlayers->isEmpty())
        <p class="text-sm text-gray-600 normal-case">
            {{ __('No NCAA players yet. Add players from the player list first.') }}
        </p>
    @else
        <div class="overflow-x-auto rounded-md border border-gray-200 -mx-2 sm:mx-0">
            <table class="min-w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 uppercase tracking-wide border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-2 font-semibold whitespace-nowrap">{{ __('PLAYER') }}</th>
                        <th class="px-3 py-2 font-semibold whitespace-nowrap">{{ __('PLATE HEAT MAP') }}</th>
                        <th class="px-3 py-2 font-semibold whitespace-nowrap">{{ __('ZONE HARD CONTACT') }}</th>
                        <th class="px-3 py-2 font-semibold whitespace-nowrap">{{ __('ACTIONS') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800 bg-white">
                    @foreach ($ncaaPlayers as $ncaaPlayer)
                        @php
                            /** @var \App\Models\NcaaPlayerHardContactVisual|null $visual */
                            $visual = $hardContactVisualsByPlayerId->get($ncaaPlayer->id);
                            $plateUrl = $visual?->plateHeatmapUrl();
                            $zoneUrl = $visual?->zonePitchMapUrl();
                            $playerLabel = strtoupper($ncaaPlayer->last_name).', '.strtoupper($ncaaPlayer->first_name);
                        @endphp
                        <tr>
                            <td class="px-3 py-3 align-top font-semibold whitespace-nowrap">
                                {{ $playerLabel }}
                            </td>
                            <td class="px-3 py-3 align-top">
                                @if (filled($plateUrl))
                                    <img
                                        src="{{ $plateUrl }}"
                                        alt="{{ __('Plate heat map preview for :player', ['player' => $playerLabel]) }}"
                                        class="mb-2 h-20 w-auto rounded border border-gray-200 bg-white object-contain"
                                    />
                                @else
                                    <p class="mb-2 text-gray-500 italic normal-case">{{ __('No image') }}</p>
                                @endif
                                <input
                                    form="hard-contact-form-{{ $ncaaPlayer->id }}"
                                    type="file"
                                    name="plate_heatmap"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="block w-full max-w-xs text-xs text-gray-700 file:mr-2 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-2 file:py-1 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-50"
                                />
                            </td>
                            <td class="px-3 py-3 align-top">
                                @if (filled($zoneUrl))
                                    <img
                                        src="{{ $zoneUrl }}"
                                        alt="{{ __('Zone pitch map preview for :player', ['player' => $playerLabel]) }}"
                                        class="mb-2 h-20 w-auto rounded border border-gray-200 bg-white object-contain"
                                    />
                                @else
                                    <p class="mb-2 text-gray-500 italic normal-case">{{ __('No image') }}</p>
                                @endif
                                <input
                                    form="hard-contact-form-{{ $ncaaPlayer->id }}"
                                    type="file"
                                    name="zone_pitch_map"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="block w-full max-w-xs text-xs text-gray-700 file:mr-2 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-2 file:py-1 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-50"
                                />
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                <form
                                    id="hard-contact-form-{{ $ncaaPlayer->id }}"
                                    method="POST"
                                    action="{{ route('ncaa-data-sources.hard-contact-visuals.update', $ncaaPlayer) }}"
                                    enctype="multipart/form-data"
                                    class="space-y-2"
                                >
                                    @csrf
                                    <x-primary-button class="!text-xs">
                                        {{ __('SAVE IMAGES') }}
                                    </x-primary-button>
                                </form>
                                <div class="mt-2 flex flex-col gap-1">
                                    @if (filled($plateUrl))
                                        <form
                                            method="POST"
                                            action="{{ route('ncaa-data-sources.hard-contact-visuals.destroy', [$ncaaPlayer, 'plate']) }}"
                                            onsubmit="return confirm(@json(__('Remove plate heat map for this player?')))"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-xs font-semibold uppercase tracking-wide text-red-700 hover:text-red-900"
                                            >
                                                {{ __('REMOVE PLATE') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if (filled($zoneUrl))
                                        <form
                                            method="POST"
                                            action="{{ route('ncaa-data-sources.hard-contact-visuals.destroy', [$ncaaPlayer, 'zone']) }}"
                                            onsubmit="return confirm(@json(__('Remove zone hard contact map for this player?')))"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-xs font-semibold uppercase tracking-wide text-red-700 hover:text-red-900"
                                            >
                                                {{ __('REMOVE ZONE') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
