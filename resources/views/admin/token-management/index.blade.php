@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">L33t Bytes Management</h1>
                <p class="text-sm text-gray-600 mt-1">Manage permanent tokens and user balances</p>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 rounded-lg text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">💎</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-indigo-100">Total Users</p>
                        <p class="text-2xl font-bold">{{ number_format($totalUsers) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4 rounded-lg text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">📊</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-100">Total Transactions</p>
                        <p class="text-2xl font-bold">{{ number_format($totalTransactions) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-orange-500 to-red-500 p-4 rounded-lg text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">🪙</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-orange-100">Active Token Types</p>
                        <p class="text-2xl font-bold">{{ $tokens->where('is_active', true)->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-4 rounded-lg text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-2xl">⚡</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-100">Recent Activity</p>
                        <p class="text-2xl font-bold">{{ $recentTransactions->count() }}</p>
                        <p class="text-xs text-blue-100">Last 24h</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('admin.token-management.index', ['tab' => 'overview']) }}" 
                   class="{{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Overview
                </a>
                <a href="{{ route('admin.token-management.users') }}" 
                   class="{{ $activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    User Balances
                </a>
                <a href="{{ route('admin.token-management.transactions') }}" 
                   class="{{ $activeTab === 'transactions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Transaction Log
                </a>
            </nav>
        </div>

        <!-- Token Statistics -->
        <div class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900">Token Statistics</h3>
            
            @foreach($tokenStats as $stat)
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3" style="color: {{ $stat['token']->color }}">{{ $stat['token']->icon }}</span>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ $stat['token']->name }}</h4>
                                <p class="text-sm text-gray-600">{{ $stat['token']->description }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p class="text-2xl font-bold text-indigo-600">{{ number_format($stat['total_in_circulation']) }}</p>
                                    <p class="text-xs text-gray-500">In Circulation</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-green-600">{{ number_format($stat['users_with_balance']) }}</p>
                                    <p class="text-xs text-gray-500">Users with Balance</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-orange-600">{{ number_format($stat['recent_transactions']) }}</p>
                                    <p class="text-xs text-gray-500">Recent Transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Recent Transactions -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Transactions</h3>
            
            @if($recentTransactions->count() > 0)
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentTransactions as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $transaction->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center">
                                            <span class="mr-2" style="color: {{ $transaction->token->color }}">{{ $transaction->token->icon }}</span>
                                            <span class="text-sm text-gray-900">{{ $transaction->token->name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium {{ $transaction->isAddition() ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction->formatted_amount }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $transaction->reason }}</div>
                                        <div class="text-sm text-gray-500">{{ ucfirst($transaction->type) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->admin?->name ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->created_at->format('M j, Y g:i A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.125M8.25 8.25V6.108" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No transactions found</h3>
                    <p class="mt-1 text-sm text-gray-500">Token transactions will appear here when users start using L33t Bytes.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection