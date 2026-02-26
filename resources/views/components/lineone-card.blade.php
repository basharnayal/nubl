@props([
    'title' => null,
    'subtitle' => null,
    'footer' => null,
])

<div class="card px-4 pb-4 sm:px-5">
    @if($title || $subtitle)
        <div class="my-3 flex flex-col">
            @if($title)
                <h2 class="font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100 lg:text-base">
                    {{ $title }}
                </h2>
            @endif
            @if($subtitle)
                <p class="mt-1 text-sm text-slate-400 dark:text-navy-300">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
    @if($footer)
        <div class="mt-4 border-t border-slate-150 pt-4 dark:border-navy-600">
            {{ $footer }}
        </div>
    @endif
</div>
