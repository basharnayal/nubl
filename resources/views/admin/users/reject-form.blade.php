<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Reject Application') }} — {{ $user->name }}
            </h2>
            <a href="{{ route('admin.users.pending') }}" class="text-sm text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent font-medium">{{ __('Cancel') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                @csrf
                <x-input-label for="rejection_reason" :value="__('Rejection reason (required)')" required />
                <textarea id="rejection_reason" name="rejection_reason" rows="5" required maxlength="2000"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary dark:focus:border-accent dark:focus:ring-accent"
                    placeholder="{{ __('Explain why this application is being rejected...') }}">{{ old('rejection_reason') }}</textarea>
                <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                <div class="mt-6 flex gap-3">
                    <x-lineone-button type="submit" variant="danger">{{ __('Reject') }}</x-lineone-button>
                    <a href="{{ route('admin.users.pending') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
