<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-gray-800">
                                {{ config('app.name', 'CMS') }} Admin
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('admin.dashboard') }}" 
                               class="{{ request()->routeIs('admin.dashboard') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            
                            @can('viewAny', \App\Models\User::class)
                                <a href="{{ route('admin.users.index') }}" 
                                   class="{{ request()->routeIs('admin.users.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    Users
                                </a>
                            @endcan

                            <!-- CMS Dropdown -->
                            <div class="relative inline-flex items-center" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="{{ request()->routeIs('admin.pages.*') || request()->routeIs('admin.taxonomy.*') || request()->routeIs('admin.navigation.*') || request()->routeIs('admin.media.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    CMS
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition
                                     class="absolute top-full left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border">
                                    <a href="{{ route('admin.pages.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pages</a>
                                    <a href="{{ route('admin.taxonomy.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Taxonomy</a>
                                    <a href="{{ route('admin.navigation.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Navigation</a>
                                    <a href="{{ route('admin.media.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Media</a>
                                </div>
                            </div>

                            <!-- L33t Economy Dropdown -->
                            <div class="relative inline-flex items-center" x-data="{ open: false }">
                                <button @click="open = !open" 
                                        class="{{ request()->routeIs('admin.token-management.*') || request()->routeIs('admin.bits-management.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                    L33t Economy
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition
                                     class="absolute top-full left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border">
                                    <a href="{{ route('admin.token-management.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">L33t Bytes</a>
                                    <a href="{{ route('admin.bits-management.index') }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">L33t Bits</a>
                                </div>
                            </div>

                            <a href="{{ route('admin.settings.index') }}" 
                               class="{{ request()->routeIs('admin.settings.*') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Settings
                            </a>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <div class="ml-3 relative">
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-700">{{ Auth::user()->name }}</span>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 space-y-2">
        @if (session('success'))
            <div class="toast bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 min-w-80 max-w-md mx-auto" data-toast>
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 ml-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="toast bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 min-w-80 max-w-md mx-auto" data-toast>
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span>{{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 ml-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('warning'))
            <div class="toast bg-yellow-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 min-w-80 max-w-md mx-auto" data-toast>
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <span>{{ session('warning') }}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 ml-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        @if (session('info'))
            <div class="toast bg-blue-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 min-w-80 max-w-md mx-auto" data-toast>
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ session('info') }}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 ml-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <script>
        // Auto-dismiss toast notifications after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('[data-toast]');
            toasts.forEach(toast => {
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html>