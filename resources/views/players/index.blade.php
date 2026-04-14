@php
    $rankLabel = static function (?int $v): string {
        return $v !== null ? (string) $v : '—';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('PLAYER LIST') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800 border border-green-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <p class="text-sm text-gray-600">
                        {{ __('Master list from aggregate board data. Add players here; NCAA profiles link when available.') }}
                    </p>

                    <form method="GET" action="{{ route('players.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                        <div class="flex-1 w-full">
                            <x-input-label for="q" :value="__('SEARCH')" />
                            <x-text-input
                                id="q"
                                class="block mt-1 w-full"
                                type="text"
                                name="q"
                                :value="$search"
                                placeholder="{{ __('Name or school') }}"
                            />
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button type="submit">{{ __('SEARCH') }}</x-primary-button>
                            @if ($search !== '')
                                <a href="{{ route('players.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                    {{ __('CLEAR') }}
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">{{ __('ADD PLAYER') }}</h3>
                        <form method="POST" action="{{ route('players.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @csrf

                            <div>
                                <x-input-label for="last_name" :value="__('LAST NAME')" />
                                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="first_name" :value="__('FIRST NAME')" />
                                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required />
                                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="player_pool" :value="__('POOL')" />
                                <select id="player_pool" name="player_pool" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="ncaa" @selected(old('player_pool', 'ncaa') === 'ncaa')>{{ __('NCAA') }}</option>
                                    <option value="hs" @selected(old('player_pool') === 'hs')>{{ __('HS') }}</option>
                                </select>
                                <x-input-error :messages="$errors->get('player_pool')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="school" :value="__('SCHOOL')" />
                                <x-text-input id="school" class="block mt-1 w-full" type="text" name="school" :value="old('school')" />
                                <x-input-error :messages="$errors->get('school')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="position" :value="__('POS')" />
                                <x-text-input id="position" class="block mt-1 w-full" type="text" name="position" :value="old('position')" maxlength="32" />
                                <x-input-error :messages="$errors->get('position')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="aggregate_rank" :value="__('AGG RK')" />
                                <x-text-input id="aggregate_rank" class="block mt-1 w-full" type="number" name="aggregate_rank" :value="old('aggregate_rank')" min="1" />
                                <x-input-error :messages="$errors->get('aggregate_rank')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="aggregate_score" :value="__('AGG')" />
                                <x-text-input id="aggregate_score" class="block mt-1 w-full" type="text" inputmode="decimal" name="aggregate_score" :value="old('aggregate_score')" />
                                <x-input-error :messages="$errors->get('aggregate_score')" class="mt-2" />
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <x-primary-button>{{ __('ADD TO LIST') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($players->isEmpty())
                        <p class="text-sm text-gray-600">{{ __('NO PLAYERS MATCH THIS SEARCH.') }}</p>
                    @else
                        <div class="overflow-x-auto -mx-2 sm:mx-0">
                            <table class="min-w-full text-sm text-left">
                                <thead>
                                    <tr class="border-b border-gray-200 text-xs uppercase text-gray-500">
                                        <th class="py-2 pr-3 whitespace-nowrap">{{ __('RK') }}</th>
                                        <th class="py-2 pr-3 whitespace-nowrap">{{ __('PLAYER') }}</th>
                                        <th class="py-2 pr-3 whitespace-nowrap">{{ __('POOL') }}</th>
                                        <th class="py-2 pr-3 min-w-[10rem]">{{ __('SCHOOL') }}</th>
                                        <th class="py-2 pr-3 whitespace-nowrap">{{ __('POS') }}</th>
                                        <th class="py-2 pr-3 whitespace-nowrap">{{ __('AGG') }}</th>
                                        <th class="py-2 pr-2 whitespace-nowrap text-center">{{ __('MDL') }}</th>
                                        <th class="py-2 pr-2 whitespace-nowrap text-center">{{ __('MLB') }}</th>
                                        <th class="py-2 pr-2 whitespace-nowrap text-center">{{ __('ESPN') }}</th>
                                        <th class="py-2 pr-2 whitespace-nowrap text-center">{{ __('LAW') }}</th>
                                        <th class="py-2 pr-2 whitespace-nowrap text-center">{{ __('FG') }}</th>
                                        <th class="py-2 pr-2 whitespace-nowrap text-center">{{ __('BA') }}</th>
                                        <th class="py-2 pl-2 whitespace-nowrap">{{ __('PROFILE') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($players as $player)
                                        @php
                                            $r = $player->source_ranks ?? [];
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2 pr-3 tabular-nums text-gray-600">{{ $rankLabel($player->aggregate_rank) }}</td>
                                            <td class="py-2 pr-3 font-medium text-gray-900 whitespace-nowrap">
                                                {{ strtoupper($player->last_name) }}, {{ strtoupper($player->first_name) }}
                                            </td>
                                            <td class="py-2 pr-3 uppercase text-gray-600 whitespace-nowrap">{{ $player->player_pool }}</td>
                                            <td class="py-2 pr-3 text-gray-700">{{ $player->school ?? '—' }}</td>
                                            <td class="py-2 pr-3 text-gray-700 whitespace-nowrap">{{ $player->position ?? '—' }}</td>
                                            <td class="py-2 pr-3 tabular-nums text-gray-700 whitespace-nowrap">
                                                {{ $player->aggregate_score !== null ? number_format((float) $player->aggregate_score, 1) : '—' }}
                                            </td>
                                            <td class="py-2 pr-2 text-center tabular-nums text-gray-600">{{ $rankLabel($r['model'] ?? null) }}</td>
                                            <td class="py-2 pr-2 text-center tabular-nums text-gray-600">{{ $rankLabel($r['mlb'] ?? null) }}</td>
                                            <td class="py-2 pr-2 text-center tabular-nums text-gray-600">{{ $rankLabel($r['espn'] ?? null) }}</td>
                                            <td class="py-2 pr-2 text-center tabular-nums text-gray-600">{{ $rankLabel($r['law'] ?? null) }}</td>
                                            <td class="py-2 pr-2 text-center tabular-nums text-gray-600">{{ $rankLabel($r['fangraphs'] ?? null) }}</td>
                                            <td class="py-2 pr-2 text-center tabular-nums text-gray-600">{{ $rankLabel($r['ba'] ?? null) }}</td>
                                            <td class="py-2 pl-2 whitespace-nowrap">
                                                @if ($player->player_pool === 'ncaa')
                                                    <a href="{{ route('ncaa.players.show', $player) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('OPEN') }}</a>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
