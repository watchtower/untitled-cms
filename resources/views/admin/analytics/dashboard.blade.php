@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transaction Analytics Dashboard</h1>
                <p class="text-sm text-gray-600 mt-1">Real-time transaction analytics powered by InfluxDB</p>
            </div>
            <div class="flex items-center space-x-3">
                <form method="GET" class="flex items-center space-x-2">
                    <select name="range" class="rounded border-gray-300 text-sm" onchange="this.form.submit()">
                        <option value="-1h" {{ $timeRange === '-1h' ? 'selected' : '' }}>Last Hour</option>
                        <option value="-24h" {{ $timeRange === '-24h' ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="-7d" {{ $timeRange === '-7d' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="-30d" {{ $timeRange === '-30d' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="-90d" {{ $timeRange === '-90d' ? 'selected' : '' }}>Last 90 Days</option>
                    </select>
                </form>
                <a href="{{ route('admin.analytics.transactions.export', ['type' => 'both', 'range' => $timeRange]) }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    📊 Export Data
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Token Stats -->
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-white bg-opacity-30 rounded-md flex items-center justify-center">
                            <span class="text-purple-100 text-lg">💎</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-purple-100 truncate">Bytes Activity</dt>
                            <dd class="text-lg font-semibold">
                                @if(isset($stats['token']['tool_usage']))
                                    {{ number_format(abs($stats['token']['tool_usage'])) }} used
                                @else
                                    No activity
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Counter Stats -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-white bg-opacity-30 rounded-md flex items-center justify-center">
                            <span class="text-blue-100 text-lg">⚡</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-blue-100 truncate">Bits Activity</dt>
                            <dd class="text-lg font-semibold">
                                @if(isset($stats['counter']['tool_usage']))
                                    {{ number_format(abs($stats['counter']['tool_usage'])) }} used
                                @else
                                    No activity
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-white bg-opacity-30 rounded-md flex items-center justify-center">
                            <span class="text-green-100 text-lg">👨‍💼</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-green-100 truncate">Admin Actions</dt>
                            <dd class="text-lg font-semibold">
                                @php
                                    $adminActions = 0;
                                    if(isset($stats['token']['admin_set'])) $adminActions += abs($stats['token']['admin_set']);
                                    if(isset($stats['counter']['admin_set'])) $adminActions += abs($stats['counter']['admin_set']);
                                @endphp
                                {{ number_format($adminActions) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-gradient-to-r from-gray-500 to-gray-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-white bg-opacity-30 rounded-md flex items-center justify-center">
                            <span class="text-gray-100 text-lg">📊</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-100 truncate">InfluxDB Status</dt>
                            <dd class="text-lg font-semibold">
                                @if($isInfluxConnected)
                                    <span class="text-green-300">● Online</span>
                                @else
                                    <span class="text-red-300">● Offline</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Token Transactions -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="bg-purple-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-purple-900">Recent Bytes Transactions</h3>
                        <a href="{{ route('admin.analytics.token-transactions') }}" class="text-purple-600 hover:text-purple-900 text-sm font-medium">
                            View All →
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @if(count($recentTokenTransactions) > 0)
                        <div class="space-y-3">
                            @foreach($recentTokenTransactions as $transaction)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if($transaction['amount'] >= 0)
                                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            @else
                                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $transaction['amount'] >= 0 ? '+' : '' }}{{ number_format($transaction['amount']) }} 💎
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $transaction['reason'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($transaction['time'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No recent transactions</p>
                    @endif
                </div>
            </div>

            <!-- Recent Counter Transactions -->
            <div class="bg-white border border-gray-200 rounded-lg">
                <div class="bg-blue-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-blue-900">Recent Bits Transactions</h3>
                        <a href="{{ route('admin.analytics.counter-transactions') }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                            View All →
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @if(count($recentCounterTransactions) > 0)
                        <div class="space-y-3">
                            @foreach($recentCounterTransactions as $transaction)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            @if($transaction['count_change'] >= 0)
                                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            @else
                                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $transaction['count_change'] >= 0 ? '+' : '' }}{{ number_format($transaction['count_change']) }} ⚡
                                            </p>
                                            <p class="text-xs text-gray-500">{{ $transaction['reason'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($transaction['time'])->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No recent transactions</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">InfluxDB Analytics</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>All new transactions are automatically recorded in InfluxDB for real-time analytics and historical reporting. This provides better performance and scalability compared to traditional MySQL queries for time-series data.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection