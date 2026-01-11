<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Create Your Template</h1>
            <div class="flex items-center gap-3">
                @if($currentStep === 2 && $mode === 'manual')
                    <button
                        wire:click="openPreview"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview Template
                    </button>
                @endif
                <a href="{{ route('templates.index') }}" wire:navigate
                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="flex">
        {{-- Left Sidebar - Step Progress --}}
        <div class="w-64 bg-white border-r border-gray-200 p-6 flex-shrink-0">
            <div class="relative">
                {{-- Vertical connector line (positioned behind circles) --}}
                <div class="absolute left-4 top-8 bottom-8 w-0.5 bg-gray-200" style="transform: translateX(-50%);"></div>

                <div class="relative space-y-6">
                    {{-- Step 1 --}}
                    <div class="flex items-start gap-3">
                        <div @class([
                            'relative z-10 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 border-2',
                            'bg-indigo-600 border-indigo-600 text-white' => $currentStep >= 1,
                        ])>
                            @if($currentStep > 1)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="text-sm font-medium">1</span>
                            @endif
                        </div>
                        <div class="pt-1">
                            <div @class([
                                'font-medium',
                                'text-indigo-600' => $currentStep === 1,
                                'text-gray-900' => $currentStep > 1,
                            ])>Choose Method</div>
                            <div class="text-sm text-gray-500">Select how to create</div>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="flex items-start gap-3">
                        <div @class([
                            'relative z-10 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 border-2',
                            'bg-indigo-600 border-indigo-600 text-white' => $currentStep === 2,
                            'bg-white border-gray-300 text-gray-400' => $currentStep < 2,
                        ])>
                            @if($currentStep === 2)
                                <span class="text-sm font-medium">2</span>
                            @else
                                <span class="text-sm font-medium">2</span>
                            @endif
                        </div>
                        <div class="pt-1">
                            <div @class([
                                'font-medium',
                                'text-indigo-600' => $currentStep === 2,
                                'text-gray-400' => $currentStep < 2,
                            ])>Build Template</div>
                            <div class="text-sm text-gray-500">Define sections</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Content Area --}}
        <div class="flex-1 p-8 overflow-auto">
            @if($currentStep === 1)
                {{-- Step 1: Choose Method --}}
                <div class="max-w-2xl mx-auto">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Choose how to create your template</h2>

                    <div class="grid grid-cols-2 gap-6">
                        {{-- Paste Content Card --}}
                        <button
                            wire:click="selectMethod('paste')"
                            class="p-6 bg-white rounded-xl border-2 border-gray-200 hover:border-indigo-300 hover:shadow-md transition text-left group"
                        >
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-indigo-200 transition">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Paste Content</h3>
                            <p class="text-sm text-gray-500">
                                Copy and paste your existing template content for quick setup and customization.
                            </p>
                        </button>

                        {{-- Build Manually Card --}}
                        <button
                            wire:click="selectMethod('manual')"
                            class="p-6 bg-white rounded-xl border-2 border-gray-200 hover:border-pink-300 hover:shadow-md transition text-left group"
                        >
                            <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4 group-hover:bg-pink-200 transition">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-2">Build Manually</h3>
                            <p class="text-sm text-gray-500">
                                Create your template from scratch with full control over every section and detail.
                            </p>
                        </button>
                    </div>
                </div>
            @elseif($currentStep === 2 && $mode === 'paste' && !$isParsing && empty($name))
                {{-- Step 2: Paste Content --}}
                <div class="max-w-2xl">
                    {{-- Back Button + Header --}}
                    <div class="flex items-center gap-3 mb-6">
                        <button wire:click="goBack" class="p-1 text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-900">Paste Your Template Content</h2>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Template Content</label>
                            <textarea
                                wire:model="pastedContent"
                                placeholder="Paste your existing template or document structure here. Include section headings and descriptions..."
                                rows="16"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y font-mono text-sm"
                            ></textarea>
                            <p class="mt-2 text-sm text-gray-500">
                                Paste at least 50 characters of content. Our AI will analyze it and extract the template structure.
                            </p>
                            @error('pastedContent')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button
                                wire:click="parseContent"
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition"
                            >
                                Generate Template
                            </button>
                        </div>
                    </div>
                </div>
            @elseif($isParsing)
                {{-- Parsing State --}}
                <div class="max-w-2xl" wire:poll.2s="checkParseResult">
                    <div class="flex items-center gap-3 mb-6">
                        <button wire:click="goBack" class="p-1 text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-900">Analyzing Content...</h2>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
                        <div class="animate-spin w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4"></div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Extracting Template Structure</h3>
                        <p class="text-gray-500">Our AI is analyzing your content and identifying sections...</p>
                    </div>
                </div>
            @else
                {{-- Step 2: Template Editor (Manual Mode or After Parsing) --}}
                <div class="max-w-2xl">
                    {{-- Back Button + Header --}}
                    <div class="flex items-center gap-3 mb-6">
                        <button wire:click="goBack" class="p-1 text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-900">Template Editor</h2>
                    </div>

                    <div class="space-y-6">
                        {{-- Template Title --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Template Title</label>
                            <input
                                type="text"
                                wire:model="name"
                                placeholder="Enter template title..."
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                wire:model="description"
                                placeholder="Describe what this template is for and how it should be used..."
                                rows="4"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                            ></textarea>
                        </div>

                        {{-- Instructions --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                            <textarea
                                wire:model="instructions"
                                placeholder="Provide instructions on how to use this template..."
                                rows="4"
                                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                            ></textarea>
                            <p class="mt-1 text-sm text-gray-500">These instructions will guide the AI when generating documents with this template.</p>
                        </div>

                        {{-- Add Section Button --}}
                        <div class="flex justify-end">
                            <button
                                wire:click="addSection"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Section
                            </button>
                        </div>

                        {{-- Template Sections --}}
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-medium text-gray-900">Template Sections</h3>
                                <span class="text-sm text-gray-500">{{ count($sections) }} {{ Str::plural('section', count($sections)) }}</span>
                            </div>

                            <div class="space-y-5" x-data x-sortable="reorderSection">
                                @foreach($sections as $index => $section)
                                    <div
                                        wire:key="section-{{ $index }}"
                                        class="relative bg-white border border-gray-200 rounded-2xl p-5"
                                    >
                                        {{-- Delete Button - top right corner --}}
                                        <button
                                            wire:click="removeSection({{ $index }})"
                                            class="absolute text-white rounded-full flex items-center justify-center transition shadow-md hover:opacity-90"
                                            style="top: 12px; right: 12px; width: 28px; height: 28px; background-color: #ec4899;"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>

                                        <div class="flex gap-3 pr-10">
                                            {{-- Drag Handle --}}
                                            <div data-sortable-handle class="flex-shrink-0 pt-1 cursor-grab text-gray-400 hover:text-gray-600">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="9" cy="6" r="1.5"/>
                                                    <circle cx="9" cy="12" r="1.5"/>
                                                    <circle cx="9" cy="18" r="1.5"/>
                                                    <circle cx="15" cy="6" r="1.5"/>
                                                    <circle cx="15" cy="12" r="1.5"/>
                                                    <circle cx="15" cy="18" r="1.5"/>
                                                </svg>
                                            </div>

                                            {{-- Section Content --}}
                                            <div class="flex-1 space-y-4">
                                                {{-- Section Title --}}
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Section Title</label>
                                                    <input
                                                        type="text"
                                                        wire:model="sections.{{ $index }}.title"
                                                        placeholder="Enter section title..."
                                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    />
                                                    @error("sections.{$index}.title")
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Section Description --}}
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Section Description</label>
                                                    <textarea
                                                        wire:model="sections.{{ $index }}.description"
                                                        placeholder="Describe what this section should contain..."
                                                        rows="4"
                                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-y"
                                                    ></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(empty($sections))
                                <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-lg">
                                    <p class="text-gray-500">No sections added yet. Click "Add Section" to start.</p>
                                </div>
                            @endif
                        </div>

                        {{-- Save Button --}}
                        <div class="flex justify-end pt-4 border-t border-gray-200">
                            <button
                                wire:click="save"
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition"
                            >
                                Save Template
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Preview Modal --}}
    @if($showPreview)
        <div class="fixed inset-0 z-50 overflow-hidden">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 wire:click="closePreview"></div>

            {{-- Panel --}}
            <div class="absolute inset-y-0 right-0 max-w-lg w-full bg-white shadow-xl flex flex-col">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <h2 class="text-lg font-semibold text-gray-900">Preview Template</h2>
                    <button wire:click="closePreview"
                            class="p-2 text-gray-400 hover:text-gray-600 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="border border-gray-200 rounded-xl p-6">
                        {{-- Template Header --}}
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-semibold text-gray-900">{{ $name ?: 'Untitled Template' }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Private
                            </span>
                        </div>

                        @if($description)
                            <p class="text-gray-600 mb-6">{{ $description }}</p>
                        @endif

                        {{-- Template Sections --}}
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-900">Template Sections</h4>
                                <span class="text-sm text-gray-500">{{ count($sections) }} {{ Str::plural('section', count($sections)) }}</span>
                            </div>

                            <div class="space-y-3">
                                @forelse($sections as $section)
                                    @if(!empty($section['title']))
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <h5 class="font-medium text-gray-900 mb-1">{{ $section['title'] }}</h5>
                                            @if(!empty($section['description']))
                                                <p class="text-sm text-gray-600">{{ $section['description'] }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @empty
                                    <p class="text-sm text-gray-500 italic">No sections defined</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
