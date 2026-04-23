@props([
    'title',
    'note' => null,
    'comfortable' => false,
    'fillHeight' => false,
    'dense' => false,
    'tightStack' => false,
    /** When set with tightStack, increases gaps between stacked tables (~15%). HS dashboard only. */
    'widerTableStack' => false,
])

@php
    $tightStack = $dense && filter_var($tightStack, FILTER_VALIDATE_BOOLEAN);
    $widerTableStack = $tightStack && filter_var($widerTableStack, FILTER_VALIDATE_BOOLEAN);
    $titleClass = $comfortable
        ? 'text-xs sm:text-sm md:text-base'
        : ($dense
            /* Sizes: .ranger-traits-dense-pane h3 in app.css (+15% vs 0.72 / 0.8 / 0.875rem). */
            ? ''
            : 'text-[calc(0.72rem/2)] sm:text-[calc(0.8rem/2)]');
    if ($dense) {
        /* Take strip heights: .dense-ranger-traits-take / .ncaa-ranger-traits-take in app.css (px; Tailwind often omits min-h from PHP-only Blade strings). */
        $noteDensePad = $tightStack
            ? 'px-1 py-2 sm:px-1 sm:py-2.5 md:px-1.5 md:py-3 '
            : 'px-1 py-px sm:px-1 sm:py-0.5 md:px-1.5 md:py-1 ';
        $noteDenseTakeClass = $tightStack ? ' ncaa-ranger-traits-take' : ' dense-ranger-traits-take';
        $noteWrapClass =
            'shrink-0 flex items-center justify-center app-outline-soft bg-[#f2f6f9] '.
            $noteDensePad.
            'text-center font-sans font-[700] leading-snug text-gray-700'.
            $noteDenseTakeClass;
    } else {
        $noteClass = $comfortable
            ? 'min-h-[2.75rem] text-xs sm:min-h-[3.5rem] sm:text-sm md:text-[0.9375rem]'
            : 'min-h-[2.75rem] text-[calc(0.65rem/2)] sm:min-h-[3.25rem] sm:text-[calc(0.75rem/2)]';
        $noteWrapClass =
            'shrink-0 app-outline-soft bg-[#f2f6f9] px-2 py-2 text-center font-[700] leading-snug text-gray-700 sm:px-3 sm:py-2.5 '.$noteClass;
    }
    if ($fillHeight) {
        if ($dense) {
            $slotWrapClass = $tightStack
                ? 'flex min-h-0 flex-1 flex-col justify-between gap-y-1.5 pt-2 sm:gap-y-2 sm:pt-2.5 md:gap-y-2.5 md:pt-3 lg:gap-y-2.5'
                : 'flex min-h-0 flex-1 flex-col justify-between gap-y-3 pt-3 sm:gap-y-4 sm:pt-4 md:gap-y-5 md:pt-5 lg:gap-y-5';
        } else {
            $slotWrapClass =
                'flex min-h-0 flex-1 flex-col justify-between gap-y-7 pt-4 sm:gap-y-8 sm:pt-5 md:gap-y-9 md:pt-6 lg:gap-y-10';
        }
    } elseif ($dense) {
        $slotWrapClass = $tightStack
            /* flex + gap: reliable spacing between stacked tables (space-y can collapse with overflow-x-auto wrappers). */
            ? ($widerTableStack
                ? 'flex min-w-0 flex-col gap-2.5 pt-2.5 sm:gap-2.5 sm:pt-3 md:gap-3 md:pt-3.5'
                : 'flex min-w-0 flex-col gap-2 pt-2 sm:gap-2 sm:pt-2.5 md:gap-2.5 md:pt-3')
            : 'min-w-0 space-y-3 pt-[calc(0.75rem*0.85)] sm:space-y-4 sm:pt-[calc(1rem*0.85)] md:space-y-5 md:pt-[calc(1.25rem*0.85)]';
    } else {
        $slotWrapClass = 'min-w-0 space-y-4 pt-4 sm:space-y-5 sm:pt-5 md:space-y-6 md:pt-6';
    }
    $sectionExtra = $fillHeight ? ' h-full min-h-0 flex-1' : '';
    /* HS dense: bottom padding ≈ pt + title-row mb; stepped ×0.9 again. */
    if ($dense && $tightStack) {
        $sectionPad = $widerTableStack
            ? ' gap-2.5 px-1.5 pt-2 pb-2 sm:gap-3 sm:px-2 sm:pt-2.5 sm:pb-2.5 md:gap-3.5 md:pt-3 md:pb-2.5'
            : ' gap-2 px-1.5 pt-2 pb-2 sm:gap-2.5 sm:px-2 sm:pt-2.5 sm:pb-2.5 md:gap-3 md:pt-3 md:pb-2.5';
    } elseif ($dense) {
        $sectionPad =
            ' gap-2 px-1.5 pt-3 pb-[0.8365275rem] sm:gap-2.5 sm:px-2 sm:pt-4 sm:pb-[1.11537rem] md:gap-3 md:pt-5 md:pb-[1.25479125rem]';
    } else {
        $sectionPad = ' gap-2 p-2.5 shadow-sm sm:gap-2.5 sm:p-3';
    }
@endphp

<section
    {{ $attributes->merge([
        'class' =>
            'flex min-w-0 flex-col rounded-md bg-white'.
            $sectionPad.
            $sectionExtra.
            ($dense ? ' ranger-traits-dense-pane' : ''),
    ]) }}
>
    <div
        @class([
            'flex min-w-0 shrink-0 items-center',
            'mb-1.5 sm:mb-2 md:mb-2.5' => $tightStack && ! $widerTableStack,
            'mb-2 sm:mb-2.5 md:mb-3' => $widerTableStack,
            'mb-2.5 sm:mb-3.5 md:mb-4' => $dense && ! $tightStack,
            'gap-2 sm:gap-2.5 md:gap-3' => $dense && ! $widerTableStack,
            'gap-2.5 sm:gap-3 md:gap-4' => $widerTableStack,
            'gap-1.5 sm:gap-2' => ! $dense,
        ])
    >
        <div
            @class([
                'flex-1 bg-gray-900',
                'h-px min-w-[1rem] sm:h-0.5 sm:min-w-[1.25rem] md:min-w-[1.5rem]' => $dense,
                'h-px min-w-[0.75rem] sm:min-w-[1rem]' => ! $dense,
            ])
            aria-hidden="true"
        ></div>
        <h3
            @class([
                'notes-grades-section-heading max-w-full shrink-0 text-center leading-none uppercase tracking-wide',
                'px-0.5' => $dense,
                $titleClass,
            ])
        >
            {{ $title }}
        </h3>
        <div
            @class([
                'flex-1 bg-gray-900',
                'h-px min-w-[1rem] sm:h-0.5 sm:min-w-[1.25rem] md:min-w-[1.5rem]' => $dense,
                'h-px min-w-[0.75rem] sm:min-w-[1rem]' => ! $dense,
            ])
            aria-hidden="true"
        ></div>
    </div>

    <div class="{{ $noteWrapClass }}">
        <p class="ranger-trait-take-text max-w-full break-words">{{ $note ? $note : '#N/A' }}</p>
    </div>

    @if (! $slot->isEmpty())
        <div class="{{ $slotWrapClass }}">
            {{ $slot }}
        </div>
    @endif
</section>
