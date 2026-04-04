@props([
    'allRoles',
    'selected' => [],
])

<div class="space-y-2">
    <div>
        <x-input-label :value="__('Roles')" />
        <p class="mt-1 text-xs text-slate-500 dark:text-navy-400">{{ __('roles_assignment_help') }}</p>
    </div>
    <div class="flex flex-wrap gap-x-4 gap-y-2 rounded-lg border border-slate-200 p-3 dark:border-navy-600">
        @foreach ($allRoles as $role)
            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                    @checked(in_array($role->name, $selected, true))
                    class="form-checkbox is-basic size-4 rounded border-slate-400/70 before:bg-primary checked:border-primary checked:bg-primary dark:border-navy-400 dark:before:bg-accent dark:checked:border-accent dark:checked:bg-accent">
                <span class="text-sm text-slate-700 dark:text-navy-100">{{ $role->name }}</span>
            </label>
        @endforeach
    </div>
    @if ($errors->has('roles') || $errors->has('roles.0'))
        <p class="mt-1 text-sm text-error">{{ $errors->first('roles') ?: $errors->first('roles.0') }}</p>
    @endif
</div>
