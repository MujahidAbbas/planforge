<div class="h-full p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Kanban Board</h2>
            <div class="flex items-center gap-2">
                <p class="text-sm text-gray-500">Drag and drop tasks across stages</p>
                @if($this->latestTaskSet)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        Generated from Tech Spec v{{ $this->latestTaskSet->sourceTechVersion?->id ? substr($this->latestTaskSet->sourceTechVersion->id, 0, 8) : 'N/A' }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($this->isStale)
                <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-sm text-amber-700">Tasks may be stale</span>
                </div>
            @endif
            @if($this->latestTaskSet || $this->isStale)
                <button
                    wire:click="regenerateTasks"
                    wire:loading.attr="disabled"
                    wire:target="regenerateTasks"
                    @class([
                        'px-4 py-2 rounded-lg transition text-sm font-medium flex items-center gap-2',
                        'bg-emerald-600 text-white hover:bg-emerald-700' => !$this->isGeneratingTasks,
                        'bg-gray-200 text-gray-500 cursor-not-allowed' => $this->isGeneratingTasks,
                    ])
                    @disabled($this->isGeneratingTasks)
                >
                    <span wire:loading.remove wire:target="regenerateTasks">
                        @if($this->isGeneratingTasks)
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        @endif
                    </span>
                    <span wire:loading wire:target="regenerateTasks">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    {{ $this->isGeneratingTasks ? 'Regenerating...' : 'Regenerate Tasks' }}
                </button>
            @endif
        </div>
    </div>

    <div class="h-[calc(100%-4rem)]">
        {{ $this->board }}
    </div>
</div>
