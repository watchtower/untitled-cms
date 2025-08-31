@extends('admin.layout')

@section('content')
<div class="space-y-6" x-data="tokenManagement()" x-init="init()">
    <!-- Header with Actions -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
        <div class="px-6 py-4">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <span class="text-4xl mr-3">🚀</span>
                        L33t Bytes Management
                    </h1>
                    <p class="text-sm text-gray-600 mt-2">Advanced token economy management with real-time analytics</p>
                </div>
                <div class="flex space-x-3">
                    <button @click="refreshData()" 
                            :class="refreshing ? 'opacity-50 cursor-not-allowed' : ''"
                            :disabled="refreshing"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <svg x-show="!refreshing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <svg x-show="refreshing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="refreshing ? 'Refreshing...' : 'Refresh Data'"></span>
                    </button>
                    <button @click="showBulkOperations = !showBulkOperations"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Bulk Operations
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-violet-500 via-purple-500 to-indigo-600 p-6 rounded-xl text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-violet-100 mb-1">Total Users</p>
                    <p class="text-3xl font-bold" x-text="formatNumber({{ $totalUsers }})">{{ number_format($totalUsers) }}</p>
                    <p class="text-xs text-violet-200 mt-2">Active economy participants</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">👥</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 via-green-500 to-teal-600 p-6 rounded-xl text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-100 mb-1">Total Transactions</p>
                    <p class="text-3xl font-bold" x-text="formatNumber({{ $totalTransactions }})">{{ number_format($totalTransactions) }}</p>
                    <p class="text-xs text-emerald-200 mt-2">All-time token activity</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">📊</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 via-red-500 to-pink-600 p-6 rounded-xl text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-100 mb-1">Active Token Types</p>
                    <p class="text-3xl font-bold">{{ $tokens->where('is_active', true)->count() }}</p>
                    <p class="text-xs text-orange-200 mt-2">Currently in circulation</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">🪙</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 via-cyan-500 to-teal-600 p-6 rounded-xl text-white shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100 mb-1">Recent Activity</p>
                    <p class="text-3xl font-bold">{{ $recentTransactions->count() }}</p>
                    <p class="text-xs text-blue-200 mt-2">Transactions in 24h</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">⚡</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Operations Modal -->
    <div x-show="showBulkOperations" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="showBulkOperations = false">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white" @click.stop>
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Bulk Token Operations</h3>
                    <button @click="showBulkOperations = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form method="POST" action="{{ route('admin.token-management.bulk-operation') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Operation Type</label>
                            <select name="operation" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Operation</option>
                                <option value="grant_to_all">Grant to All Users</option>
                                <option value="grant_to_subscription">Grant to Subscription Level</option>
                                <option value="reset_balances">Reset All Balances</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Token Type</label>
                            <select name="token_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Token</option>
                                @foreach($tokens as $token)
                                    <option value="{{ $token->id }}">{{ $token->name }} ({{ $token->icon }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                            <input type="number" name="amount" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter amount">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subscription Level</label>
                            <select name="subscription_level_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Levels</option>
                                @foreach(\App\Models\SubscriptionLevel::all() as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <textarea name="reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter reason for this operation..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" @click="showBulkOperations = false" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">Execute Operation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Advanced Tab Navigation -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6">
                <a href="{{ route('admin.token-management.index', ['tab' => 'overview']) }}" 
                   class="{{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Overview
                </a>
                <a href="{{ route('admin.token-management.users') }}" 
                   class="{{ $activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    User Balances
                </a>
                <a href="{{ route('admin.token-management.transactions') }}" 
                   class="{{ $activeTab === 'transactions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    Transaction Log
                </a>
            </nav>
        </div>

        <!-- Enhanced Token Statistics -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Token Performance Analytics</h3>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Real-time data</span>
                </div>
            </div>
            
            <div class="space-y-6">
                @foreach($tokenStats as $stat)
                    <div class="bg-gradient-to-r from-gray-50 via-white to-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center text-3xl shadow-lg" style="background: linear-gradient(135deg, {{ $stat['token']->color ?? '#6366f1' }}22, {{ $stat['token']->color ?? '#6366f1' }}44);">
                                    {{ $stat['token']->icon ?? '🪙' }}
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-xl font-bold text-gray-900">{{ $stat['token']->name }}</h4>
                                    <p class="text-sm text-gray-600 max-w-md">{{ $stat['token']->description }}</p>
                                    <div class="flex items-center mt-2">
                                        @if($stat['token']->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Token Metrics Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-white p-4 rounded-lg shadow-sm border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Total in Circulation</p>
                                        <p class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($stat['total_in_circulation']) }}</p>
                                    </div>
                                    <div class="p-2 bg-indigo-100 rounded-lg">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white p-4 rounded-lg shadow-sm border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Active Holders</p>
                                        <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stat['users_with_balance']) }}</p>
                                    </div>
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white p-4 rounded-lg shadow-sm border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Weekly Activity</p>
                                        <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($stat['recent_transactions']) }}</p>
                                    </div>
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white p-4 rounded-lg shadow-sm border">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Penetration Rate</p>
                                        <p class="text-2xl font-bold text-purple-600 mt-1">{{ $totalUsers > 0 ? number_format(($stat['users_with_balance'] / $totalUsers) * 100, 1) : 0 }}%</p>
                                    </div>
                                    <div class="p-2 bg-purple-100 rounded-lg">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Token Actions -->
                        <div class="flex justify-end mt-4 space-x-2">
                            <button class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View Details
                            </button>
                            <button class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-100 border border-indigo-200 rounded-lg hover:bg-indigo-200 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Manage
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Enhanced Recent Transactions -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Live Transaction Feed</h3>
                <div class="flex items-center space-x-2">
                    <div class="flex items-center space-x-2 text-sm text-green-600">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Live updates</span>
                    </div>
                    <button class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            @if($recentTransactions->count() > 0)
                <div class="space-y-4">
                    @foreach($recentTransactions as $transaction)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-4">
                                <!-- User Avatar -->
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                    {{ substr($transaction->user->name, 0, 2) }}
                                </div>
                                
                                <!-- Transaction Details -->
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium text-gray-900">{{ $transaction->user->name }}</span>
                                        <span class="text-gray-400">•</span>
                                        <span class="text-sm text-gray-600">{{ $transaction->user->email }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="text-sm text-gray-500">{{ ucfirst($transaction->type) }}</span>
                                        <span class="text-gray-400">•</span>
                                        <span class="text-sm text-gray-600">{{ $transaction->reason }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Token & Amount -->
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-2xl">{{ $transaction->token->icon ?? '🪙' }}</span>
                                        <span class="font-medium text-gray-900">{{ $transaction->token->name }}</span>
                                    </div>
                                    <div class="flex items-center justify-end space-x-2 mt-1">
                                        <span class="text-lg font-bold {{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Time & Admin -->
                                <div class="text-right">
                                    <div class="text-sm text-gray-900 font-medium">
                                        {{ $transaction->admin?->name ?? 'System' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $transaction->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- View All Transactions -->
                <div class="mt-6 text-center">
                    <a href="{{ route('admin.token-management.transactions') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        View All Transactions
                    </a>
                </div>
            @else
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.125M8.25 8.25V6.108" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Recent Activity</h3>
                    <p class="text-gray-500 mb-4">Token transactions will appear here as users interact with the economy.</p>
                    <button @click="refreshData()" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh Data
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function tokenManagement() {
    return {
        refreshing: false,
        showBulkOperations: false,
        
        init() {
            // Initialize any charts or real-time updates here
            console.log('L33t Bytes Management System initialized');
        },
        
        refreshData() {
            this.refreshing = true;
            // Simulate API call
            setTimeout(() => {
                location.reload();
            }, 1500);
        },
        
        formatNumber(number) {
            if (number >= 1000000) {
                return (number / 1000000).toFixed(1) + 'M';
            }
            if (number >= 1000) {
                return (number / 1000).toFixed(1) + 'K';
            }
            return number.toString();
        }
    }
}
</script>
@endsection