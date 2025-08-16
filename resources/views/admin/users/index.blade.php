@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Users</h1>
                <p class="text-sm text-gray-600 mt-1">Manage user accounts and permissions</p>
            </div>
            <a href="{{ route('admin.users.create') }}" 
               class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200">
                <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Create New User
            </a>
        </div>


        <!-- Filters -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search by name or email..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                </div>
                <div>
                    <select 
                        name="role"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Roles</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="editor" {{ request('role') === 'editor' ? 'selected' : '' }}>Editor</option>
                    </select>
                </div>
                <div>
                    <select 
                        name="status"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="deleted" {{ request('status') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'role', 'status']))
                        <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
        @if($users->count())
            <div class="overflow-hidden shadow-sm ring-1 ring-gray-300 md:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">User</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Role</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email Verification</th>
                                <th scope="col" class="hidden lg:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Last Login</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50 transition-colors duration-150 ease-in-out {{ $user->trashed() ? 'bg-red-50/50' : '' }}">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-12 w-12 flex-shrink-0">
                                                <div class="h-12 w-12 rounded-full bg-gradient-to-br {{ $user->role === 'super_admin' ? 'from-purple-500 to-purple-600' : ($user->role === 'admin' ? 'from-blue-500 to-blue-600' : 'from-indigo-500 to-indigo-600') }} flex items-center justify-center shadow-md">
                                                    <span class="text-white font-semibold text-sm">
                                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500 sm:hidden">{{ $user->email }}</div>
                                                <div class="hidden sm:block text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                            {{ $user->role === 'super_admin' ? 'bg-purple-100 text-purple-800 ring-1 ring-purple-600/20' : 
                                               ($user->role === 'admin' ? 'bg-blue-100 text-blue-800 ring-1 ring-blue-600/20' : 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-600/20') }}">
                                            @if($user->role === 'super_admin')
                                                <svg class="mr-1.5 h-2 w-2 fill-purple-500" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                            @elseif($user->role === 'admin')
                                                <svg class="mr-1.5 h-2 w-2 fill-blue-500" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                            @else
                                                <svg class="mr-1.5 h-2 w-2 fill-emerald-500" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                            @endif
                                            {{ $user->role_display_name }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4">
                                        @if($user->trashed())
                                            <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20">
                                                <svg class="mr-1.5 h-2 w-2 fill-red-500" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                                Deleted
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                {{ $user->isActive() ? 'bg-green-50 text-green-700 ring-1 ring-green-600/20' : 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20' }}">
                                                <svg class="mr-1.5 h-2 w-2 {{ $user->isActive() ? 'fill-green-500' : 'fill-yellow-500' }}" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                                {{ $user->status_display }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="hidden sm:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="flex items-center">
                                            @if($user->hasVerifiedEmail())
                                                <svg class="mr-2 h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-green-700 font-medium">Verified</span>
                                            @else
                                                <svg class="mr-2 h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-red-700 font-medium">Unverified</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="hidden lg:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $user->last_login_at ? $user->last_login_at->format('M j, Y') : 'Never' }}</span>
                                            @if($user->last_login_at)
                                                <span class="text-xs text-gray-400">{{ $user->last_login_at->format('g:i A') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex items-center justify-end space-x-2">
                                            @if($user->trashed())
                                                {{-- Restore Button --}}
                                                @can('restore', $user)
                                                    <form method="POST" action="{{ route('admin.users.restore', $user) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="inline-flex items-center justify-center rounded-md bg-green-50 p-2 text-green-700 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                                                                title="Restore user">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                            </svg>
                                                            <span class="sr-only">Restore</span>
                                                        </button>
                                                    </form>
                                                @endcan
                                                {{-- Force Delete Button --}}
                                                @can('forceDelete', $user)
                                                    <form method="POST" action="{{ route('admin.users.force-delete', $user) }}" class="inline" 
                                                          onsubmit="return confirm('Are you sure you want to permanently delete this user?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="inline-flex items-center justify-center rounded-md bg-red-50 p-2 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                                                                title="Delete forever">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                            </svg>
                                                            <span class="sr-only">Delete Forever</span>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                {{-- 1. Status Toggle with Eye Icon (matches pages pattern) --}}
                                                @can('update', $user)
                                                    @if($user->isActive())
                                                        <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" 
                                                                    class="inline-flex items-center justify-center rounded-md bg-green-50 p-2 text-green-700 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                                                                    title="Deactivate user (make inactive)">
                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                </svg>
                                                                <span class="sr-only">Deactivate</span>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" 
                                                                    class="inline-flex items-center justify-center rounded-md bg-yellow-50 p-2 text-yellow-700 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors duration-200"
                                                                    title="Activate user (make active)">
                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                                </svg>
                                                                <span class="sr-only">Activate</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan

                                                {{-- 2. Edit Button --}}
                                                @can('update', $user)
                                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                                       class="inline-flex items-center justify-center rounded-md bg-gray-50 p-2 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                                                       title="Edit user">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                        </svg>
                                                        <span class="sr-only">Edit</span>
                                                    </a>
                                                @endcan

                                                {{-- 3. Soft Delete Button --}}
                                                @can('delete', $user)
                                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" 
                                                          onsubmit="return confirm('Are you sure you want to soft delete this user? They can be restored later.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="inline-flex items-center justify-center rounded-md bg-red-50 p-2 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                                                                title="Soft delete user">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                            </svg>
                                                            <span class="sr-only">Soft Delete</span>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
            
            <!-- Pagination -->
            <div class="mt-6">
                {{ $users->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No users found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first user account.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.users.create') }}" 
                       class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200">
                        <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Create New User
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection