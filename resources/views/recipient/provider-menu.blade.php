@if($menuItems->isEmpty())
    <p class="text-gray-500">{{ __('No menu items available at the moment.') }}</p>
@else
    @php
        $byCategory = $menuItems->groupBy(fn ($item) => $item->category ?: '');
        $uncategorized = $byCategory->pull('');
        if ($uncategorized && $uncategorized->isNotEmpty()) {
            $byCategory->put(__('Other'), $uncategorized);
        }
    @endphp
    <div class="space-y-6">
        @foreach($byCategory as $category => $items)
            <div>
                @if($category)
                    <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">{{ $category }}</h4>
                @endif
                <ul class="divide-y divide-gray-100">
                    @foreach($items as $item)
                        <li class="py-3 first:pt-0">
                            <div class="flex justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                    @if($item->description)
                                        <p class="text-sm text-gray-500 mt-0.5">{{ $item->description }}</p>
                                    @endif
                                    @if($item->max_per_request)
                                        <p class="text-xs text-gray-400 mt-1">{{ __('Max per request') }}: {{ $item->max_per_request }}</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <span class="font-semibold text-gray-900">&#x20C1; {{ number_format($item->price, 2) }}</span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endif
