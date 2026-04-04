@props([
    'groups',
    'selected' => [],
])

@foreach ($groups as $group)
    <div class="card permission-group mt-4 first:mt-0">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 dark:border-navy-500 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <h3 class="font-medium text-slate-700 dark:text-navy-100">{{ $group['label'] }}</h3>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                    class="btn h-8 rounded-lg px-3 text-xs-plus text-slate-600 hover:bg-slate-150 dark:text-navy-200 dark:hover:bg-navy-600"
                    onclick="this.closest('.permission-group').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = true)">
                    {{ __('rbac.select_all') }}
                </button>
                <button type="button"
                    class="btn h-8 rounded-lg px-3 text-xs-plus text-slate-600 hover:bg-slate-150 dark:text-navy-200 dark:hover:bg-navy-600"
                    onclick="this.closest('.permission-group').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false)">
                    {{ __('rbac.select_none') }}
                </button>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-2 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($group['permissions'] as $row)
                @php
                    $perm = $row['permission'] ?? $row;
                    $label = $row['label'] ?? $perm->name;
                @endphp
                <label
                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 p-3 transition-colors hover:bg-slate-50 dark:border-navy-600 dark:hover:bg-navy-600/40"
                    title="{{ $perm->name }}">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                        @checked(in_array($perm->name, $selected, true))
                        class="form-checkbox is-basic mt-0.5 size-5 rounded border-slate-400/70 before:bg-primary checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary dark:border-navy-400 dark:before:bg-accent dark:checked:border-accent dark:checked:bg-accent dark:indeterminate:border-accent dark:indeterminate:bg-accent">
                    <span class="text-xs-plus leading-snug text-slate-700 dark:text-navy-100">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endforeach
