@props([
    'title',
    'note' => null,
])

<section
    {{ $attributes->merge([
        'class' =>
            'flex min-w-0 flex-col gap-2 rounded border border-gray-200 bg-white p-2.5 shadow-sm sm:gap-2.5 sm:p-3',
    ]) }}
>
    <div class="flex min-w-0 items-center gap-1.5 sm:gap-2">
        <div
            class="h-px min-w-[0.75rem] flex-1 bg-gray-900 sm:min-w-[1rem]"
            aria-hidden="true"
        ></div>
        <h3
            class="shrink-0 text-center font-[700] leading-none text-red-600 text-[calc(0.72rem/2)] sm:text-[calc(0.8rem/2)]"
        >
            {{ $title }}
        </h3>
        <div
            class="h-px min-w-[0.75rem] flex-1 bg-gray-900 sm:min-w-[1rem]"
            aria-hidden="true"
        ></div>
    </div>

    <div
        class="min-h-[2.75rem] border border-gray-500 bg-[#f2f6f9] px-2 py-2 text-center font-[700] leading-snug text-gray-700 text-[calc(0.65rem/2)] sm:min-h-[3.25rem] sm:px-3 sm:py-2.5 sm:text-[calc(0.75rem/2)]"
    >
        {{ $note ? $note : '#N/A' }}
    </div>

    @if (! $slot->isEmpty())
        <div class="min-w-0 space-y-2.5 border-t border-gray-100 pt-2.5 sm:space-y-3 sm:pt-3">
            {{ $slot }}
        </div>
    @endif
</section>
