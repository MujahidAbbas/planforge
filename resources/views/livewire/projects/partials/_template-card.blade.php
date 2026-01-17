{{-- Template Card Component - Reusable across template selector --}}
@props([
    'template',
    'isSelected' => false,
    'isRecommended' => false,
    'variant' => 'builtin', // 'builtin' or 'user'
])

<div
    class="group relative"
    x-data="{ showActions: false }"
    @mouseenter="showActions = true"
    @mouseleave="showActions = false"
>
    <button
        wire:click="selectTemplate('{{ $template->id }}')"
        @class([
            'flex flex-col w-full p-4 rounded-xl border-2 transition-all text-left h-full min-h-[140px]',
            'border-indigo-500 bg-indigo-50 shadow-md' => $isSelected,
            'border-transparent bg-white shadow-sm hover:shadow-md hover:border-gray-200' => !$isSelected && !$isRecommended,
            'border-green-200 bg-green-50/50 hover:border-green-300 hover:shadow-md' => !$isSelected && $isRecommended,
        ])
    >
        {{-- Header Row with Title and Selection Indicator --}}
        <div class="flex items-start justify-between gap-3 mb-1.5">
            <span @class([
                'font-semibold text-sm leading-tight',
                'text-indigo-900' => $isSelected,
                'text-gray-900' => !$isSelected,
            ])>
                {{ $template->name }}
            </span>
            {{-- Selection Indicator --}}
            <div class="shrink-0">
                @if($isSelected)
                    <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @else
                    <div @class([
                        'w-6 h-6 rounded-full border-2 transition-colors',
                        'border-gray-300 group-hover:border-gray-400' => !$isRecommended,
                        'border-green-300 group-hover:border-green-400' => $isRecommended,
                    ])></div>
                @endif
            </div>
        </div>

        {{-- Badge Row --}}
        <div class="flex items-center gap-1.5 mb-2">
            @if($isRecommended)
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-green-100 text-green-700 shadow-sm">
                    Recommended
                </span>
            @endif

            @if($template->is_built_in && !$isRecommended)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                    Built-in
                </span>
            @endif

            @if($template->is_community)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                    {{ $template->author }}
                </span>
            @endif

            @if($variant === 'user')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
                    Custom
                </span>
            @endif
        </div>

        {{-- Description --}}
        @if($template->description)
            <p @class([
                'text-xs leading-relaxed line-clamp-2 flex-1',
                'text-indigo-700' => $isSelected,
                'text-gray-500' => !$isSelected,
            ])>
                {{ $template->description }}
            </p>
        @endif

        {{-- Section Preview Chips (show first 3 sections) --}}
        @if(count($template->sections ?? []) > 0)
            <div class="flex flex-wrap gap-1 mt-2">
                @foreach(array_slice($template->sections, 0, 3) as $section)
                    <span @class([
                        'text-xs px-1.5 py-0.5 rounded',
                        'bg-indigo-100/70 text-indigo-600' => $isSelected,
                        'bg-gray-100 text-gray-500' => !$isSelected,
                    ])>
                        {{ Str::limit($section['title'], 12) }}
                    </span>
                @endforeach
                @if(count($template->sections) > 3)
                    <span class="text-xs text-gray-400 py-0.5">+{{ count($template->sections) - 3 }}</span>
                @endif
            </div>
        @endif

        {{-- Footer --}}
        <div class="mt-auto pt-3 flex items-center justify-between">
            <span @class([
                'text-xs font-medium',
                'text-indigo-600' => $isSelected,
                'text-gray-400' => !$isSelected,
            ])>
                @if(count($template->sections ?? []) > 0)
                    {{ count($template->sections) }} sections
                @else
                    Flexible structure
                @endif
            </span>
        </div>
    </button>

    {{-- Preview Button (appears on hover) --}}
    <button
        x-show="showActions"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        wire:click.stop="preview('{{ $template->id }}')"
        class="absolute bottom-3 right-3 px-2 py-1 text-xs font-medium text-indigo-600 bg-white border border-indigo-200 rounded-lg shadow-sm hover:bg-indigo-50 hover:border-indigo-300 transition"
    >
        Preview
    </button>
</div>
