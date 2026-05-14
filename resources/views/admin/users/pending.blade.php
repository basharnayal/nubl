<x-app-layout title="{{ __('Pending & Rejected Account Approvals') }}" is-header-blur="true">
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
                        {{ __('Pending & Rejected Account Approvals') }}
                    </h2>
                </div>

                <div class="card mt-3">
                    @if($pendingUsers->isEmpty())
                        <div class="px-6 py-12 text-center text-slate-500 dark:text-navy-300">
                            {{ __('No pending or rejected approvals.') }}
                        </div>
                    @else
                        <div class="is-scrollbar-hidden min-w-full overflow-x-auto">
                            <table class="is-hoverable w-full text-left">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap rounded-tl-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">#</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Avatar') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Name') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Email') }}</th>
                                        <th class="whitespace-nowrap bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Status') }}</th>
                                        <th class="whitespace-nowrap rounded-tr-lg bg-slate-200 px-4 py-3 font-semibold uppercase text-slate-800 dark:bg-navy-800 dark:text-navy-100 lg:px-5">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingUsers as $index => $user)
                                        <tr class="border-y border-transparent border-b-slate-200 dark:border-b-navy-500">
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">{{ $index + 1 }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 sm:px-5">
                                                <div class="avatar flex size-10">
                                                    <img class="mask is-squircle" src="{{ asset('images/200x200.png') }}" alt="avatar" />
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div class="font-medium text-slate-700 dark:text-navy-100">{{ $user->name }}</div>
                                                <div class="text-xs text-slate-500 dark:text-navy-300">{{ $user->created_at->format('Y-m-d') }}</div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                <div>{{ $user->email }}</div>
                                                <div class="text-xs text-slate-500 dark:text-navy-300">{{ ucfirst($user->membership_type ?? '-') }}</div>
                                            </td>
                                            <td class="px-4 py-3 sm:px-5">
                                                @if($user->status === \App\Models\User::STATUS_PENDING_APPROVAL)
                                                    <span class="badge rounded-full bg-warning/10 text-warning dark:bg-warning/15">{{ __('Pending') }}</span>
                                                @else
                                                    <div class="space-y-0.5">
                                                        <span class="badge rounded-full bg-error/10 text-error dark:bg-error/15">{{ __('Rejected') }}</span>
                                                        @if($user->rejection_reason)
                                                            <div class="text-xs text-slate-500 dark:text-navy-300 max-w-xs truncate" title="{{ $user->rejection_reason }}">{{ Str::limit($user->rejection_reason, 40) }}</div>
                                                        @endif
                                                    </div>
                                                @endif
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
                                                                    <a href="{{ route('admin.users.application', $user) }}" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100">{{ __('View') }}</a>
                                                                </li>
                                                                @can('accounts.approve')
                                                                <li>
                                                                    <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="block">
                                                                        @csrf
                                                                        <button type="submit" class="flex h-8 w-full items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100 text-left text-success">{{ __('Approve') }}</button>
                                                                    </form>
                                                                </li>
                                                                @if($user->status === \App\Models\User::STATUS_PENDING_APPROVAL)
                                                                    <li>
                                                                        <a href="{{ route('admin.users.reject.form', $user) }}" class="flex h-8 items-center px-3 pr-8 font-medium tracking-wide outline-hidden transition-all hover:bg-slate-100 hover:text-slate-800 focus:bg-slate-100 focus:text-slate-800 dark:hover:bg-navy-600 dark:hover:text-navy-100 dark:focus:bg-navy-600 dark:focus:text-navy-100 text-error">{{ __('Reject') }}</a>
                                                                    </li>
                                                                @endif
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
