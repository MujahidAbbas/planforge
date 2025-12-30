<div class="h-full flex flex-col p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Product Requirements Document</h2>
            <p class="text-sm text-gray-500">Define what you're building and why</p>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No PRD yet</h3>
                <p class="text-gray-500 mb-4">Start writing your requirements or generate them with AI</p>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Generate PRD
                </button>
            </div>
        </div>
    @else
        <div class="flex-1 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <textarea
                wire:model.live.debounce.500ms="content"
                class="w-full h-full p-4 resize-none border-0 focus:ring-0 font-mono text-sm"
                placeholder="# Product Requirements Document&#10;&#10;## Overview&#10;Describe your product..."
            ></textarea>
        </div>
    @endif
</div>
