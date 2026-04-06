<x-app-layout :title="__('rbac.page_title')" is-header-blur="true">
    <div class="pt-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-medium tracking-wide text-slate-700 dark:text-navy-100">
                    {{ __('rbac.page_title') }}
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-navy-300">
                    {{ __('rbac.page_subtitle') }}
                </p>
            </div>
            <x-lineone-button :href="route('admin.roles.create')" variant="primary" size="sm">
                {{ __('rbac.create_role') }}
            </x-lineone-button>
        </div>

        <div class="card mt-4">
            <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                <table class="is-hoverable w-full text-left">
                    <thead>
                        <tr>
                            <th
                                class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('rbac.table.role') }}</th>
                            <th
                                class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('rbac.table.permissions_count') }}</th>
                            <th
                                class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('rbac.table.type') }}</th>
                            <th
                                class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                {{ __('rbac.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            @php
                                $protected = \App\Support\ProtectedRoles::isProtected($role->name);
                            @endphp
                            <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <span class="font-medium text-slate-800 dark:text-navy-50">{{ $role->name }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $role->permissions_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    @if ($protected)
                                        <span
                                            class="badge rounded-full bg-warning/15 text-warning dark:bg-warning/20">{{ __('rbac.badge.system') }}</span>
                                    @else
                                        <span
                                            class="badge rounded-full bg-info/10 text-info dark:bg-info/15">{{ __('rbac.badge.custom') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-lineone-button :href="route('admin.roles.edit', $role)" outline
                                            variant="primary" size="sm">
                                            {{ __('rbac.edit') }}
                                        </x-lineone-button>
                                        @if (!$protected)
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                                class="inline"
                                                onsubmit="return confirm('{{ __('rbac.confirm_delete_role') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn h-8 rounded-lg border border-error/30 px-3 text-xs-plus font-medium text-error hover:bg-error/10 dark:border-error/40 dark:hover:bg-error/10">
                                                    {{ __('rbac.delete') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
