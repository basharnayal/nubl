<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 flex flex-col">
        {{-- Navigation (Fixed) --}}
        @include('layouts.partials.navigation-base')

        {{-- Flash Messages (Fixed) --}}
        @if(session('success') || session('error') || session('warning') || session('info'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
                @if(session('success'))
                    <x-flowbite-alert type="success" dismissible>{{ session('success') }}</x-flowbite-alert>
                @endif
                @if(session('error'))
                    <x-flowbite-alert type="danger" dismissible>{{ session('error') }}</x-flowbite-alert>
                @endif
                @if(session('warning'))
                    <x-flowbite-alert type="warning" dismissible>{{ session('warning') }}</x-flowbite-alert>
                @endif
                @if(session('info'))
                    <x-flowbite-alert type="info" dismissible>{{ session('info') }}</x-flowbite-alert>
                @endif
            </div>
        @endif

        {{-- Page Heading (Fixed) --}}
        @if(isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        {{-- Page Content (Dynamic) --}}
        <main class="flex-grow">
            {{ $slot ?? '' }}
            @hasSection('content')
                @yield('content')
            @endif
        </main>

        {{-- Footer (Fixed) --}}
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
                </div>
            </div>
        </footer>
    </div>
    
    @stack('scripts')
</body>
</html>