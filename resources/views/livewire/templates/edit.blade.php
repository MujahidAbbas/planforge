<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-900">Edit Template</h1>
            <div class="flex items-center gap-3">
                <button
                    wire:click="openPreview"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Preview
                </button>
                <a href="{{ route('templates.index') }}" wire:navigate
                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="p-8">
        <div class="max-w-2xl mx-auto">
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

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <button
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete this template? This action cannot be undone."
                        class="px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg font-medium transition"
                    >
                        Delete Template
                    </button>
                    <button
                        wire:click="save"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
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
