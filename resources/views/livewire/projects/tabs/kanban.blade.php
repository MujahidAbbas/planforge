<div class="h-full p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Kanban Board</h2>
            <p class="text-sm text-gray-500">Track your tasks across stages</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6 h-[calc(100%-4rem)]">
        <!-- Todo Column -->
        <div class="bg-gray-100 rounded-xl p-4 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-700">To Do</h3>
                <span class="bg-gray-300 text-gray-700 text-xs px-2 py-1 rounded-full">
                    {{ $this->todoTasks->count() }}
                </span>
            </div>
            <div class="flex-1 overflow-y-auto space-y-3">
                @forelse($this->todoTasks as $task)
                    <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition cursor-pointer">
                        <h4 class="font-medium text-gray-900 text-sm">{{ $task->title }}</h4>
                        @if($task->description)
                            <p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ Str::limit($task->description, 80) }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-3">
                            @if($task->estimate)
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $task->estimate }}</span>
                            @endif
                            @if($task->labels)
                                @foreach(array_slice($task->labels, 0, 2) as $label)
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $label }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="flex gap-1 mt-3">
                            <button
                                wire:click="moveTask('{{ $task->id }}', 'doing')"
                                class="text-xs text-indigo-600 hover:text-indigo-800"
                            >
                                Start &rarr;
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400 text-sm">No tasks</div>
                @endforelse
            </div>
        </div>

        <!-- Doing Column -->
        <div class="bg-blue-50 rounded-xl p-4 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-blue-700">In Progress</h3>
                <span class="bg-blue-200 text-blue-700 text-xs px-2 py-1 rounded-full">
                    {{ $this->doingTasks->count() }}
                </span>
            </div>
            <div class="flex-1 overflow-y-auto space-y-3">
                @forelse($this->doingTasks as $task)
                    <div class="bg-white rounded-lg p-4 shadow-sm border border-blue-200 hover:shadow-md transition cursor-pointer">
                        <h4 class="font-medium text-gray-900 text-sm">{{ $task->title }}</h4>
                        @if($task->description)
                            <p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ Str::limit($task->description, 80) }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-3">
                            @if($task->estimate)
                                <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ $task->estimate }}</span>
                            @endif
                        </div>
                        <div class="flex gap-2 mt-3">
                            <button
                                wire:click="moveTask('{{ $task->id }}', 'todo')"
                                class="text-xs text-gray-500 hover:text-gray-700"
                            >
                                &larr; Back
                            </button>
                            <button
                                wire:click="moveTask('{{ $task->id }}', 'done')"
                                class="text-xs text-green-600 hover:text-green-800"
                            >
                                Done &rarr;
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400 text-sm">No tasks</div>
                @endforelse
            </div>
        </div>

        <!-- Done Column -->
        <div class="bg-green-50 rounded-xl p-4 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-green-700">Done</h3>
                <span class="bg-green-200 text-green-700 text-xs px-2 py-1 rounded-full">
                    {{ $this->doneTasks->count() }}
                </span>
            </div>
            <div class="flex-1 overflow-y-auto space-y-3">
                @forelse($this->doneTasks as $task)
                    <div class="bg-white rounded-lg p-4 shadow-sm border border-green-200 hover:shadow-md transition cursor-pointer opacity-75">
                        <h4 class="font-medium text-gray-900 text-sm line-through">{{ $task->title }}</h4>
                        <div class="flex gap-2 mt-3">
                            <button
                                wire:click="moveTask('{{ $task->id }}', 'doing')"
                                class="text-xs text-gray-500 hover:text-gray-700"
                            >
                                &larr; Reopen
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400 text-sm">No tasks</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
