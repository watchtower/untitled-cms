{{-- Status Badge Component
     Usage: @include('admin.partials.status-badge', [
         'status' => 'published', // or 'draft', 'active', 'inactive', etc.
         'label' => 'Published', // optional custom label
         'color' => 'green' // optional custom color: green, yellow, red, blue, gray, indigo, purple
     ])
--}}

@php
    $statusColors = [
        'published' => 'green',
        'active' => 'green',
        'visible' => 'green',
        'online' => 'green',
        'success' => 'green',
        'completed' => 'green',
        
        'draft' => 'yellow',
        'pending' => 'yellow',
        'warning' => 'yellow',
        'in_progress' => 'yellow',
        
        'inactive' => 'gray',
        'hidden' => 'gray',
        'disabled' => 'gray',
        'paused' => 'gray',
        
        'deleted' => 'red',
        'error' => 'red',
        'failed' => 'red',
        'denied' => 'red',
        
        'processing' => 'blue',
        'info' => 'blue',
        'scheduled' => 'blue',
        
        'super_admin' => 'purple',
        'admin' => 'blue',
        'editor' => 'indigo',
        'user' => 'gray'
    ];
    
    $finalColor = $color ?? $statusColors[strtolower($status)] ?? 'gray';
    $finalLabel = $label ?? ucfirst(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex items-center rounded-full bg-{{ $finalColor }}-50 px-2.5 py-0.5 text-xs font-medium text-{{ $finalColor }}-700 ring-1 ring-{{ $finalColor }}-600/20">
    <svg class="mr-1.5 h-2 w-2 fill-{{ $finalColor }}-500" viewBox="0 0 6 6" aria-hidden="true">
        <circle cx="3" cy="3" r="3" />
    </svg>
    {{ $finalLabel }}
</span>