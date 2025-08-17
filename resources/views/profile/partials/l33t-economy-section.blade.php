<section class="space-y-6">
    <header>
        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
            <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-cyan-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            L33t Economy Status
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            Track your L33t journey, Bytes balance, and Bits allocation
        </p>
    </header>

    <!-- Subscription Level -->
    <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg p-6 border border-gray-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                @if($user->subscriptionLevel)
                    @php
                        $levelColors = [
                            1 => 'from-green-400 to-emerald-500',
                            2 => 'from-blue-400 to-cyan-500', 
                            3 => 'from-purple-400 to-pink-500'
                        ];
                        $levelIcons = [
                            1 => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                            2 => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                            3 => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'
                        ];
                        $colorClass = $levelColors[$user->subscriptionLevel->level] ?? 'from-gray-400 to-gray-500';
                        $iconPath = $levelIcons[$user->subscriptionLevel->level] ?? $levelIcons[1];
                    @endphp
                    
                    <div class="w-12 h-12 bg-gradient-to-r {{ $colorClass }} rounded-full flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
                        </svg>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $user->subscriptionLevel->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $user->subscriptionLevel->description }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs font-medium px-2 py-1 bg-gradient-to-r {{ $colorClass }} text-white rounded-full">
                                Level {{ $user->subscriptionLevel->level }}
                            </span>
                            @if($user->hasActiveSubscription())
                                <span class="text-xs font-medium px-2 py-1 bg-green-100 text-green-800 rounded-full">
                                    Active
                                </span>
                            @else
                                <span class="text-xs font-medium px-2 py-1 bg-gray-100 text-gray-800 rounded-full">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">No Subscription</h3>
                        <p class="text-sm text-gray-600">Join the L33t community to unlock your potential</p>
                    </div>
                @endif
            </div>
            
            @if($user->subscriptionLevel && $user->subscriptionLevel->level < 3)
                <button class="px-4 py-2 bg-gradient-to-r from-purple-500 to-cyan-500 text-white rounded-lg font-medium hover:from-purple-600 hover:to-cyan-600 transition-all duration-200 shadow-md hover:shadow-lg">
                    Upgrade
                </button>
            @endif
        </div>
    </div>

    <!-- Economy Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- L33t Bytes -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-amber-400 to-orange-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">L33t Bytes</h4>
                        <p class="text-xs text-gray-500">Permanent tokens</p>
                    </div>
                </div>
            </div>
            
            <div class="text-2xl font-bold text-gray-900 mb-2">
                {{ number_format($l33tBytesBalance) }}
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php
                    $maxBytes = match($user->subscriptionLevel?->level) {
                        1 => 1000,
                        2 => 5000, 
                        3 => 10000,
                        default => 100
                    };
                    $percentage = $maxBytes > 0 ? min(($l33tBytesBalance / $maxBytes) * 100, 100) : 0;
                @endphp
                <div class="bg-gradient-to-r from-amber-400 to-orange-500 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ $percentage }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ number_format($maxBytes) }} max capacity</p>
        </div>

        <!-- Daily Bits -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-emerald-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Daily Bits</h4>
                        <p class="text-xs text-gray-500">Resets daily</p>
                    </div>
                </div>
            </div>
            
            <div class="text-2xl font-bold text-gray-900 mb-2">
                {{ number_format($dailyBitsBalance) }}
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php
                    $maxDailyBits = match($user->subscriptionLevel?->level) {
                        1 => 100,
                        2 => 500,
                        3 => 999999, // Unlimited
                        default => 10
                    };
                    $dailyPercentage = $maxDailyBits === 999999 ? 100 : min(($dailyBitsBalance / $maxDailyBits) * 100, 100);
                @endphp
                <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ $dailyPercentage }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                @if($maxDailyBits === 999999)
                    Unlimited
                @else
                    {{ number_format($maxDailyBits) }} daily limit
                @endif
            </p>
        </div>

        <!-- Weekly Power Bits -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-400 to-pink-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Power Bits</h4>
                        <p class="text-xs text-gray-500">Resets weekly</p>
                    </div>
                </div>
            </div>
            
            <div class="text-2xl font-bold text-gray-900 mb-2">
                {{ number_format($weeklyPowerBitsBalance) }}
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php
                    $maxWeeklyBits = match($user->subscriptionLevel?->level) {
                        1 => 50,
                        2 => 250,
                        3 => 999999, // Unlimited
                        default => 5
                    };
                    $weeklyPercentage = $maxWeeklyBits === 999999 ? 100 : min(($weeklyPowerBitsBalance / $maxWeeklyBits) * 100, 100);
                @endphp
                <div class="bg-gradient-to-r from-purple-400 to-pink-500 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ $weeklyPercentage }}%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                @if($maxWeeklyBits === 999999)
                    Unlimited
                @else
                    {{ number_format($maxWeeklyBits) }} weekly limit
                @endif
            </p>
        </div>
    </div>

    <!-- Subscription Features -->
    @if($user->subscriptionLevel && $user->subscriptionLevel->features)
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-6 border border-indigo-200">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Your {{ $user->subscriptionLevel->name }} Benefits
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($user->subscriptionLevel->features as $feature)
                    <div class="flex items-center gap-3 text-sm">
                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-gray-700">{{ $feature }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>