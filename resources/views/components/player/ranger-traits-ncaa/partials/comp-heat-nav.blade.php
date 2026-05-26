@php
    $compHeatScope = $compHeatScope ?? null;
@endphp
<nav
    class="flex max-w-full flex-wrap justify-center gap-1 gap-y-1"
    aria-label="{{ __('Draft comp bucket for table heat') }}"
>
    @foreach (\App\Support\HsCompHeatScope::uiOptions() as $opt)
        @php
            $isActive = $compHeatScope === ($opt['value'] ?? null);
            $href = route('ncaa.players.show', $compHeatRoutePlayer);
            if (($opt['value'] ?? null) !== null) {
                $href .= '?'.http_build_query([\App\Support\HsCompHeatScope::QUERY_KEY => $opt['value']]);
            }
        @endphp
        <a
            href="{{ $href }}"
            @class([
                'rounded border px-2 py-0.5 text-[9px] font-semibold uppercase leading-none tracking-wide shadow-sm transition sm:px-2.5 sm:py-1 sm:text-[10px]',
                'border-indigo-600 bg-indigo-50 text-indigo-900' => $isActive,
                'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50' => ! $isActive,
            ])
        >{{ $opt['label'] }}</a>
    @endforeach
</nav>
