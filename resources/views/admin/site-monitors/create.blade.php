@extends('admin.layout')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Create Site Monitor</h1>
        <p class="text-gray-600">Add a new website to monitor for uptime and performance</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.site-monitors.store') }}">
            @csrf
            
            <div class="space-y-6">
                <!-- User Selection -->
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                    <select name="user_id" id="user_id" required
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Monitor Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Monitor Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="My Website">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- URL -->
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700">Website URL</label>
                    <input type="url" name="url" id="url" value="{{ old('url') }}" required
                           class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://example.com">
                    @error('url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Check Interval -->
                <div>
                    <label for="check_interval_minutes" class="block text-sm font-medium text-gray-700">Check Interval (minutes)</label>
                    <select name="check_interval_minutes" id="check_interval_minutes"
                            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="1" {{ old('check_interval_minutes') == 1 ? 'selected' : '' }}>Every minute</option>
                        <option value="5" {{ old('check_interval_minutes', 5) == 5 ? 'selected' : '' }}>Every 5 minutes</option>
                        <option value="10" {{ old('check_interval_minutes') == 10 ? 'selected' : '' }}>Every 10 minutes</option>
                        <option value="15" {{ old('check_interval_minutes') == 15 ? 'selected' : '' }}>Every 15 minutes</option>
                        <option value="30" {{ old('check_interval_minutes') == 30 ? 'selected' : '' }}>Every 30 minutes</option>
                        <option value="60" {{ old('check_interval_minutes') == 60 ? 'selected' : '' }}>Every hour</option>
                    </select>
                    @error('check_interval_minutes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notifications -->
                <div class="flex items-center">
                    <input type="checkbox" name="notifications_enabled" id="notifications_enabled" 
                           value="1" {{ old('notifications_enabled', true) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="notifications_enabled" class="ml-2 block text-sm text-gray-700">
                        Enable notifications for failures
                    </label>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.site-monitors.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Create Monitor
                </button>
            </div>
        </form>
    </div>
</div>
@endsection