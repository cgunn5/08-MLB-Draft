@props([
    'player',
    'rangerSheet' => [],
])

<section
    {{ $attributes->merge([
        'class' => 'flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden',
        'aria-label' => __('Ranger Traits'),
    ]) }}
>
    <div
        class="grid h-full min-h-0 min-w-0 flex-1 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] grid-rows-[minmax(0,1fr)] items-stretch gap-2.5 overflow-x-hidden sm:gap-3 md:gap-4 lg:gap-5 2xl:gap-6"
    >
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Circuit stats') }}"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                @include('components.player.ranger-traits-hs.columns-left', ['player' => $player, 'rangerSheet' => $rangerSheet])
            </div>
        </div>
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-1 sm:pb-1.5"
            role="region"
            aria-label="{{ __('Approach and impact') }}"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                @include('components.player.ranger-traits-hs.columns-middle', ['player' => $player, 'rangerSheet' => $rangerSheet])
            </div>
        </div>
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Adjustability and swing') }}"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                @include('components.player.ranger-traits-hs.columns-right', ['player' => $player, 'rangerSheet' => $rangerSheet])
            </div>
        </div>
    </div>
</section>
