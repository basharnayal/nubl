<form method="POST" action="{{ route('profile.provider-financial.update') }}" class="space-y-6">
    @csrf
    @method('PATCH')

    <p class="text-xs text-slate-500 dark:text-navy-400">{{ __('Payout details are used for transfers from the city fund. Keep your IBAN accurate.') }}</p>

    <div>
        <label for="bank_name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Bank Name') }}</label>
        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $providerFinancial->bank_name) }}" required
            class="form-input form-input-lineone w-full max-w-xl rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
        @error('bank_name')
            <p class="mt-1 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="iban" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('IBAN') }}</label>
        <input type="text" name="iban" id="iban" value="{{ old('iban', $providerFinancial->iban) }}" required dir="ltr"
            autocomplete="off"
            class="form-input form-input-lineone w-full max-w-xl rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
        @error('iban')
            <p class="mt-1 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="account_holder_name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-navy-200">{{ __('Account Holder') }}</label>
        <input type="text" name="account_holder_name" id="account_holder_name"
            value="{{ old('account_holder_name', $providerFinancial->account_holder_name) }}" required
            class="form-input form-input-lineone w-full max-w-xl rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-navy-500 dark:bg-navy-800/80 dark:text-navy-50" />
        @error('account_holder_name')
            <p class="mt-1 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-4 dark:border-navy-600">
        <button type="submit"
            class="btn inline-flex min-h-[2.75rem] items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-focus focus:outline-none focus-visible:ring-2 focus-visible:ring-primary dark:bg-accent dark:hover:bg-accent-focus">
            {{ __('Save payment details') }}
        </button>
        @if (session('status') === 'financial-profile-updated')
            <span class="text-sm font-medium text-success">{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
