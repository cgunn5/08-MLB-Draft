<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('WORKING BOARD') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p>{{ __('PLACEHOLDER: DRAGGABLE PLAYER CARDS, GROUPS, SORT, AND FILTER WILL LIVE HERE.') }}</p>
                    <p class="text-sm text-gray-600">
                        {{ __('USE') }}
                        <code class="cf-value-high px-1 rounded">.cf-value-high</code>
                        {{ __('FOR STRONG / HIGH VALUES AND') }}
                        <code class="cf-value-low px-1 rounded">.cf-value-low</code>
                        {{ __('FOR WEAK / LOW VALUES.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
