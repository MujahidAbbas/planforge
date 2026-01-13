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

            {{-- Feedback Button (only when document exists) --}}
            @if($this->document)
                <livewire:projects.partials.feedback-panel
                    :project-id="$projectId"
                    document-type="prd"
                    :wire:key="'feedback-panel-prd-'.$projectId"
                />
            @endif

            {{-- Version History Button --}}
            @if($this->document)
                <button
                    wire:click="openVersionHistory"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                    title="Version History"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
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
        {{-- Empty State with Template Selector --}}
        <div class="flex-1 flex items-center justify-center bg-white rounded-xl border border-gray-200">
            <div class="max-w-md w-full p-8">
                <div class="text-center mb-6">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No PRD yet</h3>
                    <p class="text-gray-500 mb-6">Choose a template to structure your document, then generate with AI</p>
                </div>

                {{-- Template Selector --}}
                <livewire:projects.partials.template-selector
                    :project-id="$projectId"
                    document-type="prd"
                    :wire:key="'template-selector-prd-'.$projectId"
                />

                {{-- Generate Button --}}
                <div class="mt-6 text-center">
                    <button
                        wire:click="$dispatch('startPlanRun')"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                    >
                        Generate PRD
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- Editor --}}
        <div class="flex-1 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <x-markdown-editor
                :content="$content"
                :editorMode="$editorMode"
                placeholder="# Product Requirements Document&#10;&#10;## Overview&#10;Describe your product..."
            />
        </div>
    @endif

    {{-- Version History Slide-over --}}
    @if($this->document)
        <x-version-history-slide-over
            :versions="$this->versions"
            :selectedVersion="$this->selectedVersionForPreview"
            :previewVersionId="$previewVersionId"
            :currentVersionId="$this->document->current_version_id"
        />
    @endif
</div>
