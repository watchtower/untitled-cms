@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-blue-900">⚡ Bits Transactions</h1>
                <p class="text-sm text-gray-600 mt-1">Real-time counter transaction analytics powered by InfluxDB</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.analytics.dashboard') }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    ← Dashboard
                </a>
                <a href="{{ route('admin.analytics.transactions.export', ['type' => 'counter', 'range' => $timeRange, 'user_id' => $userId]) }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    📊 Export Data
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
                    <select name="range" class="w-full rounded border-gray-300 text-sm">
                        <option value="-1h" {{ $timeRange === '-1h' ? 'selected' : '' }}>Last Hour</option>
                        <option value="-24h" {{ $timeRange === '-24h' ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="-7d" {{ $timeRange === '-7d' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="-30d" {{ $timeRange === '-30d' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="-90d" {{ $timeRange === '-90d' ? 'selected' : '' }}>Last 90 Days</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                    <select name="user_id" class="w-full rounded border-gray-300 text-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Limit</label>
                    <select name="limit" class="w-full rounded border-gray-300 text-sm">
                        <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ $limit == 200 ? 'selected' : '' }}>200</option>
                        <option value="500" {{ $limit == 500 ? 'selected' : '' }}>500</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        @if(count($stats) > 0)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-green-800">Total Added</h3>
                <p class="text-2xl font-bold text-green-900">
                    +{{ number_format(abs($stats['admin_set'] ?? 0)) }} ⚡
                </p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-red-800">Total Used</h3>
                <p class="text-2xl font-bold text-red-900">
                    -{{ number_format(abs($stats['usage'] ?? 0)) }} ⚡
                </p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800">Tool Usage</h3>
                <p class="text-2xl font-bold text-blue-900">
                    {{ number_format(abs($stats['tool_usage'] ?? 0)) }} ⚡
                </p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800">Net Change</h3>
                <p class="text-2xl font-bold text-blue-900">
                    @php
                        $netChange = collect($stats)->filter(function($value) {
                            return is_numeric($value);
                        })->sum();
                    @endphp
                    {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange) }} ⚡
                </p>
            </div>
        </div>
        @endif

        <!-- Transactions Table -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-blue-900">Transaction History</h3>
            </div>
            
            @if(count($formattedTransactions) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Counter Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($formattedTransactions as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($transaction['time'])->format('M j, Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        User #{{ $transaction['user_id'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $transaction['counter_slug'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($transaction['count_change'] >= 0)
                                            <span class="text-green-600">+{{ number_format($transaction['count_change']) }} ⚡</span>
                                        @else
                                            <span class="text-red-600">{{ number_format($transaction['count_change']) }} ⚡</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($transaction['count_before']) }} → {{ number_format($transaction['count_after']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-xs truncate">
                                        {{ $transaction['reason'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $transaction['type'] === 'admin_set' ? 'bg-blue-100 text-blue-800' : 
                                               ($transaction['type'] === 'usage' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucwords(str_replace('_', ' ', $transaction['type'])) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($transaction['is_admin_action'])
                                            Admin #{{ $transaction['admin_id'] }}
                                        @else
                                            System
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center">
                    <div class="text-gray-400 text-6xl mb-4">⚡</div>
                    <p class="text-gray-500 text-lg mb-2">No counter transactions found</p>
                    <p class="text-gray-400 text-sm">Try adjusting your filters or time range</p>
                </div>
            @endif
        </div>

        <!-- Info Panel -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Real-time Analytics</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>This data is sourced directly from InfluxDB for optimal performance. All new counter transactions are automatically recorded in real-time.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection