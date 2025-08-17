@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit User: {{ $user->name }}</h1>
            <div class="flex space-x-2">
                <a href="{{ route('admin.users.show', $user) }}" 
                   class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                    View User
                </a>
                <a href="{{ route('admin.users.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    Back to Users
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $user->name) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                            autofocus
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email', $user->email) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="Leave empty to keep current password"
                        >
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Leave empty to keep the current password.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="Only if changing password"
                        >
                    </div>
                </div>

                <!-- Access & Settings -->
                <div class="space-y-6">
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select 
                            id="role" 
                            name="role" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                        >
                            @can('assignRole', ['super_admin'])
                                <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            @endcan
                            @can('assignRole', ['admin'])
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                            @endcan
                            <option value="editor" {{ old('role', $user->role) === 'editor' ? 'selected' : '' }}>Editor</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select 
                            id="status" 
                            name="status" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                        >
                            <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subscription_level_id" class="block text-sm font-medium text-gray-700 mb-2">Subscription Level</label>
                        <select 
                            id="subscription_level_id" 
                            name="subscription_level_id" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option value="">No Subscription</option>
                            @foreach($subscriptionLevels as $level)
                                <option value="{{ $level->id }}" {{ old('subscription_level_id', $user->subscription_level_id) == $level->id ? 'selected' : '' }}>
                                    {{ $level->name }} - ${{ number_format($level->price, 2) }}/month
                                </option>
                            @endforeach
                        </select>
                        @error('subscription_level_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="subscription_active" 
                            name="subscription_active" 
                            value="1"
                            {{ old('subscription_active', $user->subscription_active) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                        <label for="subscription_active" class="ml-2 block text-sm text-gray-700">
                            Subscription is active
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="email_verified" 
                            name="email_verified" 
                            value="1"
                            {{ old('email_verified', $user->hasVerifiedEmail()) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                        <label for="email_verified" class="ml-2 block text-sm text-gray-700">
                            Email is verified
                        </label>
                    </div>

                    <!-- User Statistics -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">User Information</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Created:</span>
                                <span class="text-gray-900">{{ $user->created_at->format('M j, Y g:i A') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Last Login:</span>
                                <span class="text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Current Status:</span>
                                <span class="text-gray-900 {{ $user->isActive() ? 'text-green-600' : 'text-yellow-600' }}">
                                    {{ $user->status_display }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between">
                <div class="flex space-x-2">
                    @if($user->isActive())
                        <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button 
                                type="submit" 
                                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded"
                                onclick="return confirm('Are you sure you want to deactivate this user?')"
                            >
                                Deactivate
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button 
                                type="submit" 
                                class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded"
                            >
                                Activate
                            </button>
                        </form>
                    @endif
                </div>

                <div class="flex space-x-3">
                    <a 
                        href="{{ route('admin.users.index') }}" 
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded"
                    >
                        Cancel
                    </a>
                    <button 
                        type="submit" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Update User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection