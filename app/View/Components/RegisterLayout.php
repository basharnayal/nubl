<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class RegisterLayout extends Component
{
    /**
     * Max width: 'default' (max-w-2xl) or 'wide' (max-w-4xl).
     */
    public function __construct(
        public ?string $title = null,
        public ?string $heading = null,
        public ?string $subheading = null,
        public string $maxWidth = 'default'
    ) {}

    public function render(): View
    {
        return view('components.register-layout');
    }
}
