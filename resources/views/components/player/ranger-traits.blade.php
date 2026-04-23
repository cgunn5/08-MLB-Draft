@props(['player'])

<section
    {{ $attributes->merge([
        'class' => 'flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden',
        'aria-label' => __('Ranger Traits'),
    ]) }}
>
    {{-- Three vertical bands; flex-1 + column scroll matches HS layout inside the NCAA profile pane. --}}
    <div
        class="grid min-h-0 min-w-0 flex-1 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] grid-rows-1 items-stretch gap-1.5 overflow-x-hidden sm:gap-2 md:gap-3 lg:gap-4 2xl:gap-5"
    >
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Performance and engine') }}"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col gap-1.5 sm:gap-2 md:gap-2.5">
                @include('components.player.ranger-traits.columns-left', ['player' => $player])
            </div>
        </div>
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Approach and left/right splits') }}"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col gap-1.5 sm:gap-2 md:gap-2.5">
                @include('components.player.ranger-traits.columns-middle', ['player' => $player])
            </div>
        </div>
        <div
            class="flex h-full min-h-0 min-w-0 flex-col overflow-x-hidden overflow-y-auto overscroll-y-contain pb-2 sm:pb-2.5"
            role="region"
            aria-label="{{ __('Pitch coverage and swing') }}"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col gap-1.5 sm:gap-2 md:gap-2.5">
                @include('components.player.ranger-traits.columns-right', ['player' => $player])
            </div>
        </div>
    </div>
</section>
