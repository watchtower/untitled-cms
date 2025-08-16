{{-- Info Box Component
     Usage: @include('admin.partials.info-box', [
         'type' => 'info', // info, warning, success, error
         'message' => 'This is an informational message.',
         'dismissible' => true // optional, makes it dismissible
     ])
--}}

@php
    $type = $type ?? 'info';
    $typeConfig = [
        'info' => [
            'bg' => 'bg-blue-50',
            'border' => 'border-blue-200',
            'text' => 'text-blue-700',
            'icon' => 'text-blue-400',
            'iconPath' => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z'
        ],
        'warning' => [
            'bg' => 'bg-yellow-50',
            'border' => 'border-yellow-200',
            'text' => 'text-yellow-700',
            'icon' => 'text-yellow-400',
            'iconPath' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'
        ],
        'success' => [
            'bg' => 'bg-green-50',
            'border' => 'border-green-200',
            'text' => 'text-green-700',
            'icon' => 'text-green-400',
            'iconPath' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L7.53 10.23a.75.75 0 00-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z'
        ],
        'error' => [
            'bg' => 'bg-red-50',
            'border' => 'border-red-200',
            'text' => 'text-red-700',
            'icon' => 'text-red-400',
            'iconPath' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z'
        ]
    ];
    
    $config = $typeConfig[$type];
@endphp

<div class="mb-6 {{ $config['bg'] }} p-4 rounded-lg border {{ $config['border'] }} {{ isset($dismissible) && $dismissible ? 'relative' : '' }}">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 {{ $config['icon'] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="{{ $config['iconPath'] }}" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm {{ $config['text'] }}">
                @if(isset($title))
                    <strong>{{ $title }}:</strong> 
                @endif
                {{ $message }}
            </p>
        </div>
        @if(isset($dismissible) && $dismissible)
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" onclick="this.closest('.mb-6').remove()" class="inline-flex rounded-md {{ $config['bg'] }} p-1.5 {{ $config['text'] }} hover:{{ $config['bg'] }} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-{{ substr($config['bg'], 3) }}-50 focus:ring-{{ substr($config['icon'], 5) }}-600">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>