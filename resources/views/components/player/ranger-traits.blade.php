@props(['player'])

<section
    {{ $attributes->merge([
        'class' => 'min-w-0',
        'aria-labelledby' => 'ranger-traits-heading',
    ]) }}
>
    <div class="mb-4 flex items-center gap-2 sm:mb-5 sm:gap-3">
        <div class="h-px min-w-[1.5rem] flex-1 bg-gray-900 sm:min-w-[2rem]" aria-hidden="true"></div>
        <h2
            id="ranger-traits-heading"
            class="shrink-0 font-[700] text-gray-900 text-[calc(0.9rem/2)] sm:text-[calc(1.05rem/2)] md:text-[calc(1.125rem/2)]"
        >
            {{ __('Ranger Traits') }}
        </h2>
        <div class="h-px min-w-[1.5rem] flex-1 bg-gray-900 sm:min-w-[2rem]" aria-hidden="true"></div>
    </div>

    {{-- Three vertical bands (always side-by-side like the reference board); tables scroll inside each column. --}}
    <div
        class="grid min-w-0 grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)] items-stretch gap-2 sm:gap-3 md:gap-5 lg:gap-6"
    >
        <div
            class="flex min-h-0 min-w-0 flex-col justify-between gap-5 md:h-full md:gap-6"
            role="region"
            aria-label="{{ __('Performance and engine') }}"
        >
            @include('components.player.ranger-traits.columns-left', ['player' => $player])
        </div>
        <div
            class="flex min-h-0 min-w-0 flex-col justify-between gap-5 md:h-full md:gap-6"
            role="region"
            aria-label="{{ __('Approach and left/right splits') }}"
        >
            @include('components.player.ranger-traits.columns-middle', ['player' => $player])
        </div>
        <div
            class="flex min-h-0 min-w-0 flex-col justify-between gap-5 md:h-full md:gap-6"
            role="region"
            aria-label="{{ __('Pitch coverage and swing') }}"
        >
            @include('components.player.ranger-traits.columns-right', ['player' => $player])
        </div>
    </div>
</section>
