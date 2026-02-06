@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Role Test Page</h1>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">Current User Info:</h2>
                <p><strong>Name:</strong> {{ auth()->user()->name }}</p>
                <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">Assigned Roles:</h2>
                @if(count($roles) > 0)
                    <ul class="list-disc list-inside">
                        @foreach($roles as $role)
                            <li class="text-lg">{{ $role }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-red-600 font-semibold">No roles assigned!</p>
                @endif
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">Role Checks:</h2>
                <ul class="space-y-2">
                    <li>hasRole('admin'): <span class="font-semibold">{{ auth()->user()->hasRole('admin') ? '✅ Yes' : '❌ No' }}</span></li>
                    <li>hasRole('donor'): <span class="font-semibold">{{ auth()->user()->hasRole('donor') ? '✅ Yes' : '❌ No' }}</span></li>
                    <li>hasRole('recipient'): <span class="font-semibold">{{ auth()->user()->hasRole('recipient') ? '✅ Yes' : '❌ No' }}</span></li>
                    <li>hasRole('provider'): <span class="font-semibold">{{ auth()->user()->hasRole('provider') ? '✅ Yes' : '❌ No' }}</span></li>
                </ul>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">Test Redirect:</h2>
                <p class="mb-4">Click the button below to test the RedirectByRole middleware:</p>
                <a href="{{ route('dashboard') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                    Go to Dashboard (Test Redirect)
                </a>
            </div>
            
            <div class="mt-8 p-4 bg-gray-100 rounded">
                <h3 class="font-semibold mb-2">Expected Behavior:</h3>
                <ul class="list-disc list-inside space-y-1">
                    <li>If you have <strong>admin</strong> role → Should redirect to /admin/dashboard</li>
                    <li>If you have <strong>donor</strong> role → Should redirect to /donor/dashboard</li>
                    <li>If you have <strong>recipient</strong> role → Should redirect to /recipient/dashboard</li>
                    <li>If you have <strong>provider</strong> role → Should redirect to /provider/dashboard</li>
                    <li>If you have <strong>no role</strong> → Should stay on default dashboard</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
