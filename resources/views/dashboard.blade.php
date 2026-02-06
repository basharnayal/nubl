@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome! 👋</h1>
            <p class="text-xl text-gray-600 mb-6">You are logged in as: <strong>{{ auth()->user()->name }}</strong></p>
            
            @if(auth()->user()->roles->count() > 0)
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    ⚠️ You have roles but RedirectByRole didn't redirect you. Check your roles:
                    <ul class="mt-2">
                        @foreach(auth()->user()->roles as $role)
                            <li>- {{ $role->name }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    ⚠️ You don't have any role assigned. Please assign a role to test the redirect.
                </div>
            @endif
            
            <p class="text-gray-500">This is the default Dashboard</p>
        </div>
    </div>
</div>
@endsection
