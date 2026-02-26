<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Max width of the content area: 'default' (sm:max-w-md) or 'wide' (sm:max-w-2xl).
     */
    public function __construct(
        public string $maxWidth = 'default',
        public ?string $title = null
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('components.guest-layout');
    }
}
