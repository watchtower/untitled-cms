@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transaction Log</h1>
                <p class="text-sm text-gray-600 mt-1">Complete history of all token transactions</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('admin.token-management.index') }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Overview
                </a>
                <a href="{{ route('admin.token-management.users') }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    User Balances
                </a>
                <a href="{{ route('admin.token-management.transactions') }}" 
                   class="border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Transaction Log
                </a>
            </nav>
        </div>

        <!-- Filters -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                        <select name="user_id" id="user_id" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Users</option>
                            @foreach(\App\Models\User::select('id', 'name', 'email')->orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="token_id" class="block text-sm font-medium text-gray-700">Token Type</label>
                        <select name="token_id" id="token_id" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Tokens</option>
                            @foreach($tokens as $token)
                                <option value="{{ $token->id }}" {{ request('token_id') == $token->id ? 'selected' : '' }}>
                                    {{ $token->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Transaction Type</label>
                        <select name="type" id="type" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            @foreach($transactionTypes as $type)
                                <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">To Date</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex items-end">
                        <div class="flex space-x-2 w-full">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Filter
                            </button>
                            <a href="{{ route('admin.token-management.transactions') }}" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Statistics -->
        @if(request()->hasAny(['user_id', 'token_id', 'type', 'date_from', 'date_to']))
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">📊</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-700">Total Transactions</p>
                            <p class="text-2xl font-bold text-blue-900">{{ number_format($transactions->total()) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">⬆️</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-700">Additions</p>
                            <p class="text-2xl font-bold text-green-900">{{ number_format($transactions->where('amount', '>', 0)->count()) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">⬇️</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-700">Deductions</p>
                            <p class="text-2xl font-bold text-red-900">{{ number_format($transactions->where('amount', '<', 0)->count()) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">🎯</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-purple-700">Net Volume</p>
                            <p class="text-2xl font-bold text-purple-900">{{ number_format($transactions->sum('amount')) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Transactions Table -->
        @if($transactions->count() > 0)
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance Change</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $transaction->created_at->format('M j, Y') }}<br>
                                    <span class="text-xs text-gray-500">{{ $transaction->created_at->format('g:i A') }}</span>
                                </td>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-gray-500">{{ number_format($transaction->balance_before) }}</span>
                                        <span class="text-gray-400">→</span>
                                        <span class="font-medium">{{ number_format($transaction->balance_after) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <div class="text-sm text-gray-900 truncate" title="{{ $transaction->reason }}">
                                        {{ $transaction->reason }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                 @switch($transaction->type)
                                                     @case('manual') bg-gray-100 text-gray-800 @break
                                                     @case('automatic') bg-blue-100 text-blue-800 @break
                                                     @case('purchase') bg-green-100 text-green-800 @break
                                                     @case('reward') bg-yellow-100 text-yellow-800 @break
                                                     @case('admin_grant') bg-indigo-100 text-indigo-800 @break
                                                     @case('admin_deduct') bg-red-100 text-red-800 @break
                                                     @case('admin_set') bg-purple-100 text-purple-800 @break
                                                     @case('bulk_grant') bg-cyan-100 text-cyan-800 @break
                                                     @default bg-gray-100 text-gray-800
                                                 @endswitch">
                                        {{ ucfirst(str_replace('_', ' ', $transaction->type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($transaction->admin)
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->admin->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $transaction->admin->email }}</div>
                                    @else
                                        <span class="text-gray-400">System</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h4.125M8.25 8.25V6.108" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No transactions found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(request()->hasAny(['user_id', 'token_id', 'type', 'date_from', 'date_to']))
                        Try adjusting your search filters.
                    @else
                        Transactions will appear here when users start using tokens.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>
@endsection