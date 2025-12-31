<div class="h-full flex flex-col p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Technical Specification</h2>
            <p class="text-sm text-gray-500">Architecture, tech stack, and implementation details</p>
        </div>
        <div class="flex items-center gap-3">
            @if($isDirty)
                <span class="text-sm text-amber-600">Unsaved changes</span>
            @endif
            <button
                wire:click="save"
                @class([
                    'px-4 py-2 rounded-lg transition text-sm font-medium',
                    'bg-indigo-600 text-white hover:bg-indigo-700' => $isDirty,
                    'bg-gray-200 text-gray-500 cursor-not-allowed' => !$isDirty,
                ])
                @disabled(!$isDirty)
            >
                Save
            </button>
            @if($this->document)
                <button
                    wire:click="generateTasks"
                    wire:loading.attr="disabled"
                    wire:target="generateTasks"
                    @class([
                        'px-4 py-2 rounded-lg transition text-sm font-medium flex items-center gap-2',
                        'bg-emerald-600 text-white hover:bg-emerald-700' => !$this->isGeneratingTasks,
                        'bg-gray-200 text-gray-500 cursor-not-allowed' => $this->isGeneratingTasks,
                    ])
                    @disabled($this->isGeneratingTasks)
                >
                    <span wire:loading.remove wire:target="generateTasks">
                        @if($this->isGeneratingTasks)
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        @endif
                    </span>
                    <span wire:loading wire:target="generateTasks">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    {{ $this->isGeneratingTasks ? 'Generating...' : 'Generate Tasks' }}
                </button>
            @endif
        </div>
    </div>

    @if(!$this->document)
        <div class="flex-1 flex items-center justify-center bg-white rounded-xl border border-gray-200">
            <div class="text-center p-8">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Tech Spec yet</h3>
                <p class="text-gray-500 mb-4">Generate from your PRD or write manually</p>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Generate Tech Spec
                </button>
            </div>
        </div>
    @else
        <div class="flex-1 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <textarea
                wire:model.live.debounce.500ms="content"
                class="w-full h-full p-4 resize-none border-0 focus:ring-0 font-mono text-sm"
                placeholder="# Technical Specification&#10;&#10;## Architecture&#10;Describe your system architecture..."
            ></textarea>
        </div>
    @endif
</div>
