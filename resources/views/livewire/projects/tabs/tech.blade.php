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

            {{-- Feedback Button (only when document exists) --}}
            @if($this->document)
                <livewire:projects.partials.feedback-panel
                    :project-id="$projectId"
                    document-type="tech"
                    :wire:key="'feedback-panel-tech-'.$projectId"
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
        {{-- Empty State with Template Selector and Sticky CTA --}}
        <div
            class="flex-1 flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden"
            x-data="{
                selectedTemplateName: '{{ $this->project->techTemplate?->name ?? 'No template selected' }}',
                sectionCount: {{ $this->project->techTemplate ? count($this->project->techTemplate->sections ?? []) : 0 }},
                hasTemplate: {{ $this->project->tech_template_id ? 'true' : 'false' }}
            }"
            @template-selected.window="
                selectedTemplateName = $event.detail.templateName;
                sectionCount = $event.detail.sectionCount;
                hasTemplate = true;
            "
            @template-cleared.window="
                selectedTemplateName = 'No template selected';
                sectionCount = 0;
                hasTemplate = false;
            "
        >
            {{-- Scrollable Content Area --}}
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-3xl w-full mx-auto p-8">
                    <div class="text-center mb-8">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Create Your Tech Spec</h3>
                        <p class="text-gray-500">Select a template to structure your document, then generate with AI</p>
                    </div>

                    {{-- Template Selector --}}
                    <livewire:projects.partials.template-selector
                        :project-id="$projectId"
                        document-type="tech"
                        :wire:key="'template-selector-tech-'.$projectId"
                    />
                </div>
            </div>

            {{-- Sticky CTA Bar --}}
            <div class="sticky bottom-0 border-t border-gray-200 bg-gradient-to-r from-gray-50 via-white to-gray-50 px-6 py-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <div class="max-w-3xl mx-auto flex items-center justify-between gap-4">
                    {{-- Selection Status --}}
                    <div class="flex items-center gap-3 min-w-0">
                        <template x-if="hasTemplate">
                            <div class="flex items-center gap-2 text-sm">
                                <div class="w-5 h-5 rounded-full bg-green-500 flex items-center justify-center shrink-0">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <span class="font-medium text-gray-900 truncate" x-text="selectedTemplateName"></span>
                                <span class="text-gray-400 shrink-0">&bull;</span>
                                <span class="text-gray-500 shrink-0">
                                    <span x-text="sectionCount"></span> sections
                                </span>
                            </div>
                        </template>
                        <template x-if="!hasTemplate">
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <div class="w-5 h-5 rounded-full border-2 border-gray-300 shrink-0"></div>
                                <span>No template selected</span>
                            </div>
                        </template>
                    </div>

                    {{-- Generate Button --}}
                    <button
                        wire:click="$dispatch('startPlanRun')"
                        wire:loading.attr="disabled"
                        wire:target="$dispatch('startPlanRun')"
                        class="group inline-flex items-center gap-2.5 px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all duration-200 font-semibold text-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0 shrink-0"
                    >
                        <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span wire:loading.remove wire:target="$dispatch('startPlanRun')">Generate Tech Spec with AI</span>
                        <span wire:loading wire:target="$dispatch('startPlanRun')" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Generating...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="flex-1 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <x-markdown-editor
                :content="$content"
                :editorMode="$editorMode"
                placeholder="# Technical Specification&#10;&#10;## Architecture&#10;Describe your system architecture..."
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
