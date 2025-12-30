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
