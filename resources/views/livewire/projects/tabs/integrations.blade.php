<div class="h-full p-6" @if($this->hasRunningSyncs) wire:poll.5s @endif>
    <script>
        window.copyToClipboard = function(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
        }
    </script>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Integrations</h2>
        <p class="text-sm text-gray-500">Connect external services to sync your tasks</p>
    </div>

    <!-- GitHub Integration Card -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-2xl" x-data="{ showSetup: false }">
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-gray-900 rounded-lg">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">GitHub Issues</h3>
                    <p class="text-sm text-gray-500">Sync tasks to GitHub Issues</p>
                </div>
            </div>

            @if($this->githubIntegration?->isConnected())
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                    Connected
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                    Not Connected
                </span>
            @endif
        </div>

        @if($this->githubIntegration?->isConnected())
            <!-- Connected State -->
            <div class="space-y-4">
                <!-- Repository Info -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $this->githubIntegration->getRepoFullName() }}</p>
                            <p class="text-xs text-gray-500">
                                @if($this->lastSyncedAt)
                                    Last synced {{ $this->lastSyncedAt }}
                                @else
                                    Never synced
                                @endif
                            </p>
                        </div>
                    </div>
                    <a
                        href="https://github.com/{{ $this->githubIntegration->getRepoFullName() }}/issues"
                        target="_blank"
                        class="text-sm text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
                    >
                        View Issues
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>

                <!-- Sync Message -->
                @if($syncMessage)
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                        <p class="text-sm text-blue-700">{{ $syncMessage }}</p>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <button
                        wire:click="triggerSync"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
                    >
                        <svg wire:loading wire:target="triggerSync" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg wire:loading.remove wire:target="triggerSync" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove wire:target="triggerSync">Sync Now</span>
                        <span wire:loading wire:target="triggerSync">Syncing...</span>
                    </button>

                    <form action="{{ route('integrations.github.disconnect', $this->project) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            onclick="return confirm('Are you sure you want to disconnect GitHub?')"
                            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition"
                        >
                            Disconnect
                        </button>
                    </form>
                </div>

                <!-- Recent Sync Runs -->
                @if(count($this->recentSyncRuns) > 0)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Syncs</h4>
                        <div class="space-y-2">
                            @foreach($this->recentSyncRuns as $run)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
                                    <div class="flex items-center gap-3">
                                        @if($run['status'] === 'completed')
                                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                        @elseif($run['status'] === 'running')
                                            <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                                        @else
                                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        @endif
                                        <span class="text-gray-600">{{ ucfirst($run['trigger']) }}</span>
                                        <span class="text-gray-400">{{ $run['started_at'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs">
                                        @if($run['created'] > 0)
                                            <span class="text-green-600">+{{ $run['created'] }} created</span>
                                        @endif
                                        @if($run['updated'] > 0)
                                            <span class="text-blue-600">{{ $run['updated'] }} updated</span>
                                        @endif
                                        @if($run['skipped'] > 0)
                                            <span class="text-gray-500">{{ $run['skipped'] }} unchanged</span>
                                        @endif
                                        @if($run['failed'] > 0)
                                            <span class="text-red-600">{{ $run['failed'] }} failed</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <!-- Not Connected State -->
            <div class="text-center py-6">
                <p class="text-sm text-gray-500 mb-4">
                    Connect a GitHub repository to automatically sync your tasks as GitHub Issues.
                </p>
                <a
                    href="{{ route('integrations.github.install', $this->project) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Connect GitHub
                </a>
            </div>

            <!-- Features List -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Features</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Sync tasks to GitHub Issues automatically
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Close issues when tasks are marked done
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update task status when issues are closed on GitHub
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Smart change detection - only sync what changed
                    </li>
                </ul>
            </div>
        @endif

        <!-- Setup Guide (inside the card) -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <button
                @click="showSetup = !showSetup"
                class="w-full flex items-center justify-between text-left group"
            >
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Setup Guide</h4>
                        <p class="text-xs text-gray-500">How to configure the GitHub integration</p>
                    </div>
                </div>
                <svg
                    class="w-5 h-5 text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': showSetup }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="showSetup" x-collapse class="mt-4">
                <!-- Step 1: Create GitHub App -->
                <div class="mb-6">
                    <h5 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-600 text-white rounded-full text-xs flex items-center justify-center">1</span>
                        Create a GitHub App
                    </h5>
                    <p class="text-sm text-gray-600 mb-3">
                        Go to <a href="https://github.com/settings/apps/new" target="_blank" class="text-indigo-600 hover:underline">GitHub Settings &rarr; Developer settings &rarr; GitHub Apps</a> and create a new app with these settings:
                    </p>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                            <div>
                                <span class="text-gray-600">Homepage URL:</span>
                                <code class="ml-2 text-xs text-gray-800">{{ config('app.url') }}</code>
                            </div>
                            <button
                                x-data="{ copied: false }"
                                @click="window.copyToClipboard('{{ config('app.url') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition"
                                title="Copy to clipboard"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                            <div class="min-w-0 flex-1">
                                <span class="text-gray-600">Callback URL:</span>
                                <code class="ml-2 text-xs text-gray-800 break-all">{{ route('integrations.github.callback') }}</code>
                            </div>
                            <button
                                x-data="{ copied: false }"
                                @click="window.copyToClipboard('{{ route('integrations.github.callback') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition flex-shrink-0 ml-2"
                                title="Copy to clipboard"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                            <div class="min-w-0 flex-1">
                                <span class="text-gray-600">Webhook URL:</span>
                                <code class="ml-2 text-xs text-gray-800 break-all">{{ route('webhooks.github') }}</code>
                            </div>
                            <button
                                x-data="{ copied: false }"
                                @click="window.copyToClipboard('{{ route('webhooks.github') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition flex-shrink-0 ml-2"
                                title="Copy to clipboard"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Webhook events:</span>
                            <span class="ml-2 text-gray-800">Issues</span>
                        </div>
                        <div class="p-2 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Permissions:</span>
                            <span class="ml-2 text-gray-800">Issues (Read & write), Metadata (Read-only)</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Environment Variables -->
                <div class="mb-6">
                    <h5 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-600 text-white rounded-full text-xs flex items-center justify-center">2</span>
                        Configure Environment Variables
                    </h5>
                    <p class="text-sm text-gray-600 mb-2">Add these to your <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">.env</code> file:</p>
                    <div class="relative" x-data="{ copied: false }">
                        <div class="bg-gray-900 rounded-lg p-3 text-sm font-mono text-gray-100 overflow-x-auto">
                            <div>GITHUB_APP_ID=<span class="text-gray-500">your_app_id</span></div>
                            <div>GITHUB_APP_SLUG=<span class="text-gray-500">your-app-slug</span></div>
                            <div>GITHUB_APP_CLIENT_ID=<span class="text-gray-500">your_client_id</span></div>
                            <div>GITHUB_APP_CLIENT_SECRET=<span class="text-gray-500">your_client_secret</span></div>
                            <div>GITHUB_APP_PRIVATE_KEY=<span class="text-gray-500">"-----BEGIN RSA..."</span></div>
                            <div>GITHUB_WEBHOOK_SECRET=<span class="text-gray-500">your_webhook_secret</span></div>
                        </div>
                        <button
                            @click="window.copyToClipboard('GITHUB_APP_ID=your_app_id\nGITHUB_APP_SLUG=your-app-slug\nGITHUB_APP_CLIENT_ID=your_client_id\nGITHUB_APP_CLIENT_SECRET=your_client_secret\nGITHUB_APP_PRIVATE_KEY=-----BEGIN RSA...\nGITHUB_WEBHOOK_SECRET=your_webhook_secret'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="absolute top-2 right-2 p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition"
                            title="Copy to clipboard"
                        >
                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        The private key should be the full PEM content with newlines as <code class="bg-gray-100 px-1 rounded">\n</code>
                    </p>
                </div>

                <!-- Step 3: Queue Worker -->
                <div class="mb-6">
                    <h5 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-600 text-white rounded-full text-xs flex items-center justify-center">3</span>
                        Run the Queue Worker
                    </h5>
                    <p class="text-sm text-gray-600 mb-2">Sync jobs run in the background. Start the queue worker:</p>
                    <div class="relative" x-data="{ copied: false }">
                        <div class="bg-gray-900 rounded-lg p-3 text-sm font-mono text-gray-100">
                            php artisan queue:work
                        </div>
                        <button
                            @click="window.copyToClipboard('php artisan queue:work'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="absolute top-2 right-2 p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition"
                            title="Copy to clipboard"
                        >
                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Connect -->
                <div>
                    <h5 class="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
                        <span class="w-5 h-5 bg-indigo-600 text-white rounded-full text-xs flex items-center justify-center">4</span>
                        Connect Your Repository
                    </h5>
                    <p class="text-sm text-gray-600">
                        Click the "Connect GitHub" button above to install the app on your repository and start syncing tasks.
                    </p>
                </div>

                <!-- Webhook Info (when connected) -->
                @if($this->githubIntegration?->isConnected())
                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <h5 class="text-sm font-semibold text-gray-900 mb-2">Webhook Configuration</h5>
                        <p class="text-sm text-gray-600 mb-2">Ensure your GitHub App webhook is configured to send <strong>Issues</strong> events to:</p>
                        <div class="flex items-center justify-between p-2 bg-gray-100 rounded-lg">
                            <code class="text-sm text-gray-700 break-all">{{ route('webhooks.github') }}</code>
                            <button
                                x-data="{ copied: false }"
                                @click="window.copyToClipboard('{{ route('webhooks.github') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition flex-shrink-0 ml-2"
                                title="Copy to clipboard"
                            >
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            When issues are closed or reopened on GitHub, task statuses will update automatically.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
