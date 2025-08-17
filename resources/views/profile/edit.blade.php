<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <!-- User Avatar -->
                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-cyan-500 rounded-full flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-lg">
                        {{ substr($user->name, 0, 1) }}
                    </span>
                </div>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                        {{ $user->name }}'s Profile
                    </h2>
                    <p class="text-sm text-gray-600 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ $user->getRoleDisplayNameAttribute() }}
                        @if($user->subscriptionLevel)
                            <span class="ml-2 px-2 py-1 text-xs font-medium bg-gradient-to-r from-purple-100 to-cyan-100 text-purple-800 rounded-full">
                                {{ $user->subscriptionLevel->name }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="hidden md:flex items-center gap-3">
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">{{ number_format($l33tBytesBalance) }}</div>
                    <div class="text-xs text-gray-500">L33t Bytes</div>
                </div>
                <div class="w-8 h-8 bg-gradient-to-r from-amber-400 to-orange-500 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </div>
    </x-slot>

    <!-- Background Pattern -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50">
        <!-- Hero Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23e5e7eb" fill-opacity="0.3"%3E%3Ccircle cx="30" cy="30" r="1"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
        
        <div class="relative py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column - L33t Economy -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl p-6 border border-white/20">
                            @include('profile.partials.l33t-economy-section')
                        </div>
                    </div>

                    <!-- Right Column - Profile Settings -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Profile Information -->
                        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl p-6 sm:p-8 border border-white/20 hover:shadow-2xl transition-all duration-300">
                            <div class="max-w-2xl">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>

                        <!-- Password Update -->
                        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl p-6 sm:p-8 border border-white/20 hover:shadow-2xl transition-all duration-300">
                            <div class="max-w-2xl">
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>

                        <!-- Account Management -->
                        <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl p-6 sm:p-8 border border-white/20 hover:shadow-2xl transition-all duration-300">
                            <div class="max-w-2xl">
                                @include('profile.partials.delete-user-form')
                            </div>
                        </div>
                        
                        <!-- Quick Stats Footer -->
                        <div class="bg-gradient-to-r from-purple-500/10 to-cyan-500/10 backdrop-blur-sm rounded-2xl p-6 border border-purple-200/30">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($l33tBytesBalance) }}</div>
                                    <div class="text-xs text-gray-600">L33t Bytes</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($dailyBitsBalance) }}</div>
                                    <div class="text-xs text-gray-600">Daily Bits</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ number_format($weeklyPowerBitsBalance) }}</div>
                                    <div class="text-xs text-gray-600">Power Bits</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        @if($user->subscriptionLevel)
                                            {{ $user->subscriptionLevel->level }}
                                        @else
                                            0
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-600">L33t Level</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
