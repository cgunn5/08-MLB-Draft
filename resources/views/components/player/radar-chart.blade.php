@props([
    'player' => null,
    'radar' => null,
    'comfortable' => false,
    'compact' => false,
    'showLegend' => true,
    /** When true, grow with parent height (profile header); width follows viewBox aspect ratio. */
    'fillHeight' => false,
])

@php
    $radar = is_array($radar) ? $radar : null;
    $hasDynamicRadar = $radar !== null
        && isset($radar['values'])
        && is_array($radar['values'])
        && count($radar['values']) >= 5;

    $fillHeight = filter_var($fillHeight, FILTER_VALIDATE_BOOLEAN);

    if ($compact) {
        $svgSize = 'max-w-[30px] max-h-[24px] sm:max-w-[32px] sm:max-h-[26px]';
    } elseif ($fillHeight) {
        /* w-full h-full + slice: column is usually width-tight vs viewBox; meet leaves top/bottom gap. */
        $svgSize = 'block h-full max-h-full w-full min-h-0 min-w-0';
    } elseif ($comfortable) {
        $svgSize =
            'max-w-[5.25rem] max-h-[4.15rem] sm:max-w-[6.75rem] sm:max-h-[5.35rem] md:max-w-[7.65rem] md:max-h-[6rem] lg:max-w-[8.5rem] lg:max-h-[6.75rem] 2xl:max-w-[12rem] 2xl:max-h-[9.35rem]';
    } else {
        $svgSize =
            'max-w-[4.25rem] max-h-[3.35rem] sm:max-w-[5.5rem] sm:max-h-[4.35rem] md:max-w-[6.25rem] md:max-h-[4.9rem] lg:max-w-[7rem] lg:max-h-[5.5rem] 2xl:max-w-[10.25rem] 2xl:max-h-[8rem]';
    }
@endphp

@php
    $cx = 110;
    $cy = 100;
    $rMax = 72;
    $nAxes = 5;
    $degStep = 360 / $nAxes;

    $toPoint = function (int $vertex, float $pct) use ($cx, $cy, $rMax, $degStep): string {
        $deg = -90 + $degStep * $vertex;
        $rad = deg2rad($deg);
        $r = $rMax * ($pct / 100);
        $x = round($cx + $r * cos($rad), 2);
        $y = round($cy + $r * sin($rad), 2);

        return "{$x},{$y}";
    };

    $polyRing = function (float $pct) use ($toPoint, $nAxes): string {
        $pts = [];
        for ($i = 0; $i < $nAxes; $i++) {
            $pts[] = $toPoint($i, $pct);
        }

        return implode(' ', $pts);
    };

    $polyFromValues = function (array $values) use ($toPoint, $nAxes): string {
        $pts = [];
        for ($i = 0; $i < $nAxes; $i++) {
            $v = (float) ($values[$i] ?? 0);
            $pts[] = $toPoint($i, $v);
        }

        return implode(' ', $pts);
    };

    if ($hasDynamicRadar) {
        $chartValues = array_map(static fn ($v) => (float) $v, array_slice($radar['values'], 0, $nAxes));
        /** @var list<array<string, mixed>> $axisMeta */
        $axisMeta = isset($radar['axes']) && is_array($radar['axes']) ? $radar['axes'] : [];
        $labels = [];
        foreach (\App\Support\HsOverallRadarNtile::AXES as $idx => $def) {
            $labels[$idx] = (string) ($axisMeta[$idx]['label'] ?? $def['label']);
        }
        $compScope = $radar['comp_scope'] ?? null;
        $compScope = is_string($compScope) ? $compScope : null;
        $compLabel = $compScope !== null ? $compScope : __('All');
        $ariaRadar =
            __('HS Overall vs draft comp: quintiles (NTILE 5) for OPS, SwM%, GB%, EV95, CH%.').' '.__('Comp set').': '.$compLabel;
    } else {
        $chartValues = array_fill(0, $nAxes, 35.0);
        $labels = array_column(\App\Support\HsOverallRadarNtile::AXES, 'label');
        $ariaRadar = __('Swing metrics radar (connect HS Overall data for comp quintiles).');
    }
@endphp

<div
    {{ $attributes->merge([
        'class' =>
            'flex min-w-0 flex-col items-center gap-px text-black sm:gap-0.5'.
            ($fillHeight ? ' h-full min-h-0 w-full justify-center' : ''),
    ]) }}
>
    <svg
        viewBox="0 5 220 200"
        class="{{ $fillHeight ? $svgSize.' shrink-0' : 'h-auto w-full shrink-0 '.$svgSize }}"
        preserveAspectRatio="{{ $fillHeight ? 'xMidYMid slice' : 'xMidYMid meet' }}"
        role="img"
        aria-label="{{ $ariaRadar }}"
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
                points="{{ $polyRing((float) $lvl) }}"
                fill="none"
                stroke="#d1d5db"
                stroke-width="0.6"
            />
        @endforeach

        @for ($i = 0; $i < $nAxes; $i++)
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

        @if ($hasDynamicRadar)
            <polygon
                points="{{ $polyFromValues($chartValues) }}"
                fill="rgb(220 38 38)"
                fill-opacity="0.22"
                stroke="#dc2626"
                stroke-width="1.8"
                stroke-linejoin="round"
            />
        @else
            <polygon
                points="{{ $polyFromValues($chartValues) }}"
                fill="rgb(243 244 246)"
                fill-opacity="0.85"
                stroke="#9ca3af"
                stroke-width="0.8"
            />
        @endif

        @foreach ($labels as $i => $label)
            @php
                $deg = -90 + $degStep * $i;
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

</div>
