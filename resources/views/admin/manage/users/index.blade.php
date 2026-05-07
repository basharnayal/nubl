<x-app-layout title="{{ __('User Management') }}" is-header-blur="true">
    <div class="pt-4">
        <div class="tabs mb-4 flex border-b border-slate-200 dark:border-navy-500">
            <a href="{{ route('admin.manage.users.index') }}"
                class="btn shrink-0 rounded-none border-b-2 px-4 py-2.5 font-medium {{ request()->routeIs('admin.manage.users.index') ? 'border-primary text-primary dark:border-accent dark:text-accent-light' : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100' }}">
                {{ __('Users') }}
            </a>
            <a href="{{ route('admin.users.pending') }}"
                class="btn shrink-0 rounded-none border-b-2 px-4 py-2.5 font-medium {{ request()->routeIs('admin.users.pending') ? 'border-primary text-primary dark:border-accent dark:text-accent-light' : 'border-transparent text-slate-600 hover:text-slate-800 dark:text-navy-300 dark:hover:text-navy-100' }}">
                {{ __('Pending users') }}
            </a>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
        <div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-medium tracking-wide text-slate-700 line-clamp-1 dark:text-navy-100">
                        {{ __('User Management') }}
                    </h2>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                        {{-- Filter form: Search → Role → Status → Filter --}}
                        <form method="GET" action="{{ route('admin.manage.users.index') }}" class="flex flex-wrap items-center gap-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search...') }}"
                                class="form-input form-input-lineone min-w-32 sm:w-36">
                            <select name="role" class="form-select form-select-lineone w-auto min-w-24">
                                <option value="">{{ __('All roles') }}</option>
                                @foreach(['admin','donor','recipient','provider'] as $r)
                                    <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="form-select form-select-lineone w-auto min-w-24">
                                <option value="">{{ __('All Access') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Login Enabled') }}</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Login Disabled') }}</option>
                            </select>
                            <button type="submit" class="btn size-9 rounded-full p-0 text-primary hover:bg-primary/10 focus:bg-primary/10 dark:text-accent dark:hover:bg-accent/10 dark:focus:bg-accent/10">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </form>
                        <x-lineone-button :href="route('admin.manage.users.create')" variant="primary" size="sm">
                            {{ __('Create User') }}
                        </x-lineone-button>
                    </div>
                </div>

            @php
                $activeSort = request('sort');
                $activeDir  = request('direction', 'asc');
                $sortHref   = fn(string $col) => request()->fullUrlWithQuery([
                    'sort'      => $col,
                    'direction' => ($activeSort === $col && $activeDir === 'asc') ? 'desc' : 'asc',
                    'page'      => 1,
                ]);
            @endphp

            <div class="card mt-3">
                <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                    <table class="is-hoverable w-full text-left">
                        <thead>
                            <tr>
                                <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">#</th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Avatar') }}</th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                    <a href="{{ $sortHref('name') }}" class="inline-flex items-center gap-1 hover:text-primary dark:hover:text-accent-light transition-colors">
                                        {{ __('Name') }}
                                        @if($activeSort === 'name')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-primary dark:text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $activeDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 opacity-35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                    <a href="{{ $sortHref('email') }}" class="inline-flex items-center gap-1 hover:text-primary dark:hover:text-accent-light transition-colors">
                                        {{ __('Email') }}
                                        @if($activeSort === 'email')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-primary dark:text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $activeDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 opacity-35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Role') }}</th>
                                <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">
                                    <a href="{{ $sortHref('status') }}" class="inline-flex items-center gap-1 hover:text-primary dark:hover:text-accent-light transition-colors">
                                        {{ __('Status') }}
                                        @if($activeSort === 'status')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 text-primary dark:text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $activeDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 opacity-35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $user->id }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                        <x-user-avatar :user="$user" size-class="size-10" />
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="font-medium text-slate-700 dark:text-navy-100">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-navy-300">{{ $user->created_at->format('Y-m-d') }}</div>
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div>{{ $user->email }}</div>
                                        <div class="text-xs text-slate-500 dark:text-navy-300">
                                            {{ $user->phone_number || $user->providerProfile?->phone_number ? \App\Support\PhoneHelper::formatForDisplay($user->phone_number ?? $user->providerProfile?->phone_number) : '-' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                        @php
                                            $roleNames = $user->roles->pluck('name');
                                            $roleColorMap = [
                                                'admin'     => 'bg-secondary/10 text-secondary dark:bg-secondary-light/15 dark:text-secondary-light',
                                                'donor'     => 'bg-primary/10 text-primary dark:bg-accent/15 dark:text-accent-light',
                                                'provider'  => 'bg-warning/10 text-warning dark:bg-warning/15',
                                                'recipient' => 'bg-success/10 text-success dark:bg-success/15',
                                            ];
                                        @endphp
                                        @forelse($roleNames as $roleName)
                                            <span class="badge rounded-full {{ $roleColorMap[$roleName] ?? 'bg-slate-150 text-slate-500 dark:bg-navy-500 dark:text-navy-300' }}">
                                                {{ $roleName }}
                                            </span>
                                        @empty
                                            <span class="badge rounded-full bg-slate-150 text-slate-500 dark:bg-navy-500 dark:text-navy-300">-</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 sm:px-5">
                                        <div class="flex flex-col gap-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-slate-400 dark:text-navy-400 shrink-0">{{ __('Access:') }}</span>
                                                @if($user->is_active)
                                                    <span class="badge rounded-full bg-info/10 text-info dark:bg-info/15 whitespace-nowrap">{{ __('Login Enabled') }}</span>
                                                @else
                                                    <span class="badge rounded-full bg-slate-150 text-slate-500 dark:bg-navy-500 dark:text-navy-300 whitespace-nowrap">{{ __('Login Disabled') }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-slate-400 dark:text-navy-400 shrink-0">{{ __('Approval:') }}</span>
                                                @php
                                                    [$approvalClass, $approvalLabel] = match($user->status) {
                                                        'active'           => ['bg-success/10 text-success dark:bg-success/15', __('Active')],
                                                        'pending_approval' => ['bg-warning/10 text-warning dark:bg-warning/15', __('Pending Approval')],
                                                        'rejected'         => ['bg-error/10 text-error dark:bg-error/15', __('Rejected')],
                                                        default            => ['bg-slate-150 text-slate-500 dark:bg-navy-500 dark:text-navy-300', ucfirst($user->status ?? '-')],
                                                    };
                                                @endphp
                                                <span class="badge rounded-full {{ $approvalClass }} whitespace-nowrap">{{ $approvalLabel }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                        <div x-data="usePopper({placement:'bottom-end',offset:4})" @click.outside="if(isShowPopper) isShowPopper = false" class="inline-flex">
                                            <button x-ref="popperRef" @click="isShowPopper = !isShowPopper"
                                                class="btn size-8 rounded-full p-0 hover:bg-slate-300/20 focus:bg-slate-300/20 active:bg-slate-300/25 dark:hover:bg-navy-300/20 dark:focus:bg-navy-300/20 dark:active:bg-navy-300/25">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                                </svg>
                                            </button>
                                            <div x-ref="popperRoot" class="popper-root" :class="isShowPopper && 'show'">
                                                <div class="popper-box rounded-md border border-slate-150 bg-white py-1.5 font-inter dark:border-navy-500 dark:bg-navy-700">
                                                    <ul>
                                                        <li>
                                                            <a href="{{ route('admin.manage.users.show', $user) }}" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">{{ __('View') }}</a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('admin.manage.users.edit', $user) }}" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">{{ __('Edit') }}</a>
                                                        </li>
                                                        @if($user->is_active)
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.manage.users.deactivate', $user) }}" class="block" onsubmit="return confirm('{{ __('Deactivate this user? They will not be able to log in.') }}')">
                                                                    @csrf
                                                                    <button type="submit" class="flex h-8 w-full items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100 text-left">{{ __('Deactivate') }}</button>
                                                                </form>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.manage.users.reactivate', $user) }}" class="block">
                                                                    @csrf
                                                                    <button type="submit" class="flex h-8 w-full items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100 text-left">{{ __('Reactivate') }}</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        @if($user->id !== auth()->id())
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.manage.users.destroy', $user) }}" class="block" onsubmit="return confirm('{{ __('Permanently delete this user?') }}')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="flex h-8 w-full items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100 text-left text-error">{{ __('Delete') }}</button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">{{ __('No users found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="flex flex-col justify-between space-y-4 border-t border-slate-200 px-4 py-4 dark:border-navy-500 sm:flex-row sm:items-center sm:space-y-0 sm:px-5">
                        <div class="text-xs-plus text-slate-500 dark:text-navy-300">
                            {{ __('Showing') }} {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} {{ __('of') }} {{ $users->total() }} {{ __('entries') }}
                        </div>
                        <div class="[&_.pagination]:flex [&_.pagination]:gap-1 [&_.pagination]:flex-wrap [&_.pagination_li]:rounded-lg [&_.pagination_li]:bg-slate-150 [&_.pagination_li]:dark:bg-navy-500 [&_.pagination_a]:flex [&_.pagination_a]:h-8 [&_.pagination_a]:min-w-[2rem] [&_.pagination_a]:items-center [&_.pagination_a]:justify-center [&_.pagination_a]:rounded-lg [&_.pagination_a]:px-3 [&_.pagination_a]:leading-tight [&_.pagination_a]:transition-colors [&_.pagination_a:hover]:bg-slate-300 [&_.pagination_a]:dark:hover:bg-navy-450 [&_.pagination_.active_a]:bg-primary [&_.pagination_.active_a]:text-white [&_.pagination_.active_a]:dark:bg-accent [&_.pagination_.disabled]:opacity-50">
                            {{ $users->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    </div>
</x-app-layout>
