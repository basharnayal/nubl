@props([
    'name' => 'layout',
])

@php
    $svgClass = $attributes->get('class', 'size-7');
@endphp

@switch($name)
    {{-- Dashboard (Lineone) --}}
    @case('home')
        <svg class="{{ $svgClass }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path fill="currentColor" fill-opacity=".3" d="M5 14.059c0-1.01 0-1.514.222-1.945.221-.43.632-.724 1.453-1.31l4.163-2.974c.56-.4.842-.601 1.162-.601.32 0 .601.2 1.162.601l4.163 2.974c.821.586 1.232.88 1.453 1.31.222.43.222.935.222 1.945V19c0 .943 0 1.414-.293 1.707C18.414 21 17.943 21 17 21H7c-.943 0-1.414 0-1.707-.293C5 20.414 5 19.943 5 19v-4.94Z" />
            <path fill="currentColor" d="M3 12.387c0 .267 0 .4.084.441.084.041.19-.04.4-.204l7.288-5.669c.59-.459.885-.688 1.228-.688.343 0 .638.23 1.228.688l7.288 5.669c.21.163.316.245.4.204.084-.04.084-.174.084-.441v-.409c0-.48 0-.72-.102-.928-.101-.208-.291-.355-.67-.65l-7-5.445c-.59-.459-.885-.688-1.228-.688-.343 0-.638.23-1.228.688l-7 5.445c-.379.295-.569.442-.67.65-.102.208-.102.448-.102.928v.409Z" />
            <path fill="currentColor" d="M11.5 15.5h1A1.5 1.5 0 0 1 14 17v3.5h-4V17a1.5 1.5 0 0 1 1.5-1.5Z" />
            <path fill="currentColor" d="M17.5 5h-1a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5Z" />
        </svg>
        @break

    {{-- Wallet: bifold + bill edge + dollar (provider balance) --}}
    @case('wallet')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5.5 9.25h11.5a2 2 0 012 2v8.5a2 2 0 01-2 2H7.5a2 2 0 01-2-2v-8.5a2 2 0 012-2z" fill="currentColor" fill-opacity="0.22" />
            <path d="M5.5 9.25h11.5a2 2 0 012 2v8.5a2 2 0 01-2 2H7.5a2 2 0 01-2-2v-8.5a2 2 0 012-2z" stroke="currentColor" stroke-width="1.2" fill="none" />
            <path d="M5.5 9.25V8.5a2.75 2.75 0 012.75-2.75h6a2.75 2.75 0 012.75 2.75v0.75" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" fill="none" stroke-opacity="0.5" />
            <path d="M4.75 11.5h4.25a1.25 1.25 0 001.1-.65l.35-.7a1.25 1.25 0 011.1-.65H17" stroke="currentColor" stroke-width="1.05" stroke-linecap="round" stroke-opacity="0.4" fill="none" />
            <rect x="6" y="12.25" width="10" height="1.15" rx="0.35" fill="currentColor" fill-opacity="0.2" />
            <circle cx="14.75" cy="16.75" r="2.65" fill="white" fill-opacity="0.92" />
            <path d="M14.75 14.95v3.6M13.05 15.85c0-.72.72-1.1 1.7-1.1s1.7.38 1.7 1.1-.72 1.1-1.7 1.1-1.7.38-1.7 1.1.72 1.1 1.7 1.1 1.7-.38 1.7-1.1" stroke="currentColor" stroke-width="1.05" fill="none" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break

    {{-- ClipboardList: request queues / fulfillments / my requests --}}
    @case('clipboard-list')
    @case('incoming-requests')
    @case('inbox')
    @case('truck')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 4.25h1.2a1.5 1.5 0 012.6 0H14a2 2 0 012 2v12.5a2 2 0 01-2 2H8a2 2 0 01-2-2V6.25a2 2 0 012-2z" fill="currentColor" fill-opacity="0.2" />
            <path d="M9 4.25h1.2a1.5 1.5 0 012.6 0H14a2 2 0 012 2v12.5a2 2 0 01-2 2H8a2 2 0 01-2-2V6.25a2 2 0 012-2z" stroke="currentColor" stroke-width="1.15" fill="none" />
            <path d="M10 4.25V3.5a1 1 0 011-1h2a1 1 0 011 1v0.75" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" fill="none" stroke-opacity="0.55" />
            <rect x="7.25" y="8.75" width="1.35" height="1.35" rx="0.35" fill="currentColor" />
            <path d="M10.25 9.4h7.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
            <rect x="7.25" y="11.85" width="1.35" height="1.35" rx="0.35" fill="currentColor" fill-opacity="0.55" />
            <path d="M10.25 12.5h7.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-opacity="0.75" />
            <rect x="7.25" y="14.95" width="1.35" height="1.35" rx="0.35" fill="currentColor" fill-opacity="0.35" />
            <path d="M10.25 15.6h5.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-opacity="0.55" />
        </svg>
        @break

    {{-- UsersRound-style: overlapping heads + shared base --}}
    @case('users-round')
    @case('user-group')
    @case('users')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="8" cy="9" r="2.45" fill="currentColor" fill-opacity="0.38" />
            <circle cx="16" cy="9" r="2.45" fill="currentColor" fill-opacity="0.38" />
            <circle cx="12" cy="8.25" r="2.85" fill="currentColor" />
            <path d="M5.25 20.25c0-3.05 2.35-5.5 6.75-5.5s6.75 2.45 6.75 5.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" fill="none" />
        </svg>
        @break

    {{-- CreditCard + dollar: admin finance module --}}
    @case('credit-card')
    @case('finance')
    @case('banknotes')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2.5" y="5.5" width="19" height="13" rx="2.25" fill="currentColor" />
            <rect x="2.5" y="5.5" width="19" height="13" rx="2.25" stroke="currentColor" stroke-width="0.5" stroke-opacity="0.2" fill="none" />
            <path d="M2.5 9.75h19" stroke="currentColor" stroke-width="2.25" stroke-opacity="0.22" />
            <rect x="5" y="11" width="4.25" height="3.25" rx="0.55" fill="currentColor" fill-opacity="0.28" />
            <circle cx="12" cy="15.75" r="3.85" fill="white" fill-opacity="0.92" />
            <path d="M12 13.35v4.85M9.85 14.55c0-1.05 1-1.6 2.15-1.6s2.15.55 2.15 1.6-1 1.6-2.15 1.6-2.15.55-2.15 1.6 1 1.6 2.15 1.6 2.15-.55 2.15-1.6" stroke="currentColor" stroke-width="1.15" fill="none" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="17.25" cy="17.1" r="1.15" fill="currentColor" fill-opacity="0.35" />
            <path d="M15.25 17.1h1.75M6.5 17.1h2.75" stroke="currentColor" stroke-width="0.95" stroke-linecap="round" stroke-opacity="0.35" />
        </svg>
        @break

    {{-- FileClock: audit trail / time-stamped log --}}
    @case('file-clock')
    @case('audit-log')
    @case('shield-check')
    @case('clipboard-check')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 3.25H8.5A2.25 2.25 0 006.25 5.5V19a2.25 2.25 0 002.25 2.25H15.5A2.25 2.25 0 0017.75 19v-9.25L14 3.25z" fill="currentColor" fill-opacity="0.18" />
            <path d="M14 3.25H8.5A2.25 2.25 0 006.25 5.5V19a2.25 2.25 0 002.25 2.25H15.5A2.25 2.25 0 0017.75 19v-9.25L14 3.25z" stroke="currentColor" stroke-width="1.15" fill="none" />
            <path d="M14 3.25V8.5h4.5" stroke="currentColor" stroke-width="1.15" stroke-linejoin="round" />
            <path d="M8.75 12.75h5.5M8.75 15.25h4" stroke="currentColor" stroke-width="1.05" stroke-linecap="round" stroke-opacity="0.45" />
            <circle cx="16.75" cy="14.75" r="3.6" stroke="currentColor" stroke-width="1.15" fill="currentColor" fill-opacity="0.12" />
            <path d="M16.75 13.1v1.85l1.1 0.65" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break

    {{-- Lineone: Settings --}}
    @case('cog')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-opacity="0.3" fill="currentColor" d="M2 12.947v-1.771c0-1.047.85-1.913 1.899-1.913 1.81 0 2.549-1.288 1.64-2.868a1.919 1.919 0 0 1 .699-2.607l1.729-.996c.79-.474 1.81-.192 2.279.603l.11.192c.9 1.58 2.379 1.58 3.288 0l.11-.192c.47-.795 1.49-1.077 2.279-.603l1.73.996a1.92 1.92 0 0 1 .699 2.607c-.91 1.58-.17 2.868 1.639 2.868 1.04 0 1.899.856 1.899 1.912v1.772c0 1.047-.85 1.912-1.9 1.912-1.808 0-2.548 1.288-1.638 2.869.52.915.21 2.083-.7 2.606l-1.729.997c-.79.473-1.81.191-2.279-.604l-.11-.191c-.9-1.58-2.379-1.58-3.288 0l-.11.19c-.47.796-1.49 1.078-2.279.605l-1.73-.997a1.919 1.919 0 0 1-.699-2.606c.91-1.58.17-2.869-1.639-2.869A1.911 1.911 0 0 1 2 12.947Z" />
            <path fill="currentColor" d="M11.995 15.332c1.794 0 3.248-1.464 3.248-3.27 0-1.807-1.454-3.272-3.248-3.272-1.794 0-3.248 1.465-3.248 3.271 0 1.807 1.454 3.271 3.248 3.271Z" />
        </svg>
        @break

    {{-- Utensils: provider menus / food catalog --}}
    @case('utensils')
    @case('book-open')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 20V9.5M9 9.5L6.25 5.25M9 9.5L9 4.25M9 9.5L11.75 5.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M15.75 4.25v15.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
            <path d="M15.75 4.25l1.65 1.1c.55.55.85 1.3.85 2.1s-.3 1.55-.85 2.1L15.75 10.5" stroke="currentColor" stroke-width="1.15" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke-opacity="0.55" />
        </svg>
        @break

    @case('qr-code')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 4h6v6H4V4zm2 2v2h2V6H6zm8-2h6v6h-6V4zm2 2v2h2V6h-2zM4 14h6v6H4v-6zm2 2v2h2v-2H6zm4 0h2v2h-2v-2zm4-2h6v6h-6v-6zm2 2v2h2v-2h-2zm-6 4h2v2h-2v-2z" fill="currentColor" />
            <path d="M14 14h2v2h-2v-2zm4 0h2v2h-2v-2z" fill="currentColor" fill-opacity="0.35" />
        </svg>
        @break

    @case('cube')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2.5l7.5 4.25v11L12 22l-7.5-4.25v-11L12 2.5z" fill="currentColor" fill-opacity="0.22" />
            <path d="M12 2.5v8.25M12 10.75L4.5 6.5M12 10.75l7.5-4.25M4.5 6.5v11l7.5 4.25M19.5 6.5v11L12 21.75" stroke="currentColor" stroke-width="1.25" stroke-linejoin="round" />
            <path d="M7.25 9.125L12 11.5l4.75-2.375" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.5" />
        </svg>
        @break

    @case('list-bullet')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="6" cy="7" r="1.5" fill="currentColor" />
            <circle cx="6" cy="12" r="1.5" fill="currentColor" fill-opacity="0.45" />
            <circle cx="6" cy="17" r="1.5" fill="currentColor" fill-opacity="0.3" />
            <path d="M10 7h10M10 12h10M10 17h7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
        </svg>
        @break

    @case('document-text')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 3.5h7l4 4V20a1 1 0 01-1 1H7a2 2 0 01-2-2V5.5a2 2 0 012-2z" fill="currentColor" fill-opacity="0.28" />
            <path d="M14 3.5V8h4" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" />
            <path d="M8.5 12h7M8.5 15h7M8.5 18h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
        </svg>
        @break

    @case('user-clock')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="10" cy="9" r="3.25" fill="currentColor" />
            <path d="M4.5 19.5c0-3 2.5-5.5 5.5-5.5s3 .6 3.8 1.3" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" fill="none" />
            <circle cx="17.5" cy="15.5" r="3.75" stroke="currentColor" stroke-width="1.25" fill="currentColor" fill-opacity="0.2" />
            <path d="M17.5 14v2l1.2.7" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break

    @case('storefront')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 10l2.5-5h13L21 10v10a1 1 0 01-1 1H4a1 1 0 01-1-1V10z" fill="currentColor" fill-opacity="0.28" />
            <path d="M3 10h18v2H3V10z" fill="currentColor" />
            <path d="M8 14h8v6H8v-6z" fill="currentColor" fill-opacity="0.45" />
            <path d="M5 8l1.2-2.4h11.6L19 8" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break

    @case('heart')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 20.5s-7-4.35-9.2-8.1C.85 9.25 2.05 6 5.25 6c1.85 0 3.15 1 3.75 2.45C9.6 7 10.9 6 12.75 6 15.95 6 17.15 9.25 15.2 12.4 13 16.15 12 20.5 12 20.5z" fill="currentColor" />
        </svg>
        @break

    @case('analytics')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19h16" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
            <rect x="5.5" y="11" width="3" height="7" rx="0.75" fill="currentColor" fill-opacity="0.35" />
            <rect x="10.5" y="7" width="3" height="11" rx="0.75" fill="currentColor" fill-opacity="0.55" />
            <rect x="15.5" y="4" width="3" height="14" rx="0.75" fill="currentColor" />
            <path d="M6 9l3-2 4 3 5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </svg>
        @break

    @case('statistics')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="8" fill="currentColor" fill-opacity="0.2" />
            <path d="M12 12V6.5a5.5 5.5 0 015.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            <path d="M12 12l4.2 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-opacity="0.45" />
            <circle cx="12" cy="12" r="1.6" fill="currentColor" />
        </svg>
        @break

    @case('chart-bar')
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 19h16" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
            <rect x="5.5" y="11" width="3" height="7" rx="0.75" fill="currentColor" fill-opacity="0.35" />
            <rect x="10.5" y="7" width="3" height="11" rx="0.75" fill="currentColor" fill-opacity="0.55" />
            <rect x="15.5" y="4" width="3" height="14" rx="0.75" fill="currentColor" />
            <path d="M6 9l3-2 4 3 5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </svg>
        @break

    @default
        <svg class="{{ $svgClass }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="8" cy="8" r="2" fill="currentColor" />
            <circle cx="16" cy="8" r="2" fill="currentColor" fill-opacity="0.35" />
            <circle cx="8" cy="16" r="2" fill="currentColor" fill-opacity="0.35" />
            <circle cx="16" cy="16" r="2" fill="currentColor" fill-opacity="0.2" />
        </svg>
        @break
@endswitch
