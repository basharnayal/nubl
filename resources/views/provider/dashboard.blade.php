@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Welcome Provider! 🏪</h1>
            <p class="text-xl text-gray-600 mb-6">You are logged in as: <strong>Provider</strong></p>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                ✅ RedirectByRole Middleware is working correctly!
            </div>
            <p class="text-gray-500">This is the Provider Dashboard</p>
        </div>
    </div>
</div>
@endsection
