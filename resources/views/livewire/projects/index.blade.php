<div class="p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <p class="text-gray-600 mt-1">Manage your AI-powered project plans</p>
        </div>
        <button
            wire:click="openCreateModal"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Project
        </button>
    </div>

    @if($projects->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No projects yet</h3>
            <p class="text-gray-500 mb-6">Get started by creating your first project</p>
            <button
                wire:click="openCreateModal"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
            >
                Create Project
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($projects as $project)
                <a
                    href="{{ route('projects.workspace', $project) }}"
                    wire:navigate
                    class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md hover:border-indigo-300 transition group"
                >
                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition">
                        {{ $project->name }}
                    </h3>
                    <p class="text-gray-500 text-sm mt-2 line-clamp-2">
                        {{ Str::limit($project->idea, 100) }}
                    </p>
                    <div class="flex items-center gap-4 mt-4 text-xs text-gray-400">
                        <span>{{ $project->created_at->diffForHumans() }}</span>
                        <span class="px-2 py-1 bg-gray-100 rounded-full">
                            {{ $project->tasks_count ?? $project->tasks()->count() }} tasks
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <!-- Create Project Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="closeCreateModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Create New Project</h2>

                <form wire:submit="createProject">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                        <input
                            type="text"
                            wire:model="name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="e.g., AI Task Manager"
                        >
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Idea</label>
                        <textarea
                            wire:model="idea"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Describe your project idea in detail..."
                        ></textarea>
                        @error('idea') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- AI Provider Selection -->
                    @if($this->hasProviders)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">AI Provider</label>
                            <select
                                wire:model.live="selectedProvider"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                @foreach($this->availableProviders as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('selectedProvider') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                            <select
                                wire:model.live="selectedModel"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                @foreach($this->modelsForSelectedProvider as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                                <option value="custom">Custom Model ID...</option>
                            </select>
                            @error('selectedModel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                            @if($useCustomModel)
                                <input
                                    type="text"
                                    wire:model="customModel"
                                    class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Enter custom model ID (e.g., claude-opus-4-5-20250514)"
                                >
                                @error('customModel') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @endif
                        </div>
                    @else
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">No AI providers configured</p>
                                    <p class="text-sm text-yellow-700 mt-1">Add an API key to your <code class="bg-yellow-100 px-1 rounded">.env</code> file to enable AI generation.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeCreateModal"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
