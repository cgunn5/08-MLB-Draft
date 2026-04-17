@props(['heat' => null])
<td {{ $attributes }} @if (! empty($heat)) style="{{ $heat }}" @endif>{{ $slot }}</td>
