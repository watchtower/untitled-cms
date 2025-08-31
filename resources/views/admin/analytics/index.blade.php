@extends('admin.layout')

@section('content')
<div class="space-y-6" x-data="analyticsManager()" x-init="init()">
    <!-- Header with Time Period Selector -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <span class="text-4xl mr-3">📊</span>
                        Economy Analytics
                    </h1>
                    <p class="text-sm text-gray-600 mt-2">Comprehensive analytics and insights for your token economy</p>
                </div>
                <div class="flex items-center space-x-3">
                    <select x-model="selectedPeriod" @change="updatePeriod()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                    </select>
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
                        <span x-text="refreshing ? 'Refreshing...' : 'Refresh'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Economic Health Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-green-500 via-emerald-500 to-teal-600 p-6 rounded-xl text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-100 mb-1">Economy Health Score</p>
                    <p class="text-3xl font-bold">{{ number_format($economicHealth['activity_score'], 1) }}</p>
                    <p class="text-xs text-green-200 mt-2">Daily avg transactions</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">💪</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 p-6 rounded-xl text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100 mb-1">Token Velocity</p>
                    <p class="text-3xl font-bold">{{ number_format($economicHealth['token_velocity'], 2) }}x</p>
                    <p class="text-xs text-blue-200 mt-2">How often tokens circulate</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">🔄</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 via-red-500 to-pink-600 p-6 rounded-xl text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-100 mb-1">Counter Utilization</p>
                    <p class="text-3xl font-bold">{{ number_format($economicHealth['counter_utilization'], 1) }}%</p>
                    <p class="text-xs text-orange-200 mt-2">Of allocated counters used</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">⚡</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 via-pink-500 to-rose-600 p-6 rounded-xl text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-100 mb-1">User Engagement</p>
                    <p class="text-3xl font-bold">{{ number_format($userEngagement['engagement_rate'], 1) }}%</p>
                    <p class="text-xs text-purple-200 mt-2">Of users actively participating</p>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <span class="text-3xl">👥</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Token Transaction Volume Chart -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Token Transaction Volume</h3>
                <p class="text-sm text-gray-600">Daily transaction volume by token type</p>
            </div>
            <div class="p-6">
                <div class="relative h-80">
                    <canvas id="tokenVolumeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Counter Activity Chart -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Counter Activity</h3>
                <p class="text-sm text-gray-600">Daily counter transactions</p>
            </div>
            <div class="p-6">
                <div class="relative h-80">
                    <canvas id="counterActivityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Distribution Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Token Distribution -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Token Distribution</h3>
                <p class="text-sm text-gray-600">Current token supply by type</p>
            </div>
            <div class="p-6">
                <div class="relative h-80">
                    <canvas id="tokenDistributionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Subscription Level Distribution -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Subscription Distribution</h3>
                <p class="text-sm text-gray-600">Users by subscription level</p>
            </div>
            <div class="p-6">
                <div class="relative h-80">
                    <canvas id="subscriptionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- User Engagement Trends -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">User Engagement Trends</h3>
            <p class="text-sm text-gray-600">Daily active users over time</p>
        </div>
        <div class="p-6">
            <div class="relative h-80">
                <canvas id="engagementChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Token Performance Table -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Token Performance</h3>
                <p class="text-sm text-gray-600">Detailed token metrics and statistics</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Circulation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Size</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($tokenMetrics as $metric)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">{{ $metric['token']->icon ?? '🪙' }}</span>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $metric['token']->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $metric['token']->symbol }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['total_in_circulation']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['active_users']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['transaction_volume']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['avg_transaction_size'], 1) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Counter Performance Table -->
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Counter Performance</h3>
                <p class="text-sm text-gray-600">Detailed counter metrics and statistics</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Counter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allocated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Change</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($counterMetrics as $metric)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3">{{ $metric['counter_type']->icon ?? '⚡' }}</span>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $metric['counter_type']->name }}</div>
                                            <div class="text-sm text-gray-500">{{ ucfirst($metric['counter_type']->reset_frequency) }} reset</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['total_allocated']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['active_users']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['transaction_count']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($metric['avg_transaction_size'], 1) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function analyticsManager() {
    return {
        selectedPeriod: '{{ $period }}',
        refreshing: false,
        charts: {},
        
        init() {
            this.initializeCharts();
            this.startAutoRefresh();
        },
        
        initializeCharts() {
            // Token Volume Chart
            const tokenCtx = document.getElementById('tokenVolumeChart').getContext('2d');
            this.charts.tokenVolume = new Chart(tokenCtx, {
                type: 'line',
                data: @json($tokenChartData),
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: false
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Counter Activity Chart
            const counterCtx = document.getElementById('counterActivityChart').getContext('2d');
            this.charts.counterActivity = new Chart(counterCtx, {
                type: 'bar',
                data: @json($counterChartData),
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Token Distribution Chart
            const tokenDistCtx = document.getElementById('tokenDistributionChart').getContext('2d');
            this.charts.tokenDistribution = new Chart(tokenDistCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($tokenDistribution->pluck('token')),
                    datasets: [{
                        data: @json($tokenDistribution->pluck('value')),
                        backgroundColor: @json($tokenDistribution->pluck('color')),
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Subscription Chart
            const subCtx = document.getElementById('subscriptionChart').getContext('2d');
            this.charts.subscription = new Chart(subCtx, {
                type: 'pie',
                data: {
                    labels: @json($subscriptionAnalytics->pluck('name')),
                    datasets: [{
                        data: @json($subscriptionAnalytics->pluck('users')),
                        backgroundColor: [
                            '#6366f1', '#8b5cf6', '#a855f7', '#c084fc', '#d8b4fe'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Engagement Chart
            const engCtx = document.getElementById('engagementChart').getContext('2d');
            this.charts.engagement = new Chart(engCtx, {
                type: 'line',
                data: {
                    labels: @json($tokenChartData['labels']),
                    datasets: [{
                        label: 'Daily Active Users',
                        data: @json($userEngagement['daily_active_users']),
                        borderColor: '#10b981',
                        backgroundColor: '#10b98120',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },
        
        updatePeriod() {
            window.location.href = `?period=${this.selectedPeriod}`;
        },
        
        refreshData() {
            this.refreshing = true;
            
            fetch(`/admin/analytics/realtime?period=${this.selectedPeriod}`)
                .then(response => response.json())
                .then(data => {
                    // Update charts with new data
                    console.log('Updated analytics data:', data);
                    this.refreshing = false;
                })
                .catch(error => {
                    console.error('Error refreshing analytics:', error);
                    this.refreshing = false;
                });
        },
        
        startAutoRefresh() {
            setInterval(() => {
                if (!this.refreshing) {
                    this.refreshData();
                }
            }, 60000); // Refresh every minute
        }
    }
}
</script>
@endsection