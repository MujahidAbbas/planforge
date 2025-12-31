@php
    $badges = $getState() ?? [];
@endphp

@if(count($badges) > 0)
    <div class="flex flex-wrap items-center gap-1.5 mt-1">
        @foreach($badges as $badge)
            <span @class([
                'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-md',
                'bg-gray-100 text-gray-600' => ($badge['color'] ?? 'gray') === 'gray',
                'bg-green-100 text-green-700' => ($badge['color'] ?? '') === 'success',
                'bg-blue-100 text-blue-700' => ($badge['color'] ?? '') === 'info',
                'bg-amber-100 text-amber-700' => ($badge['color'] ?? '') === 'warning',
                'bg-indigo-100 text-indigo-700' => ($badge['color'] ?? '') === 'primary',
                'bg-red-100 text-red-700' => ($badge['color'] ?? '') === 'danger',
            ])>
                @if($badge['icon'] ?? false)
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @endif
                {{ $badge['label'] }}
            </span>
        @endforeach
    </div>
@endif
