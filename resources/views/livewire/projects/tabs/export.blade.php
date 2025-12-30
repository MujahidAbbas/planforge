<div class="h-full p-6">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Export</h2>
        <p class="text-sm text-gray-500">Download your project documents and tasks</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Export PRD -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">PRD Document</h3>
                    <p class="text-sm text-gray-500">Product Requirements</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    wire:click="downloadPrdMarkdown"
                    class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Markdown
                </button>
            </div>
        </div>

        <!-- Export Tech Spec -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Tech Spec</h3>
                    <p class="text-sm text-gray-500">Technical Specification</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    wire:click="downloadTechMarkdown"
                    class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Markdown
                </button>
            </div>
        </div>

        <!-- Export Tasks -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Tasks</h3>
                    <p class="text-sm text-gray-500">{{ $this->project->tasks->count() }} tasks</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    wire:click="downloadTasksJson"
                    class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    JSON
                </button>
            </div>
        </div>

        <!-- Export All -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-3 bg-amber-100 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Full Project Kit</h3>
                    <p class="text-sm text-gray-500">PRD, Tech Spec, Tasks & metadata</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    wire:click="downloadProjectKit"
                    class="px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download ZIP
                </button>
            </div>
        </div>
    </div>

    <!-- Contents Preview -->
    <div class="mt-8 bg-gray-50 rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Project Kit Contents</h3>
        <div class="text-sm text-gray-600 space-y-2">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <code class="bg-white px-2 py-0.5 rounded">README.md</code>
                <span class="text-gray-500">- Project overview</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <code class="bg-white px-2 py-0.5 rounded">docs/PRD.md</code>
                <span class="text-gray-500">- Product Requirements Document</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <code class="bg-white px-2 py-0.5 rounded">docs/TECH.md</code>
                <span class="text-gray-500">- Technical Specification</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <code class="bg-white px-2 py-0.5 rounded">kanban/tasks.json</code>
                <span class="text-gray-500">- Tasks for re-import</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <code class="bg-white px-2 py-0.5 rounded">kanban/tasks.csv</code>
                <span class="text-gray-500">- Tasks for PM tools</span>
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <code class="bg-white px-2 py-0.5 rounded">meta.json</code>
                <span class="text-gray-500">- Export metadata</span>
            </div>
        </div>
    </div>
</div>
