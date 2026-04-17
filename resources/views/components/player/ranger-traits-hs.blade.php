@props([
    'player',
    'rangerSheet' => [],
])

<section
    {{ $attributes->merge([
        'class' => 'flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden',
        'aria-labelledby' => 'ranger-traits-hs-heading',
    ]) }}
>
    <div class="mb-4 flex shrink-0 items-center gap-2.5 sm:mb-5 sm:gap-3 md:mb-6 md:gap-4">
        <div
            class="h-px min-w-[1.25rem] flex-1 bg-gray-900 sm:h-0.5 sm:min-w-[1.5rem] md:min-w-[2rem]"
            aria-hidden="true"
        ></div>
        <h2
            id="ranger-traits-hs-heading"
            class="shrink-0 px-0.5 text-center font-[700] leading-none tracking-wide text-gray-900 text-[0.85rem] sm:text-[0.95rem] md:text-[1.05rem]"
        >
            {{ __('Ranger Traits') }}
        </h2>
        <div
            class="h-px min-w-[1.25rem] flex-1 bg-gray-900 sm:h-0.5 sm:min-w-[1.5rem] md:min-w-[2rem]"
            aria-hidden="true"
        ></div>
    </div>

    <div
        class="grid min-h-0 min-w-0 flex-1 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] grid-rows-1 items-stretch gap-1.5 overflow-x-hidden sm:gap-2 md:gap-3 lg:gap-4 2xl:gap-5"
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
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Approach and impact') }}"
        >
            @include('components.player.ranger-traits-hs.columns-middle', ['player' => $player, 'rangerSheet' => $rangerSheet])
        </div>
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Adjustability and swing') }}"
        >
            @include('components.player.ranger-traits-hs.columns-right', ['player' => $player, 'rangerSheet' => $rangerSheet])
        </div>
    </div>
</section>
