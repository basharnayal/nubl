<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Provider Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold">Store Status</h3>
                        <p class="text-sm text-gray-500">
                            Current Status:
                            <span class="font-bold {{ auth()->user()->is_active ? 'text-green-600' : 'text-red-600' }}">
                                {{ auth()->user()->is_active ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ auth()->user()->is_active ? 'Your menu is visible to recipients.' : 'Your menu is hidden from recipients.' }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('provider.profile.toggle-active') }}">
                        @csrf
                        <button type="submit" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer" {{ auth()->user()->is_active ? 'checked' : '' }} disabled>
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                            </div>
                            <span
                                class="ml-3 text-sm font-medium text-gray-900 border border-gray-300 rounded px-3 py-1 hover:bg-gray-50 transition">
                                {{ auth()->user()->is_active ? 'Pause Store' : 'Activate Store' }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>

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
</x-app-layout>