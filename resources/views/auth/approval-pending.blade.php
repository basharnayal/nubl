{{-- Recipients & Providers with status=pending_approval or rejected --}}
<x-guest-layout>
    <div class="w-full">
        <div class="rounded-xl border border-slate-200 dark:border-navy-600 bg-white dark:bg-navy-750 p-6 sm:p-8 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center {{ auth()->user()?->status === 'rejected' ? 'bg-red-100' : 'bg-primary/10 dark:bg-accent/10' }}">
                    @if(auth()->user()?->status === 'rejected')
                        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-primary dark:text-accent-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>
                <div class="flex-1">
                    @if(auth()->user()?->status === 'rejected')
                        <h1 class="text-xl font-semibold text-slate-900 dark:text-navy-100 mb-2">{{ __('Your application was rejected') }}</h1>
                        <p class="text-slate-600 dark:text-navy-300 mb-4">
                            {{ __('Your account application has been reviewed and was not approved at this time.') }}
                        </p>
                        @if(auth()->user()?->rejection_reason)
                            <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-100">
                                <p class="text-sm font-medium text-red-800 mb-1">{{ __('Reason:') }}</p>
                                <p class="text-sm text-red-700">{{ auth()->user()->rejection_reason }}</p>
                            </div>
                        @endif
                        <p class="text-sm text-slate-500 mb-6">
                            {{ __('You may contact support if you have questions or wish to reapply.') }}
                        </p>
                    @else
                        <h1 class="text-xl font-semibold text-slate-900 dark:text-navy-100 mb-2">{{ __('Your account is under review') }}</h1>
                        <p class="text-slate-600 dark:text-navy-300 mb-4">
                            {{ __('Thank you for registering. Your account has been submitted and is awaiting review by our team. You will receive access once an administrator approves your application.') }}
                        </p>
                        <p class="text-sm text-slate-500 mb-6">
                            {{ __('Please check back later or contact support if you have questions.') }}
                        </p>
                    @endif
                    <div class="flex flex-col sm:flex-row gap-3">
                        @if(auth()->user()?->hasRole('provider'))
                        <a href="{{ route('provider.application') }}" class="inline-flex items-center justify-center px-4 py-2.5 border border-primary/30 text-primary dark:border-accent/30 dark:text-accent-light text-sm font-medium rounded-lg hover:bg-primary/10 dark:hover:bg-accent/10 transition">
                            {{ __('View my application') }}
                        </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <x-lineone-button type="submit" size="sm">
                                {{ __('Log out') }}
                            </x-lineone-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
