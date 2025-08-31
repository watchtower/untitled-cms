@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Site Monitor Management</h1>
            <p class="text-gray-600">Monitor website uptime and performance across all users</p>
        </div>
        <a href="{{ route('admin.site-monitors.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            Add Monitor
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select name="user_id" class="border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-md px-3 py-2">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Filter
            </button>
            <a href="{{ route('admin.site-monitors.index') }}" class="text-gray-600 hover:text-gray-800">
                Clear
            </a>
        </form>
    </div>

    <!-- Monitors Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monitor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Check</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($monitors as $monitor)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $monitor->name }}</div>
                                <div class="text-sm text-gray-500">{{ $monitor->url }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $monitor->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $monitor->user->subscriptionLevel?->name ?? 'No Plan' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                {{ $monitor->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $monitor->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $monitor->status === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($monitor->status) }}
                            </span>
                            @if($monitor->consecutive_failures > 0)
                                <div class="text-xs text-red-600 mt-1">
                                    {{ $monitor->consecutive_failures }} failures
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $monitor->last_checked_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $monitor->response_time_ms ? $monitor->response_time_ms . 'ms' : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('admin.site-monitors.show', $monitor) }}" 
                               class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="{{ route('admin.site-monitors.edit', $monitor) }}" 
                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <form method="POST" action="{{ route('admin.site-monitors.check', $monitor) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900">Check Now</button>
                            </form>
                            <form method="POST" action="{{ route('admin.site-monitors.destroy', $monitor) }}" 
                                  class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No site monitors found. <a href="{{ route('admin.site-monitors.create') }}" class="text-blue-600 hover:text-blue-800">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($monitors->hasPages())
        <div class="mt-6">
            {{ $monitors->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection