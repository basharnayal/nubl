<x-app-layout :title="__('rbac.edit_role')" is-header-blur="true">
    <div class="pt-4">
        <div class="mb-4">
            <a href="{{ route('admin.roles.index') }}"
                class="text-sm font-medium text-primary hover:text-primary-focus dark:text-accent-light dark:hover:text-accent">
                {{ __('rbac.back_to_roles') }}
            </a>
        </div>

        <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">{{ __('rbac.edit_role') }}:
            {{ $role->name }}</h2>

        @if ($isProtected)
            <div class="mt-3 max-w-2xl rounded-lg border border-warning/30 bg-warning/10 px-4 py-3 text-sm text-slate-700 dark:border-warning/40 dark:bg-warning/15 dark:text-navy-100">
                {{ __('rbac.protected_role_hint') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="card p-4 sm:p-5">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('rbac.field.role_name') }}</span>
                    @if ($isProtected)
                        <input type="text" readonly disabled
                            class="form-input form-input-lineone mt-1.5 w-full max-w-md cursor-not-allowed opacity-75"
                            value="{{ $role->name }}">
                    @else
                        <input type="text" name="name" value="{{ old('name', $role->name) }}" required autocomplete="off"
                            class="form-input form-input-lineone mt-1.5 w-full max-w-md">
                        <span class="mt-1 block text-xs text-slate-500 dark:text-navy-400">{{ __('rbac.field.role_name_help') }}</span>
                    @endif
                </label>
                @error('name')
                    <p class="mt-1 text-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <h3 class="text-sm font-medium text-slate-700 dark:text-navy-100">{{ __('rbac.assign_permissions') }}</h3>
                @include('admin.roles._permission-fields', [
                    'groups' => $groups,
                    'selected' => old('permissions', $selected),
                ])
                @error('permissions')
                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                @enderror
                @foreach ($errors->get('permissions.*') as $messages)
                    @foreach ($messages as $message)
                        <p class="mt-1 text-sm text-error">{{ $message }}</p>
                    @endforeach
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                <x-lineone-button type="submit" variant="primary" size="sm">{{ __('rbac.save') }}</x-lineone-button>
                <a href="{{ route('admin.roles.index') }}"
                    class="btn h-10 rounded-lg px-4 text-sm font-medium text-slate-600 hover:bg-slate-150 dark:text-navy-200 dark:hover:bg-navy-600">{{ __('rbac.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
