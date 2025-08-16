@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Navigation Management</h1>
            <a href="{{ route('admin.navigation.create') }}" 
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Add Navigation Item
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if ($navigationItems->count() > 0)
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <p class="text-sm text-gray-600">
                    <strong>Tip:</strong> Drag and drop items to reorder them. Items can be nested up to 2 levels deep.
                </p>
            </div>

            <div id="navigation-list" class="space-y-2">
                @foreach ($navigationItems as $item)
                    <div class="navigation-item bg-white border border-gray-200 rounded-lg p-4" data-id="{{ $item->id }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="cursor-move text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $item->label }}</h3>
                                    <p class="text-sm text-gray-500">
                                        @if ($item->type === 'page' && $item->page)
                                            Page: {{ $item->page->title }}
                                        @elseif ($item->type === 'url')
                                            URL: {{ $item->url }}
                                        @else
                                            Custom: {{ $item->url }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if ($item->is_visible)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Visible
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Hidden
                                    </span>
                                @endif
                                <a href="{{ route('admin.navigation.edit', $item) }}" 
                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form method="POST" action="{{ route('admin.navigation.destroy', $item) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to delete this navigation item?')"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </div>

                        @if ($item->children->count() > 0)
                            <div class="mt-4 ml-8 space-y-2">
                                @foreach ($item->children as $child)
                                    <div class="navigation-item bg-gray-50 border border-gray-200 rounded p-3" data-id="{{ $child->id }}" data-parent="{{ $item->id }}">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-3">
                                                <div class="cursor-move text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-900">{{ $child->label }}</h4>
                                                    <p class="text-xs text-gray-500">
                                                        @if ($child->type === 'page' && $child->page)
                                                            Page: {{ $child->page->title }}
                                                        @elseif ($child->type === 'url')
                                                            URL: {{ $child->url }}
                                                        @else
                                                            Custom: {{ $child->url }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                @if ($child->is_visible)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Visible
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Hidden
                                                    </span>
                                                @endif
                                                <a href="{{ route('admin.navigation.edit', $child) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</a>
                                                <form method="POST" action="{{ route('admin.navigation.destroy', $child) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            onclick="return confirm('Are you sure you want to delete this navigation item?')"
                                                            class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No navigation items</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first navigation item.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.navigation.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Add Navigation Item
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// Simple drag and drop implementation would go here
// For now, we'll use a basic approach without external libraries
document.addEventListener('DOMContentLoaded', function() {
    // This is a placeholder for drag-and-drop functionality
    // In a real implementation, you'd use something like Sortable.js
    console.log('Navigation management loaded');
});
</script>
@endsection