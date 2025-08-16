{{-- Action Buttons Component
     Usage: @include('admin.partials.action-buttons', [
         'actions' => [
             ['type' => 'view', 'url' => route('admin.pages.show', $page), 'title' => 'View page'],
             ['type' => 'edit', 'url' => route('admin.pages.edit', $page), 'title' => 'Edit page'],
             ['type' => 'duplicate', 'action' => route('admin.pages.duplicate', $page), 'title' => 'Duplicate page'],
             ['type' => 'delete', 'action' => route('admin.pages.destroy', $page), 'title' => 'Delete page', 'confirm' => 'Are you sure?']
         ]
     ])
--}}

<div class="flex items-center justify-end space-x-2">
    @foreach($actions as $action)
        @php
            $actionTypes = [
                'view' => [
                    'color' => 'indigo',
                    'icon' => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
                ],
                'edit' => [
                    'color' => 'gray',
                    'icon' => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'
                ],
                'duplicate' => [
                    'color' => 'blue',
                    'icon' => 'M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75'
                ],
                'delete' => [
                    'color' => 'red',
                    'icon' => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'
                ],
                'restore' => [
                    'color' => 'green',
                    'icon' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99'
                ],
                'download' => [
                    'color' => 'blue',
                    'icon' => 'M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03L10.75 11.364V2.75z M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z'
                ]
            ];
            
            $actionConfig = $actionTypes[$action['type']] ?? $actionTypes['edit'];
            $color = $actionConfig['color'];
            $icon = $actionConfig['icon'];
        @endphp
        
        @if(isset($action['url']))
            {{-- Simple link action --}}
            <a href="{{ $action['url'] }}" 
               class="inline-flex items-center justify-center rounded-md bg-{{ $color }}-50 p-2 text-{{ $color }}-700 hover:bg-{{ $color }}-100 focus:outline-none focus:ring-2 focus:ring-{{ $color }}-500 focus:ring-offset-2 transition-colors duration-200"
               title="{{ $action['title'] ?? ucfirst($action['type']) }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
                <span class="sr-only">{{ $action['title'] ?? ucfirst($action['type']) }}</span>
            </a>
        @else
            {{-- Form action (POST, DELETE, etc.) --}}
            <form method="POST" action="{{ $action['action'] }}" class="inline">
                @csrf
                @if(isset($action['method']))
                    @method($action['method'])
                @elseif($action['type'] === 'delete')
                    @method('DELETE')
                @endif
                
                <button type="submit" 
                        @if(isset($action['confirm']))
                            onclick="return confirm('{{ $action['confirm'] }}')"
                        @endif
                        class="inline-flex items-center justify-center rounded-md bg-{{ $color }}-50 p-2 text-{{ $color }}-700 hover:bg-{{ $color }}-100 focus:outline-none focus:ring-2 focus:ring-{{ $color }}-500 focus:ring-offset-2 transition-colors duration-200"
                        title="{{ $action['title'] ?? ucfirst($action['type']) }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                    </svg>
                    <span class="sr-only">{{ $action['title'] ?? ucfirst($action['type']) }}</span>
                </button>
            </form>
        @endif
    @endforeach
</div>