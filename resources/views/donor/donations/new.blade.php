<x-app-layout title="{{ __('New Donation') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
            <div class="max-w-2xl">
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('Make a Donation') }}
                </h2>

                <div class="card mt-3 p-6">
                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-error/30 bg-error/10 p-4 dark:bg-error/15 dark:border-error/20">
                            <ul class="list-disc list-inside text-sm text-error dark:text-error">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('donor.payments.initiate') }}">
                        @csrf

                        <div class="space-y-4">
                            <div>
                                <label for="amount" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Amount') }} ({{ __('SAR') }}) *</label>
                                <input type="number" id="amount" name="amount" value="{{ old('amount') }}" step="0.01" min="10" max="999999.99" required
                                    class="form-input form-input-lineone" placeholder="100">
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-lineone-button type="submit" variant="primary">{{ __('Proceed to Payment') }}</x-lineone-button>
                            <x-lineone-button :href="route('donor.dashboard')" variant="slate" outline>{{ __('Cancel') }}</x-lineone-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
