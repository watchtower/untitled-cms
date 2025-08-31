<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Page Header -->
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-12 w-12 flex-shrink-0">
                                <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-sm">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
                                <p class="text-sm text-gray-600 mt-1">Manage your account information and economy status</p>
                            </div>
                        </div>
                    </div>

                    <!-- Economy Section -->
                    <div class="mb-8">
                        <div class="bg-gray-50 rounded-lg p-6">
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Economy</h2>
                                    <p class="text-sm text-gray-600 mt-1">Your subscription level and currency balances</p>
                                </div>
                                @if($user->subscriptionLevel)
                                    @php
                                        $levelColors = [
                                            1 => 'bg-green-100 text-green-800 ring-green-600/20',
                                            2 => 'bg-blue-100 text-blue-800 ring-blue-600/20', 
                                            3 => 'bg-purple-100 text-purple-800 ring-purple-600/20'
                                        ];
                                        $colorClass = $levelColors[$user->subscriptionLevel->level] ?? 'bg-gray-100 text-gray-800 ring-gray-600/20';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset {{ $colorClass }}">
                                        {{ $user->subscriptionLevel->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800 ring-1 ring-inset ring-gray-600/20">
                                        No Subscription
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <!-- Bytes -->
                                <div class="bg-white rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-md bg-amber-100 flex items-center justify-center">
                                                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-900">Bytes</p>
                                            <p class="text-xs text-gray-500">Permanent tokens</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-semibold text-gray-900">{{ number_format($bytesBalance) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bits Balances -->
                                @foreach($userCounters as $userCounter)
                                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="h-8 w-8 rounded-md bg-blue-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $userCounter->counterType->name }}</p>
                                                <p class="text-xs text-gray-500">Resets {{ $userCounter->counterType->reset_frequency }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-semibold text-gray-900">{{ number_format($userCounter->current_count) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="space-y-8">
                        <!-- Profile Information -->
                        <div class="border-b border-gray-200 pb-8">
                            @include('profile.partials.update-profile-information-form')
                        </div>

                        <!-- Update Password -->
                        <div class="border-b border-gray-200 pb-8">
                            @include('profile.partials.update-password-form')
                        </div>

                        <!-- Delete Account -->
                        <div>
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>