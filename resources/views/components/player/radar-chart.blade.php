@props([
    'player' => null,
    'comfortable' => false,
    'compact' => false,
    'showLegend' => true,
])

@php
    $showLegend = filter_var($showLegend, FILTER_VALIDATE_BOOLEAN);
    if ($compact) {
        /* Pixels: root font-size scales on wide screens (see app.css); rem would blow past the 32–34px strip. */
        $svgSize = 'max-w-[30px] max-h-[24px] sm:max-w-[32px] sm:max-h-[26px]';
        $legendText = 'text-[0.4rem] sm:text-[0.45rem]';
    } elseif ($comfortable) {
        $svgSize =
            'max-w-[5.25rem] max-h-[4.15rem] sm:max-w-[6.75rem] sm:max-h-[5.35rem] md:max-w-[7.65rem] md:max-h-[6rem] lg:max-w-[8.5rem] lg:max-h-[6.75rem] 2xl:max-w-[12rem] 2xl:max-h-[9.35rem]';
        $legendText = 'text-[0.625rem] sm:text-[0.6875rem] md:text-[0.75rem] 2xl:text-[0.875rem]';
    } else {
        $svgSize =
            'max-w-[4.25rem] max-h-[3.35rem] sm:max-w-[5.5rem] sm:max-h-[4.35rem] md:max-w-[6.25rem] md:max-h-[4.9rem] lg:max-w-[7rem] lg:max-h-[5.5rem] 2xl:max-w-[10.25rem] 2xl:max-h-[8rem]';
        $legendText = 'text-[0.5rem] sm:text-[0.53125rem] 2xl:text-[0.625rem]';
    }
@endphp

{{-- Placeholder hex radar; wire $player + metrics when data model exists --}}
@php $cx = 110;
    $cy = 100;
    $rMax = 72;
    $labels = ['XWOBACON', 'SWDEC', 'SWM', 'IZSWM%', 'GB%', 'EV95'];

    $toPoint = function (int $vertex, float $pct) use ($cx, $cy, $rMax): string {
        $deg = -90 + 60 * $vertex;
        $rad = deg2rad($deg);
        $r = $rMax * ($pct / 100);
        $x = round($cx + $r * cos($rad), 2);
        $y = round($cy + $r * sin($rad), 2);

        return "{$x},{$y}";
    };

    $hexRing = function (float $pct) use ($toPoint): string {
        $pts = [];
        for ($i = 0; $i < 6; $i++) {
            $pts[] = $toPoint($i, $pct);
        }

        return implode(' ', $pts);
    };

    $polyFromValues = function (array $values) use ($toPoint): string {
        $pts = [];
        foreach ($values as $i => $v) {
            $pts[] = $toPoint((int) $i, (float) $v);
        }

        return implode(' ', $pts);
    };

    // Static demo series (replace with player metrics later)
    $avgVals = [35, 35, 35, 35, 35, 35];
    $y2024 = [40, 90, 86, 84, 45, 36];
    $y2025 = [48, 58, 76, 74, 70, 44];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex min-w-0 flex-col items-center gap-px text-black sm:gap-0.5']) }}
>
    <svg
        viewBox="0 5 220 200"
        class="h-auto w-full shrink-0 {{ $svgSize }}"
        preserveAspectRatio="xMidYMid meet"
        role="img"
        aria-label="{{ __('SWING METRICS RADAR (PLACEHOLDER)') }}"
    >
        <defs>
            <style>
                .radar-text {
                    font-family: Carbon, ui-sans-serif, system-ui, sans-serif;
                    font-weight: 700;
                }
            </style>
        </defs>

        @foreach ([20, 40, 60, 80, 100] as $lvl)
            <polygon
                points="{{ $hexRing($lvl) }}"
                fill="none"
                stroke="#d1d5db"
                stroke-width="0.6"
            />
        @endforeach

        @for ($i = 0; $i < 6; $i++)
            <line
                x1="{{ $cx }}"
                y1="{{ $cy }}"
                x2="{{ explode(',', $toPoint($i, 100))[0] }}"
                y2="{{ explode(',', $toPoint($i, 100))[1] }}"
                stroke="#d1d5db"
                stroke-width="0.6"
            />
        @endfor

        <text
            x="{{ $cx }}"
            y="{{ $cy + 9 }}"
            text-anchor="middle"
            class="radar-text"
            fill="#111827"
            font-size="7"
        >
            0
        </text>
        @foreach ([25, 50, 75, 100] as $tick)
            @php
                $tp = $toPoint(0, (float) $tick);
                [$tx, $ty] = array_map('floatval', explode(',', $tp));
            @endphp
            <text
                x="{{ $tx }}"
                y="{{ $ty - 3 }}"
                text-anchor="middle"
                class="radar-text"
                fill="#111827"
                font-size="7"
            >
                {{ $tick }}
            </text>
        @endforeach

        <polygon
            points="{{ $polyFromValues($avgVals) }}"
            fill="rgb(243 244 246)"
            fill-opacity="0.85"
            stroke="#9ca3af"
            stroke-width="0.8"
        />

        <polygon
            points="{{ $polyFromValues($y2024) }}"
            fill="rgb(220 38 38)"
            fill-opacity="0.22"
            stroke="#dc2626"
            stroke-width="1.8"
            stroke-linejoin="round"
        />

        <polygon
            points="{{ $polyFromValues($y2025) }}"
            fill="rgb(37 99 235)"
            fill-opacity="0.22"
            stroke="#2563eb"
            stroke-width="1.8"
            stroke-linejoin="round"
        />

        @foreach ($labels as $i => $label)
            @php
                $deg = -90 + 60 * $i;
                $rad = deg2rad($deg);
                $lr = $rMax + 16;
                $lx = round($cx + $lr * cos($rad), 1);
                $ly = round($cy + $lr * sin($rad), 1);
            @endphp
            <text
                x="{{ $lx }}"
                y="{{ $ly }}"
                text-anchor="middle"
                dominant-baseline="middle"
                class="radar-text"
                fill="#111827"
                font-size="6.5"
            >
                {{ $label }}
            </text>
        @endforeach
    </svg>

    <div
        class="flex flex-wrap items-center justify-center gap-x-1 gap-y-px font-sans font-[700] leading-none text-gray-900 sm:gap-x-1.5 2xl:gap-x-2 {{ $legendText }} {{ $compact || ! $showLegend ? 'hidden' : '' }}"
    >
        <span class="inline-flex items-center gap-px sm:gap-0.5">
            <span class="inline-block h-1 w-1 shrink-0 rounded-[0.5px] bg-blue-600 sm:h-1.5 sm:w-1.5" aria-hidden="true"></span>
            2025
        </span>
        <span class="inline-flex items-center gap-px sm:gap-0.5">
            <span class="inline-block h-1 w-1 shrink-0 rounded-[0.5px] bg-red-600 sm:h-1.5 sm:w-1.5" aria-hidden="true"></span>
            2024
        </span>
        <span class="inline-flex items-center gap-px sm:gap-0.5">
            <span
                class="inline-block h-1 w-1 shrink-0 rounded-[0.5px] border border-gray-400 bg-gray-50 sm:h-1.5 sm:w-1.5"
                aria-hidden="true"
            ></span>
            AVG
        </span>
    </div>
</div>
