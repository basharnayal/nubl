{{-- Recipients & Providers with status=pending_approval --}}
<x-guest-layout>
    <div class="w-full">
        <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-nubl-gold-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-nubl-gold-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ __('Your account is under review') }}</h1>
                    <p class="text-slate-600 mb-4">
                        {{ __('Thank you for registering. Your account has been submitted and is awaiting review by our team. You will receive access once an administrator approves your application.') }}
                    </p>
                    <p class="text-sm text-slate-500 mb-6">
                        {{ __('Please check back later or contact support if you have questions.') }}
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        @if(auth()->user()?->hasRole('provider'))
                        <a href="{{ route('provider.application') }}" class="inline-flex items-center justify-center px-4 py-2.5 border border-nubl-teal-300 text-nubl-teal-700 text-sm font-medium rounded-lg hover:bg-nubl-teal-50 transition">
                            {{ __('View my application') }}
                        </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 bg-nubl-blue-600 text-white text-sm font-medium rounded-lg hover:bg-nubl-blue-700 focus:ring-4 focus:ring-nubl-blue-200 transition">
                                {{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
