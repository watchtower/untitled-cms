{{-- Page Header Component
     Usage: @include('admin.partials.page-header', [
         'title' => 'Page Title',
         'description' => 'Optional description',
         'actions' => [
             ['label' => 'Create New', 'url' => route('admin.pages.create'), 'icon' => 'plus'],
             ['label' => 'Export', 'url' => '#', 'type' => 'secondary']
         ]
     ])
--}}

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
        @if(isset($description))
            <p class="text-sm text-gray-600 mt-1">{{ $description }}</p>
        @endif
    </div>
    
    @if(isset($actions) && is_array($actions))
        <div class="flex space-x-3">
            @foreach($actions as $action)
                @php
                    $type = $action['type'] ?? 'primary';
                    $baseClasses = 'inline-flex items-center gap-x-2 rounded-md px-3.5 py-2.5 text-sm font-semibold shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 transition-colors duration-200';
                    
                    $typeClasses = match($type) {
                        'secondary' => 'bg-gray-600 text-white hover:bg-gray-500 focus-visible:outline-gray-600',
                        'danger' => 'bg-red-600 text-white hover:bg-red-500 focus-visible:outline-red-600',
                        default => 'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline-indigo-600'
                    };
                    
                    $iconMap = [
                        'plus' => 'M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z',
                        'upload' => 'M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z',
                        'download' => 'M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03L10.75 11.364V2.75z M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z',
                        'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
                    ];
                @endphp
                
                @if(isset($action['onclick']))
                    <button 
                        onclick="{{ $action['onclick'] }}"
                        class="{{ $baseClasses }} {{ $typeClasses }}"
                    >
                        @if(isset($action['icon']) && isset($iconMap[$action['icon']]))
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="{{ $iconMap[$action['icon']] }}" />
                            </svg>
                        @endif
                        {{ $action['label'] }}
                    </button>
                @else
                    <a 
                        href="{{ $action['url'] }}"
                        class="{{ $baseClasses }} {{ $typeClasses }}"
                    >
                        @if(isset($action['icon']) && isset($iconMap[$action['icon']]))
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="{{ $iconMap[$action['icon']] }}" />
                            </svg>
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif
</div>