<x-app-layout title="{{ __('Upload Proof') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-medium text-slate-800 dark:text-navy-50">
                {{ __('Upload Proof of Fulfillment') }}
            </h2>
        </div>

        <div class="card p-4 sm:p-5 max-w-2xl mx-auto">
            <div class="border-b border-slate-200 pb-4 mb-4 dark:border-navy-600">
                <h3 class="font-bold text-lg text-slate-700 dark:text-navy-100">{{ __('Order Details') }}</h3>
                <p class="text-sm text-slate-500 dark:text-navy-400">
                    {{ __('Request #') }}{{ $redemption->request->id }} &bull; {{ __('Recipient') }}:
                    {{ $redemption->request->recipient->name }}
                </p>
                <p class="text-sm font-semibold text-primary mt-2">
                    {{ __('Reserved Amount') }}: {{ number_format($redemption->request->reserved_amount, 2) }}
                    {{ __('SAR') }}
                </p>
            </div>

            <div class="bg-info/10 p-4 rounded-lg mb-6 border border-info/20 text-info">
                <p class="text-sm">
                    <strong>{{ __('Required Action') }}:</strong>
                    {{ __('Please upload a photo of the receipt or a photo of the food being handed over to complete the fulfillment of this request.') }}
                </p>
            </div>

            <form action="{{ route('provider.proof.store', $redemption->id) }}" method="POST"
                enctype="multipart/form-data" class="space-y-4">
                @csrf
                <label class="block">
                    <span class="font-medium text-slate-600 dark:text-navy-100">{{ __('Proof File') }}</span>
                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf"
                        class="form-input mt-1.5 w-full rounded-lg border border-slate-300 bg-transparent px-3 py-2 hover:border-slate-400 focus:border-primary dark:border-navy-450 dark:hover:border-navy-400 dark:focus:border-accent"
                        required />
                    <small class="text-xs text-slate-400 mt-1 block">
                        {{ __('Allowed formats: JPG, PNG, WEBP, PDF. Max size: 5MB.') }}
                    </small>
                    @error('proof_file')
                        <span class="text-xs text-error mt-1">{{ $message }}</span>
                    @enderror
                </label>

                <button type="submit"
                    class="btn mt-4 w-full bg-primary font-medium text-white hover:bg-primary-focus focus:bg-primary-focus active:bg-primary-focus/90 dark:bg-accent dark:hover:bg-accent-focus dark:focus:bg-accent-focus dark:active:bg-accent/90">
                    {{ __('Submit Proof and Fulfill Order') }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>