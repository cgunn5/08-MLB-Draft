<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('NCAA / JUCO DASHBOARD') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($players->isEmpty())
                        <p>{{ __('NO COLLEGIATE PLAYERS YET. RUN') }} <code class="text-sm bg-gray-100 px-1 rounded">php artisan migrate --seed</code>.</p>
                    @else
                        <p class="text-sm text-gray-600 mb-4">{{ __('OPEN A PROFILE:') }}</p>
                        <ul class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                            @foreach ($players as $player)
                                <li>
                                    <a
                                        href="{{ route('ncaa.players.show', $player) }}"
                                        class="block px-4 py-3 hover:bg-gray-50 text-gray-900 font-medium"
                                    >
                                        {{ strtoupper($player->last_name) }}, {{ strtoupper($player->first_name) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
