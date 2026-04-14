@php
    $cols = (int) ($cols ?? 1);
    $cols = max(1, $cols);
@endphp
<colgroup>
    @foreach (range(1, $cols) as $_)
        <col style="width: calc(100% / {{ $cols }})" />
    @endforeach
</colgroup>
