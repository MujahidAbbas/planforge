<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-gray-700">Choose a template</h3>
        @if($selectedTemplateId)
            <button
                wire:click="clearTemplate"
                class="text-xs text-gray-500 hover:text-gray-700"
            >
                Clear selection
            </button>
        @endif
    </div>

    {{-- Templates by Category --}}
    <div class="space-y-6 max-h-96 overflow-y-auto pr-2">
        @foreach($this->groupedTemplates as $group)
            @if($group['category'])
                <div>
                    {{-- Category Header --}}
                    <div class="flex items-center gap-2 mb-3 sticky top-0 bg-white py-1">
                        <span class="text-lg">{{ $group['category']->icon() }}</span>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {{ $group['category']->label() }}
                        </h4>
                    </div>

                    {{-- Template Grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($group['templates'] as $template)
                            <button
                                wire:click="selectTemplate('{{ $template->id }}')"
                                @class([
                                    'relative flex flex-col items-start p-3 rounded-lg border-2 transition-all text-left',
                                    'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' => $selectedTemplateId === $template->id,
                                    'border-gray-200 hover:border-gray-300 hover:bg-gray-50' => $selectedTemplateId !== $template->id,
                                ])
                            >
                                {{-- Badges --}}
                                <div class="absolute top-2 right-2 flex gap-1">
                                    @if($template->is_community)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                            {{ $template->author }}
                                        </span>
                                    @elseif($template->is_built_in)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                            Built-in
                                        </span>
                                    @endif
                                </div>

                                {{-- Template Name --}}
                                <div class="font-medium text-gray-900 text-sm pr-16">
                                    {{ $template->name }}
                                </div>

                                {{-- Template Description --}}
                                @if($template->description)
                                    <div class="text-xs text-gray-500 mt-1 line-clamp-2">
                                        {{ $template->description }}
                                    </div>
                                @endif

                                {{-- Section Count --}}
                                <div class="text-xs text-gray-400 mt-2">
                                    @if(count($template->sections) > 0)
                                        {{ count($template->sections) }} sections
                                    @else
                                        Flexible structure
                                    @endif
                                </div>

                                {{-- Selected Check --}}
                                @if($selectedTemplateId === $template->id)
                                    <div class="absolute bottom-2 right-2">
                                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Selected Template Preview --}}
    @if($this->selectedTemplate)
        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 max-h-64 overflow-y-auto">
            <div class="flex items-center justify-between mb-3 sticky top-0 bg-gray-50 pb-2">
                <h4 class="font-medium text-gray-900">{{ $this->selectedTemplate->name }}</h4>
                <span class="text-xs text-gray-500">{{ count($this->selectedTemplate->sections) }} sections</span>
            </div>

            @if($this->selectedTemplate->description)
                <p class="text-xs text-gray-600 mb-3">{{ $this->selectedTemplate->description }}</p>
            @endif

            @if(count($this->selectedTemplate->sections) > 0)
                <div class="space-y-2">
                    <div class="text-xs text-gray-600 font-medium">Document Structure:</div>
                    <ul class="space-y-1">
                        @foreach($this->selectedTemplate->sections as $section)
                            <li class="flex items-center gap-2 text-sm text-gray-700">
                                @if($section['required'] ?? false)
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0" title="Required"></span>
                                @else
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300 flex-shrink-0" title="Optional"></span>
                                @endif
                                <span>{{ $section['title'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($this->selectedTemplate->ai_instructions)
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="text-xs text-gray-600 font-medium mb-1">AI Instructions:</div>
                    <p class="text-xs text-gray-500 italic">{{ Str::limit($this->selectedTemplate->ai_instructions, 150) }}</p>
                </div>
            @endif

            @if($this->selectedTemplate->is_community && $this->selectedTemplate->author)
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="text-xs text-gray-500">
                        Created by <span class="font-medium text-purple-600">{{ $this->selectedTemplate->author }}</span>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Create Custom Link (Phase 2) --}}
    <div class="pt-2 border-t border-gray-200">
        <span class="inline-flex items-center gap-1 text-sm text-gray-400 cursor-not-allowed">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create custom template (coming soon)
        </span>
    </div>
</div>
