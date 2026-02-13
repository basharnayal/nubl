<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pending & Rejected Account Approvals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">{{ session('error') }}</div>
            @endif

            @if($pendingUsers->isEmpty())
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    {{ __('No pending or rejected approvals.') }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Email') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($pendingUsers as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($user->membership_type) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->status === \App\Models\User::STATUS_PENDING_APPROVAL)
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">{{ __('Pending') }}</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">{{ __('Rejected') }}</span>
                                            @if($user->rejection_reason)
                                                <span class="block mt-1 text-xs text-gray-500 max-w-xs truncate" title="{{ $user->rejection_reason }}">{{ Str::limit($user->rejection_reason, 40) }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                                        <a href="{{ route('admin.users.application', $user) }}" class="text-nubl-teal-600 hover:text-nubl-teal-700 font-medium">{{ __('View') }}</a>
                                        <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-800 font-medium">{{ __('Approve') }}</button>
                                        </form>
                                        @if($user->status === \App\Models\User::STATUS_PENDING_APPROVAL)
                                            <a href="{{ route('admin.users.reject.form', $user) }}" class="text-red-600 hover:text-red-800 font-medium">{{ __('Reject') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
