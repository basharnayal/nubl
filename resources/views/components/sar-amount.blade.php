@props([
    'value',
    'class' => '',
])

<span
    class="inline-flex items-center gap-2 whitespace-nowrap {{ $class }}"
    dir="ltr"
    style="unicode-bidi:isolate;"
>
    <x-sar-symbol />
    <span>{{ $value }}</span>
</span>
