<x-layouts.app>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Connect GitHub Repository
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Select a repository for "{{ $project->name }}"
                    </h3>

                    @if(count($repositories) === 0)
                        <div class="rounded-md bg-yellow-50 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        No repositories available
                                    </h3>
                                    <p class="mt-2 text-sm text-yellow-700">
                                        The GitHub App doesn't have access to any repositories.
                                        Please update the installation permissions on GitHub.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('projects.workspace', $project) }}" class="text-indigo-600 hover:text-indigo-500">
                            &larr; Back to project
                        </a>
                    @else
                        <form action="{{ route('integrations.github.setup', $project) }}" method="POST">
                            @csrf

                            <div class="space-y-4">
                                <div>
                                    <label for="repo" class="block text-sm font-medium text-gray-700">
                                        Repository
                                    </label>
                                    <select name="repo" id="repo" required
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                        onchange="updateOwner(this)">
                                        <option value="">Select a repository...</option>
                                        @foreach($repositories as $repo)
                                            <option value="{{ $repo['name'] }}" data-owner="{{ $repo['owner']['login'] }}">
                                                {{ $repo['full_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="owner" id="owner">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Default Labels (optional)
                                    </label>
                                    <div class="flex gap-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="default_labels[]" value="planforge" checked
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-600">planforge</span>
                                        </label>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        These labels will be added to all synced issues.
                                    </p>
                                </div>

                                <div class="pt-4 flex items-center justify-between">
                                    <a href="{{ route('projects.workspace', $project) }}"
                                        class="text-sm text-gray-600 hover:text-gray-500">
                                        Cancel
                                    </a>
                                    <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Connect Repository
                                    </button>
                                </div>
                            </div>
                        </form>

                        <script>
                            function updateOwner(select) {
                                const option = select.selectedOptions[0];
                                document.getElementById('owner').value = option?.dataset?.owner || '';
                            }
                        </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
