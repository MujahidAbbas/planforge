<div class="h-full flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $this->project->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($this->project->idea, 80) }}</p>
            </div>
            <div class="flex items-center gap-4">
                <livewire:projects.plan-run-banner :project-id="$projectId" />

                {{-- Generate All Button --}}
                <button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    @disabled($isGenerating)
                    @class([
                        'px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2 text-sm font-medium',
                        'opacity-50 cursor-not-allowed' => $isGenerating,
                    ])
                >
                    <svg wire:loading wire:target="generate,regeneratePrd,regenerateTech" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="generate,regeneratePrd,regenerateTech" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span wire:loading.remove wire:target="generate,regeneratePrd,regenerateTech">Generate All</span>
                    <span wire:loading wire:target="generate,regeneratePrd,regenerateTech">Generating...</span>
                </button>

                {{-- Regenerate Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        @click.away="open = false"
                        @disabled($isGenerating)
                        @class([
                            'px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2 text-sm font-medium',
                            'opacity-50 cursor-not-allowed' => $isGenerating,
                        ])
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Regenerate
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                    >
                        <div class="px-3 py-2 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">PRD</p>
                        </div>
                        <button
                            wire:click="regeneratePrd(false)"
                            @click="open = false"
                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            PRD only
                        </button>
                        <button
                            wire:click="regeneratePrd(true)"
                            @click="open = false"
                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            PRD + Tech Spec
                        </button>

                        <div class="px-3 py-2 border-t border-b border-gray-100 mt-1">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tech Spec</p>
                        </div>
                        <button
                            wire:click="regenerateTech"
                            @click="open = false"
                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            Tech Spec (use current PRD)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <nav class="flex gap-1 mt-4 -mb-px">
            @foreach(['prd' => 'PRD', 'tech' => 'Tech Spec', 'kanban' => 'Kanban', 'export' => 'Export'] as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    @class([
                        'px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition',
                        'border-indigo-600 text-indigo-600 bg-indigo-50' => $tab === $key,
                        'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' => $tab !== $key,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </header>

    <!-- Tab Content -->
    <section class="flex-1 overflow-auto bg-gray-50">
        @switch($tab)
            @case('prd')
                <livewire:projects.tabs.prd :project-id="$projectId" :key="'prd-'.$projectId" />
                @break

            @case('tech')
                <livewire:projects.tabs.tech :project-id="$projectId" :key="'tech-'.$projectId" />
                @break

            @case('kanban')
                <livewire:projects.tabs.kanban-board :project-id="$projectId" :key="'kanban-'.$projectId" />
                @break

            @case('export')
                <livewire:projects.tabs.export :project-id="$projectId" :key="'export-'.$projectId" />
                @break
        @endswitch
    </section>
</div>
