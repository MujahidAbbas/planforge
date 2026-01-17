<div class="space-y-6">
    {{-- Section Header with Search --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Choose a template</h3>
            @if($selectedTemplateId)
                <button
                    wire:click="clearTemplate"
                    class="text-sm text-gray-500 hover:text-gray-700 transition"
                >
                    Clear selection
                </button>
            @endif
        </div>

        {{-- Search Input --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search {{ $this->totalTemplateCount }} templates..."
                class="w-full pl-9 pr-9 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition placeholder-gray-400"
            >
            @if($search)
                <button
                    wire:click="clearSearch"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- No Search Results State --}}
    @if(!$this->hasSearchResults)
        <div class="text-center py-12">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h4 class="text-sm font-medium text-gray-900 mb-1">No templates found</h4>
            <p class="text-sm text-gray-500">Try adjusting your search term</p>
            <button
                wire:click="clearSearch"
                class="mt-4 text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            >
                Clear search
            </button>
        </div>
    @else
        {{-- Hero Recommendation Card (only show when not searching) --}}
        @if(!$search && $this->recommendedTemplate)
            @php
                $recommended = $this->recommendedTemplate;
                $isSelected = $selectedTemplateId === $recommended->id;
            @endphp
            <div class="relative">
                {{-- Floating Badge --}}
                <div class="absolute -top-2.5 left-4 z-10">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-semibold rounded-full shadow-lg">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Recommended
                    </span>
                </div>

                {{-- Hero Card --}}
                <button
                    wire:click="selectTemplate('{{ $recommended->id }}')"
                    @class([
                        'w-full text-left p-6 pt-8 rounded-2xl border-2 transition-all duration-200',
                        'border-indigo-500 bg-gradient-to-br from-indigo-50 via-white to-purple-50 shadow-lg ring-2 ring-indigo-500/20' => $isSelected,
                        'border-indigo-200 bg-gradient-to-br from-indigo-50/50 via-white to-purple-50/50 hover:border-indigo-300 hover:shadow-lg' => !$isSelected,
                    ])
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            {{-- Title --}}
                            <h4 @class([
                                'text-lg font-bold mb-2',
                                'text-indigo-900' => $isSelected,
                                'text-gray-900' => !$isSelected,
                            ])>
                                {{ $recommended->name }}
                            </h4>

                            {{-- Description --}}
                            <p @class([
                                'text-sm leading-relaxed mb-4',
                                'text-indigo-700' => $isSelected,
                                'text-gray-600' => !$isSelected,
                            ])>
                                {{ $recommended->description }}
                            </p>

                            {{-- Section Preview Chips --}}
                            @if(count($recommended->sections) > 0)
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(array_slice($recommended->sections, 0, 4) as $section)
                                        <span @class([
                                            'inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium',
                                            'bg-indigo-100 text-indigo-700' => $isSelected,
                                            'bg-gray-100 text-gray-600' => !$isSelected,
                                        ])>
                                            {{ Str::limit($section['title'], 18) }}
                                        </span>
                                    @endforeach
                                    @if(count($recommended->sections) > 4)
                                        <span class="text-xs text-gray-400 py-0.5">
                                            +{{ count($recommended->sections) - 4 }} more
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Selection Indicator + Preview Button --}}
                        <div class="flex flex-col items-end gap-3 shrink-0">
                            {{-- Large Selection Indicator --}}
                            @if($isSelected)
                                <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center shadow-md">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full border-2 border-indigo-300 transition-colors"></div>
                            @endif

                            {{-- Preview Link --}}
                            <span
                                wire:click.stop="preview('{{ $recommended->id }}')"
                                class="text-xs text-indigo-600 hover:text-indigo-700 font-medium cursor-pointer hover:underline"
                            >
                                Preview sections
                            </span>
                        </div>
                    </div>

                    {{-- Footer Stats --}}
                    <div class="mt-4 pt-4 border-t border-indigo-100 flex items-center justify-between">
                        <span @class([
                            'text-sm font-medium',
                            'text-indigo-600' => $isSelected,
                            'text-gray-500' => !$isSelected,
                        ])>
                            {{ count($recommended->sections) }} sections
                        </span>
                        <span class="text-xs text-gray-400">
                            Best for comprehensive PRDs
                        </span>
                    </div>
                </button>
            </div>
        @endif

        {{-- Quick Options Row (only show when not searching) --}}
        @if(!$search && count($this->quickOptions) > 0)
            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide shrink-0">Quick options</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @foreach($this->quickOptions as $option)
                    @php
                        $isSelected = $selectedTemplateId === $option->id;
                        $isNoTemplate = $option->name === 'No Template';
                    @endphp
                    <button
                        wire:click="selectTemplate('{{ $option->id }}')"
                        @class([
                            'group relative flex items-center gap-3 p-4 rounded-xl border-2 transition-all text-left',
                            'border-indigo-500 bg-indigo-50 shadow-md' => $isSelected,
                            'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md' => !$isSelected,
                        ])
                    >
                        {{-- Icon --}}
                        <div @class([
                            'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
                            'bg-indigo-100' => $isSelected,
                            'bg-gray-100 group-hover:bg-gray-200' => !$isSelected,
                        ])>
                            @if($isNoTemplate)
                                <svg @class(['w-5 h-5', 'text-indigo-600' => $isSelected, 'text-gray-500' => !$isSelected]) fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                </svg>
                            @else
                                <svg @class(['w-5 h-5', 'text-indigo-600' => $isSelected, 'text-gray-500' => !$isSelected]) fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <h5 @class([
                                'font-semibold text-sm',
                                'text-indigo-900' => $isSelected,
                                'text-gray-900' => !$isSelected,
                            ])>
                                {{ $option->name }}
                            </h5>
                            <p class="text-xs text-gray-500 truncate">
                                @if(count($option->sections) > 0)
                                    {{ count($option->sections) }} sections
                                @else
                                    Flexible structure
                                @endif
                            </p>
                        </div>

                        {{-- Selection Indicator --}}
                        <div class="shrink-0">
                            @if($isSelected)
                                <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @else
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 group-hover:border-gray-400 transition-colors"></div>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Browse All Templates Divider (only show when not searching) --}}
        @if(!$search)
            <div class="flex items-center gap-3 pt-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide shrink-0">Browse all templates</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>
        @endif

        {{-- My Templates Section (if user has any) --}}
        @if($this->myTemplates->isNotEmpty())
            <div
                x-data="{ expanded: {{ $search ? 'true' : 'true' }} }"
                x-effect="if ($wire.search) expanded = true"
                class="border border-indigo-200 rounded-xl overflow-hidden bg-indigo-50/30"
            >
                {{-- Category Header --}}
                <button
                    @click="expanded = !expanded"
                    class="w-full flex items-center justify-between px-4 py-3 bg-indigo-50 hover:bg-indigo-100 transition"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base">✨</span>
                        <h4 class="text-sm font-medium text-indigo-700">My Templates</h4>
                        <span class="text-xs text-indigo-400">({{ $this->myTemplates->count() }})</span>
                    </div>
                    <svg
                        class="w-5 h-5 text-indigo-400 transition-transform duration-200"
                        :class="{ 'rotate-180': expanded }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Template Grid --}}
                <div x-show="expanded" x-collapse>
                    <div class="p-4 grid grid-cols-2 gap-3">
                        @foreach($this->myTemplates as $template)
                            @include('livewire.projects.partials._template-card', [
                                'template' => $template,
                                'isSelected' => $selectedTemplateId === $template->id,
                                'isRecommended' => false,
                                'variant' => 'user',
                            ])
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Built-in Templates by Category --}}
        @foreach($this->builtInGroupedTemplates as $categoryKey => $group)
            @if($group['category'])
                @php
                    $isCore = $group['category']->value === 'core';
                    // Skip core category if not searching (already shown in hero + quick options)
                    $skipCategory = !$search && $isCore;
                @endphp

                @if(!$skipCategory)
                    <div
                        x-data="{ expanded: {{ ($isCore || $search) ? 'true' : 'false' }} }"
                        x-effect="if ($wire.search) expanded = true"
                        class="border border-gray-200 rounded-xl overflow-hidden"
                    >
                        {{-- Category Header (Collapsible) --}}
                        <button
                            @click="expanded = !expanded"
                            class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ $group['category']->icon() }}</span>
                                <h4 class="text-sm font-medium text-gray-700">{{ $group['category']->label() }}</h4>
                                <span class="text-xs text-gray-400">({{ count($group['templates']) }})</span>
                            </div>
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': expanded }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Template Grid --}}
                        <div x-show="expanded" x-collapse>
                            <div class="p-4 grid grid-cols-2 gap-3">
                                @foreach($group['templates'] as $template)
                                    @php
                                        $isRecommended = $template->name === 'PlanForge PRD' || $template->name === 'PlanForge Tech Spec';
                                        // Skip recommended in category grid when not searching (shown as hero)
                                        $skipTemplate = !$search && $isRecommended;
                                    @endphp

                                    @if(!$skipTemplate)
                                        @include('livewire.projects.partials._template-card', [
                                            'template' => $template,
                                            'isSelected' => $selectedTemplateId === $template->id,
                                            'isRecommended' => $isRecommended,
                                            'variant' => 'builtin',
                                        ])
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        @endforeach

        {{-- Show Core Templates when searching --}}
        @if($search)
            @php
                $coreGroup = $this->builtInGroupedTemplates->get('core');
            @endphp
            @if($coreGroup && count($coreGroup['templates']) > 0)
                <div
                    x-data="{ expanded: true }"
                    class="border border-gray-200 rounded-xl overflow-hidden"
                >
                    <button
                        @click="expanded = !expanded"
                        class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-base">{{ $coreGroup['category']->icon() }}</span>
                            <h4 class="text-sm font-medium text-gray-700">{{ $coreGroup['category']->label() }}</h4>
                            <span class="text-xs text-gray-400">({{ count($coreGroup['templates']) }})</span>
                        </div>
                        <svg
                            class="w-5 h-5 text-gray-400 transition-transform duration-200"
                            :class="{ 'rotate-180': expanded }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="expanded" x-collapse>
                        <div class="p-4 grid grid-cols-2 gap-3">
                            @foreach($coreGroup['templates'] as $template)
                                @php
                                    $isRecommended = $template->name === 'PlanForge PRD' || $template->name === 'PlanForge Tech Spec';
                                @endphp
                                @include('livewire.projects.partials._template-card', [
                                    'template' => $template,
                                    'isSelected' => $selectedTemplateId === $template->id,
                                    'isRecommended' => $isRecommended,
                                    'variant' => 'builtin',
                                ])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif

    {{-- Create Custom Link --}}
    <div class="flex justify-center pt-2">
        <a
            href="{{ route('templates.create') }}"
            wire:navigate
            class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium transition"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create custom template
        </a>
    </div>

    {{-- Preview Slide-over --}}
    @if($this->previewTemplate)
        <div
            x-data="{ show: @entangle('showPreview').live }"
            x-show="show"
            x-cloak
            class="fixed inset-0 z-50 overflow-hidden"
            @keydown.escape.window="$wire.closePreview()"
        >
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="ease-in-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in-out duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 transition-opacity"
                wire:click="closePreview"
            ></div>

            {{-- Slide-over panel --}}
            <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div
                    x-show="show"
                    x-transition:enter="transform transition ease-in-out duration-300"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-200"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-lg"
                >
                    <div class="flex h-full flex-col bg-white shadow-xl">
                        {{-- Header --}}
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ $this->previewTemplate->name }}</h2>
                                </div>
                                <button
                                    wire:click="closePreview"
                                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            {{-- Badges --}}
                            <div class="flex items-center gap-2 mt-2">
                                @if($this->previewTemplate->is_built_in)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-100 text-indigo-700">
                                        Built-in
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ count($this->previewTemplate->sections ?? []) }} sections
                                </span>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 overflow-y-auto">
                            {{-- Description --}}
                            @if($this->previewTemplate->description)
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ $this->previewTemplate->description }}</p>
                                </div>
                            @endif

                            {{-- Sections List --}}
                            <div class="px-6 py-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-4">Template Sections</h3>
                                <div class="space-y-3">
                                    @forelse($this->previewTemplate->sections ?? [] as $index => $section)
                                        <div class="flex gap-3 p-3 bg-gray-50 rounded-xl">
                                            <span class="flex-shrink-0 w-6 h-6 bg-white rounded-full flex items-center justify-center text-xs font-semibold text-gray-500 ring-1 ring-gray-200 shadow-sm">
                                                {{ $index + 1 }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <h5 class="font-medium text-gray-900 text-sm">{{ $section['title'] }}</h5>
                                                @if(!empty($section['description']))
                                                    <p class="text-xs text-gray-500 mt-1">{{ $section['description'] }}</p>
                                                @endif
                                                @if(!empty($section['guidance']))
                                                    <div class="mt-2 p-2 bg-amber-50 rounded-lg border border-amber-100">
                                                        <p class="text-xs text-amber-700">
                                                            <span class="font-medium">Guidance:</span> {{ $section['guidance'] }}
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-8">
                                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                            </svg>
                                            <p class="text-sm text-gray-500">No predefined sections</p>
                                            <p class="text-xs text-gray-400 mt-1">This template allows flexible structure</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between gap-3">
                                <button
                                    wire:click="closePreview"
                                    class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition"
                                >
                                    Close
                                </button>
                                <button
                                    wire:click="selectFromPreview"
                                    @class([
                                        'px-6 py-2.5 text-sm font-semibold rounded-xl transition flex items-center gap-2',
                                        'bg-green-600 text-white' => $selectedTemplateId === $this->previewTemplate->id,
                                        'bg-indigo-600 text-white hover:bg-indigo-700' => $selectedTemplateId !== $this->previewTemplate->id,
                                    ])
                                >
                                    @if($selectedTemplateId === $this->previewTemplate->id)
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Selected
                                    @else
                                        Use This Template
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
