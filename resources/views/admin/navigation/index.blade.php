@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Navigation</h1>
                <p class="text-sm text-gray-600 mt-1">Organize your website navigation structure with drag and drop</p>
            </div>
            <div class="flex items-center space-x-4">
                <div id="saving-status" class="flex items-center space-x-2 text-sm font-medium" style="display: none;">
                    <div class="animate-spin rounded-full h-4 w-4 border-2 border-indigo-500 border-t-transparent"></div>
                    <span id="status-text">Saving...</span>
                </div>
                <a href="{{ route('admin.navigation.create') }}" 
                   class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200">
                    <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Add Navigation Item
                </a>
            </div>
        </div>


        @if ($navigationItems->count() > 0)
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 p-1.5 bg-blue-100 rounded-md">
                        <svg class="h-4 w-4 text-blue-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-1">Drag & Drop Navigation</h3>
                        <p class="text-sm text-gray-600">
                            Drag items using the grip handle to reorder them. Items can be nested up to 2 levels deep for dropdown menus.
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden shadow-sm ring-1 ring-gray-300 md:rounded-lg">
                <div id="navigation-list" class="divide-y divide-gray-200 bg-white">
                    @foreach ($navigationItems as $item)
                        <div class="navigation-item group hover:bg-gray-50 transition-colors duration-150 ease-in-out" data-id="{{ $item->id }}">
                            <div class="py-4 px-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1.5 rounded-md hover:bg-gray-100 mr-4">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </div>
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-sm">
                                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ $item->label }}</div>
                                            <div class="flex items-center space-x-1 mt-1">
                                                @if ($item->type === 'page' && $item->page)
                                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-600/20">
                                                        <svg class="mr-1.5 h-2 w-2 fill-blue-500" viewBox="0 0 6 6" aria-hidden="true">
                                                            <circle cx="3" cy="3" r="3" />
                                                        </svg>
                                                        Page
                                                    </span>
                                                    <span class="text-sm text-gray-500 truncate">{{ $item->page->title }}</span>
                                                @elseif ($item->type === 'url')
                                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">
                                                        <svg class="mr-1.5 h-2 w-2 fill-green-500" viewBox="0 0 6 6" aria-hidden="true">
                                                            <circle cx="3" cy="3" r="3" />
                                                        </svg>
                                                        URL
                                                    </span>
                                                    <span class="text-sm text-gray-500 truncate">{{ $item->url }}</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-purple-600/20">
                                                        <svg class="mr-1.5 h-2 w-2 fill-purple-500" viewBox="0 0 6 6" aria-hidden="true">
                                                            <circle cx="3" cy="3" r="3" />
                                                        </svg>
                                                        Custom
                                                    </span>
                                                    <span class="text-sm text-gray-500 truncate">{{ $item->url }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        @if ($item->is_visible)
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">
                                                <svg class="mr-1.5 h-2 w-2 fill-green-500" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                                Visible
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-600/20">
                                                <svg class="mr-1.5 h-2 w-2 fill-gray-500" viewBox="0 0 6 6" aria-hidden="true">
                                                    <circle cx="3" cy="3" r="3" />
                                                </svg>
                                                Hidden
                                            </span>
                                        @endif
                                        <div class="flex items-center space-x-2">
                                            <form method="POST" action="{{ route('admin.navigation.toggle-visibility', $item) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="inline-flex items-center justify-center rounded-md p-2 transition-colors duration-200 {{ $item->is_visible ? 'bg-green-50 text-green-700 hover:bg-green-100 focus:ring-2 focus:ring-green-500' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100 focus:ring-2 focus:ring-yellow-500' }}"
                                                        title="{{ $item->is_visible ? 'Make hidden' : 'Make visible' }}">
                                                    @if($item->is_visible)
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                    @else
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                        </svg>
                                                    @endif
                                                    <span class="sr-only">{{ $item->is_visible ? 'Make hidden' : 'Make visible' }}</span>
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.navigation.edit', $item) }}" 
                                               class="inline-flex items-center justify-center rounded-md bg-gray-50 p-2 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                                               title="Edit navigation item">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                                <span class="sr-only">Edit</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.navigation.duplicate', $item) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="inline-flex items-center justify-center rounded-md bg-blue-50 p-2 text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                                                        title="Duplicate navigation item">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                                    </svg>
                                                    <span class="sr-only">Duplicate</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.navigation.destroy', $item) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        onclick="return confirm('Are you sure you want to delete this navigation item?')"
                                                        class="inline-flex items-center justify-center rounded-md bg-red-50 p-2 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                                                        title="Delete navigation item">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                    <span class="sr-only">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($item->children->count() > 0)
                                <div class="ml-8 border-l border-gray-200">
                                    <div class="navigation-item-children pl-4">
                                        @foreach ($item->children as $child)
                                            <div class="navigation-item border-t border-gray-100 hover:bg-gray-50 transition-colors duration-150 ease-in-out" data-id="{{ $child->id }}" data-parent="{{ $item->id }}">
                                                <div class="py-3 px-4 sm:px-6">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center">
                                                            <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 transition-colors duration-200 p-1.5 rounded-md hover:bg-gray-100 mr-4">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                                                </svg>
                                                            </div>
                                                            <div class="h-8 w-8 flex-shrink-0">
                                                                <div class="h-8 w-8 rounded-md bg-gradient-to-br from-gray-400 to-gray-500 flex items-center justify-center shadow-sm">
                                                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                            <div class="ml-3">
                                                                <div class="text-sm font-medium text-gray-900">{{ $child->label }}</div>
                                                                <div class="flex items-center space-x-1 mt-1">
                                                                    @if ($child->type === 'page' && $child->page)
                                                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-600/20">
                                                                            <svg class="mr-1 h-1.5 w-1.5 fill-blue-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                <circle cx="3" cy="3" r="3" />
                                                                            </svg>
                                                                            Page
                                                                        </span>
                                                                        <span class="text-xs text-gray-500 truncate">{{ $child->page->title }}</span>
                                                                    @elseif ($child->type === 'url')
                                                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">
                                                                            <svg class="mr-1 h-1.5 w-1.5 fill-green-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                <circle cx="3" cy="3" r="3" />
                                                                            </svg>
                                                                            URL
                                                                        </span>
                                                                        <span class="text-xs text-gray-500 truncate">{{ $child->url }}</span>
                                                                    @else
                                                                        <span class="inline-flex items-center rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-purple-600/20">
                                                                            <svg class="mr-1 h-1.5 w-1.5 fill-purple-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                                <circle cx="3" cy="3" r="3" />
                                                                            </svg>
                                                                            Custom
                                                                        </span>
                                                                        <span class="text-xs text-gray-500 truncate">{{ $child->url }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center space-x-3">
                                                            @if ($child->is_visible)
                                                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-600/20">
                                                                    <svg class="mr-1 h-1.5 w-1.5 fill-green-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                        <circle cx="3" cy="3" r="3" />
                                                                    </svg>
                                                                    Visible
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center rounded-full bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-600/20">
                                                                    <svg class="mr-1 h-1.5 w-1.5 fill-gray-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                        <circle cx="3" cy="3" r="3" />
                                                                    </svg>
                                                                    Hidden
                                                                </span>
                                                            @endif
                                                            <div class="flex items-center space-x-2">
                                                                <form method="POST" action="{{ route('admin.navigation.toggle-visibility', $child) }}" class="inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" 
                                                                            class="inline-flex items-center justify-center rounded-md p-1.5 transition-colors duration-200 {{ $child->is_visible ? 'bg-green-50 text-green-700 hover:bg-green-100 focus:ring-2 focus:ring-green-500' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100 focus:ring-2 focus:ring-yellow-500' }}"
                                                                            title="{{ $child->is_visible ? 'Make hidden' : 'Make visible' }}">
                                                                        @if($child->is_visible)
                                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                            </svg>
                                                                        @else
                                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                                            </svg>
                                                                        @endif
                                                                        <span class="sr-only">{{ $child->is_visible ? 'Make hidden' : 'Make visible' }}</span>
                                                                    </button>
                                                                </form>
                                                                <a href="{{ route('admin.navigation.edit', $child) }}" 
                                                                   class="inline-flex items-center justify-center rounded-md bg-gray-50 p-1.5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
                                                                   title="Edit navigation item">
                                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                                    </svg>
                                                                    <span class="sr-only">Edit</span>
                                                                </a>
                                                                <form method="POST" action="{{ route('admin.navigation.duplicate', $child) }}" class="inline">
                                                                    @csrf
                                                                    <button type="submit" 
                                                                            class="inline-flex items-center justify-center rounded-md bg-blue-50 p-1.5 text-blue-700 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                                                                            title="Duplicate navigation item">
                                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                                                        </svg>
                                                                        <span class="sr-only">Duplicate</span>
                                                                    </button>
                                                                </form>
                                                                <form method="POST" action="{{ route('admin.navigation.destroy', $child) }}" class="inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" 
                                                                            onclick="return confirm('Are you sure you want to delete this navigation item?')"
                                                                            class="inline-flex items-center justify-center rounded-md bg-red-50 p-1.5 text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                                                                            title="Delete navigation item">
                                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                                        </svg>
                                                                        <span class="sr-only">Delete</span>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No navigation items found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first navigation item for your website menu.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.navigation.create') }}" 
                       class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200">
                        <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Add Navigation Item
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusEl = document.getElementById('saving-status');
    const statusText = document.getElementById('status-text');
    const navigationList = document.getElementById('navigation-list');

    function initSortable(container) {
        if (!container) return;
        
        return new Sortable(container, {
            group: {
                name: 'navigation',
                pull: true,
                put: true
            },
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            forceFallback: false,
            fallbackTolerance: 3,
            swapThreshold: 0.65,
            bubbleScroll: true,
            onStart: function (evt) {
                document.body.classList.add('is-dragging');
                evt.item.classList.add('dragging');
            },
            onEnd: function (evt) {
                document.body.classList.remove('is-dragging');
                evt.item.classList.remove('dragging');
                updateOrder();
            }
        });
    }

    // Initialize sortable for main navigation list
    if (navigationList) {
        initSortable(navigationList);
        
        // Initialize sortable for all child containers
        document.querySelectorAll('.navigation-item-children').forEach(function (el) {
            initSortable(el);
        });
    }

    function updateOrder() {
        if (!statusEl || !statusText) return;
        
        statusText.textContent = 'Saving...';
        statusEl.style.display = 'flex';
        statusEl.classList.remove('text-red-600', 'text-green-600');
        statusEl.classList.add('text-indigo-600');

        const items = [];

        function collectItems(container, parentId = null) {
            if (!container) return;
            
            const directChildren = Array.from(container.children).filter(child => 
                child.classList.contains('navigation-item')
            );
            
            directChildren.forEach((itemEl, index) => {
                const id = itemEl.dataset.id;
                if (id) {
                    items.push({
                        id: id,
                        sort_order: index + 1,
                        parent_id: parentId
                    });
                    
                    // Look for children
                    const childContainer = itemEl.querySelector('.navigation-item-children');
                    if (childContainer) {
                        collectItems(childContainer, id);
                    }
                }
            });
        }

        collectItems(navigationList);

        fetch('{{ route("admin.navigation.order") }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ items: items })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusText.textContent = 'Saved!';
                statusEl.classList.remove('text-indigo-600');
                statusEl.classList.add('text-green-600');
                setTimeout(() => {
                    statusEl.style.display = 'none';
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to save order');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            statusText.textContent = 'Error saving order!';
            statusEl.classList.remove('text-indigo-600');
            statusEl.classList.add('text-red-600');
            setTimeout(() => {
                statusEl.style.display = 'none';
            }, 3000);
        });
    }
});
</script>

<style>
/* Drag and drop styles */
.is-dragging {
    cursor: grabbing !important;
}

.dragging {
    opacity: 0.9 !important;
}

.sortable-ghost {
    background: #f3f4f6 !important;
    border: 2px dashed #6366f1 !important;
    border-radius: 0.5rem !important;
    opacity: 0.5 !important;
}

.sortable-chosen {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    transform: scale(1.02) !important;
}

.sortable-drag {
    opacity: 0.8 !important;
}

.navigation-item .drag-handle {
    opacity: 0.4;
    transition: opacity 0.2s ease, background-color 0.2s ease;
}

.navigation-item:hover .drag-handle {
    opacity: 1;
}

.navigation-item .drag-handle:hover {
    background-color: #f3f4f6 !important;
}

.navigation-item .drag-handle:active {
    background-color: #e5e7eb !important;
}

/* Drop zone indicators */
.navigation-item-children {
    min-height: 8px;
}

.navigation-item-children:empty {
    border: 2px dashed transparent;
    border-radius: 0.375rem;
    transition: border-color 0.2s ease;
}

.navigation-item-children:empty.sortable-active {
    border-color: #6366f1;
    background-color: #f3f4f6;
}

/* Smooth transitions */
.navigation-item {
    transition: all 0.15s ease;
}

/* Loading spinner */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
@endpush