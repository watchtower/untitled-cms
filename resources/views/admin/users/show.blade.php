@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">User Details: {{ $user->name }}</h1>
            <div class="flex space-x-2">
                @can('update', $user)
                    <a href="{{ route('admin.users.edit', $user) }}" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Edit User
                    </a>
                @endcan
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- User Profile -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 overflow-hidden rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-20 w-20 rounded-full bg-indigo-500 flex items-center justify-center">
                                <span class="text-white font-bold text-2xl">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ $user->name }}</dt>
                                <dd class="text-lg text-gray-900">{{ $user->email }}</dd>
                                <dd class="mt-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $user->role === 'super_admin' ? 'bg-purple-100 text-purple-800' : 
                                           ($user->role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                        {{ $user->role_display_name }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Quick Actions -->
                <div class="bg-gray-50 overflow-hidden rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        @can('update', $user)
                            @if($user->isActive())
                                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button 
                                        type="submit" 
                                        class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded"
                                        onclick="return confirm('Are you sure you want to deactivate this user?')"
                                    >
                                        Deactivate User
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button 
                                        type="submit" 
                                        class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Activate User
                                    </button>
                                </form>
                            @endif

                            @if($user->hasVerifiedEmail())
                                <form method="POST" action="{{ route('admin.users.unverify-email', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button 
                                        type="submit" 
                                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded"
                                        onclick="return confirm('Are you sure you want to remove email verification?')"
                                    >
                                        Unverify Email
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.verify-email', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button 
                                        type="submit" 
                                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Verify Email
                                    </button>
                                </form>
                            @endif
                        @endcan

                        @can('delete', $user)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                @csrf
                                @method('DELETE')
                                <button 
                                    type="submit" 
                                    class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded"
                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                >
                                    Delete User
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

            <!-- User Information -->
            <div class="lg:col-span-2">
                <div class="bg-gray-50 overflow-hidden rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">User Information</h3>
                    
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Role</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->role === 'super_admin' ? 'bg-purple-100 text-purple-800' : 
                                       ($user->role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                    {{ $user->role_display_name }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->isActive() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $user->status_display }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email Verification</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->hasVerifiedEmail() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->hasVerifiedEmail() ? '✓ Verified' : '✗ Unverified' }}
                                </span>
                                @if($user->hasVerifiedEmail())
                                    <span class="ml-2 text-xs text-gray-500">
                                        on {{ $user->email_verified_at->format('M j, Y g:i A') }}
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('M j, Y g:i A') }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $user->last_login_at ? $user->last_login_at->format('M j, Y g:i A') : 'Never logged in' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('M j, Y g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

                <!-- Role Permissions -->
                <div class="bg-gray-50 overflow-hidden rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Role Permissions</h3>
                    
                    @if($user->role === 'super_admin')
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <h4 class="font-medium text-purple-900 mb-2">Super Administrator</h4>
                            <ul class="text-sm text-purple-800 space-y-1">
                                <li>• Full system access and control</li>
                                <li>• User management and role assignment</li>
                                <li>• System settings and configuration</li>
                                <li>• Content management and publishing</li>
                                <li>• Media library management</li>
                                <li>• Navigation and site structure</li>
                            </ul>
                        </div>
                    @elseif($user->role === 'admin')
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-medium text-blue-900 mb-2">Administrator</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• User management (except Super Admins)</li>
                                <li>• Content management and publishing</li>
                                <li>• Media library management</li>
                                <li>• Navigation and site structure</li>
                                <li>• View system information</li>
                            </ul>
                        </div>
                    @else
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-900 mb-2">Editor</h4>
                            <ul class="text-sm text-green-800 space-y-1">
                                <li>• Create and edit content</li>
                                <li>• Manage draft content</li>
                                <li>• Limited edit access on published content</li>
                                <li>• Upload and manage media files</li>
                                <li>• View basic system information</li>
                            </ul>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection