<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($page) && $page->meta_title ? $page->meta_title : ($page->title ?? config('app.name', 'Laravel')) }}</title>

        @if(isset($page))
            @if($page->meta_description)
                <meta name="description" content="{{ $page->meta_description }}">
            @endif
            @if($page->meta_keywords && count($page->meta_keywords))
                <meta name="keywords" content="{{ implode(', ', $page->meta_keywords) }}">
            @endif
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen flex flex-col">
            <!-- Navigation -->
            <nav class="bg-white shadow">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex-shrink-0 flex items-center">
                                <a href="{{ route('home') }}" class="text-xl font-bold text-gray-800">
                                    {{ config('app.name', 'CMS') }}
                                </a>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                @php
                                    $navigationItems = \App\Models\NavigationItem::visible()
                                        ->topLevel()
                                        ->ordered()
                                        ->with(['children' => function($query) {
                                            $query->visible()->ordered();
                                        }, 'page'])
                                        ->get();
                                @endphp
                                
                                @foreach($navigationItems as $item)
                                    @if($item->children->count() > 0)
                                        {{-- Dropdown menu for items with children --}}
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" @click.away="open = false" 
                                                    class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out {{ $item->css_class }}">
                                                {{ $item->label }}
                                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" 
                                                 class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50" style="display: none;">
                                                @foreach($item->children as $child)
                                                    <a href="{{ $child->url }}" 
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ $child->css_class }}"
                                                       {{ $child->opens_new_tab ? 'target="_blank"' : '' }}>
                                                        {{ $child->label }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        {{-- Simple link for items without children --}}
                                        <a href="{{ $item->url }}" 
                                           class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out {{ $item->css_class }}"
                                           {{ $item->opens_new_tab ? 'target="_blank"' : '' }}>
                                            {{ $item->label }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            @auth
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                        <div>{{ Auth::user()->name }}</div>
                                        <div class="ml-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50" style="display: none;">
                                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Admin
                                        </a>
                                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Profile
                                        </a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Log Out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center space-x-4">
                                    <a href="{{ route('login') }}" class="text-gray-500 hover:text-gray-700">Login</a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="text-gray-500 hover:text-gray-700">Register</a>
                                    @endif
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main class="flex-1">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 mt-auto">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="text-center text-gray-500 text-sm">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>