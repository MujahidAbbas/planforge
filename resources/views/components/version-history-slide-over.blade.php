@props([
    'versions',
    'selectedVersion' => null,
    'previewVersionId' => null,
    'currentVersionId' => null,
])

<div
    x-data="{ show: @entangle('showVersionHistory').live }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    @keydown.escape.window="$wire.closeVersionHistory()"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/40 transition-opacity"
        wire:click="closeVersionHistory"
    ></div>

    {{-- Slide-over panel --}}
    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div
            x-show="show"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="w-screen max-w-3xl"
        >
            <div class="flex h-full flex-col bg-white shadow-xl">
                {{-- Header --}}
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-900">Version History</h2>
                        </div>
                        <button
                            wire:click="closeVersionHistory"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content: Two-column layout --}}
                <div class="flex-1 flex overflow-hidden">
                    {{-- Left: Version list --}}
                    <div class="w-72 border-r border-gray-200 overflow-y-auto bg-gray-50">
                        @if($versions->isEmpty())
                            <div class="p-6 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-gray-500">No versions yet</p>
                            </div>
                        @else
                            <div class="py-2">
                                @foreach($versions as $version)
                                    <button
                                        wire:click="selectVersion('{{ $version->id }}')"
                                        wire:key="version-{{ $version->id }}"
                                        @class([
                                            'w-full text-left px-4 py-3 hover:bg-white transition border-l-4',
                                            'bg-white border-l-indigo-600 shadow-sm' => $previewVersionId === $version->id,
                                            'border-l-transparent' => $previewVersionId !== $version->id,
                                        ])
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                {{-- Author --}}
                                                <div class="flex items-center gap-2 mb-1">
                                                    @if($version->created_by && $version->createdBy)
                                                        <span class="text-sm font-medium text-gray-900">
                                                            {{ $version->createdBy->name }}
                                                        </span>
                                                    @elseif($version->plan_run_id)
                                                        <span class="text-sm font-medium text-indigo-600 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                            </svg>
                                                            AI Generated
                                                        </span>
                                                    @else
                                                        <span class="text-sm font-medium text-gray-500">Unknown</span>
                                                    @endif

                                                    @if($currentVersionId === $version->id)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                                            Current
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Timestamp --}}
                                                <p
                                                    class="text-xs text-gray-500"
                                                    title="{{ $version->created_at->format('F j, Y g:i A') }}"
                                                >
                                                    {{ $version->created_at->diffForHumans() }}
                                                </p>

                                                {{-- Summary --}}
                                                @if($version->summary)
                                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">
                                                        {{ $version->summary }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Right: Preview & Restore --}}
                    <div class="flex-1 flex flex-col overflow-hidden bg-white">
                        @if($selectedVersion)
                            {{-- Preview Header --}}
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">
                                        Version from {{ $selectedVersion->created_at->format('M j, Y') }} at {{ $selectedVersion->created_at->format('g:i A') }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        @if($selectedVersion->created_by && $selectedVersion->createdBy)
                                            By {{ $selectedVersion->createdBy->name }}
                                        @elseif($selectedVersion->plan_run_id)
                                            AI Generated
                                        @endif
                                    </p>
                                </div>

                                @if($selectedVersion->id !== $currentVersionId)
                                    <button
                                        wire:click="restoreVersion('{{ $selectedVersion->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="restoreVersion"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium flex items-center gap-2 disabled:opacity-50"
                                    >
                                        <svg wire:loading.remove wire:target="restoreVersion" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <svg wire:loading wire:target="restoreVersion" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="restoreVersion">Restore This Version</span>
                                        <span wire:loading wire:target="restoreVersion">Restoring...</span>
                                    </button>
                                @else
                                    <span class="px-4 py-2 bg-gray-100 text-gray-500 rounded-lg text-sm font-medium">
                                        Current Version
                                    </span>
                                @endif
                            </div>

                            {{-- Preview Content --}}
                            <div class="flex-1 overflow-y-auto p-6">
                                <pre class="whitespace-pre-wrap font-mono text-sm text-gray-700 bg-gray-50 p-4 rounded-lg border border-gray-200">{{ $selectedVersion->content_md }}</pre>
                            </div>
                        @else
                            {{-- Empty state --}}
                            <div class="flex-1 flex items-center justify-center text-gray-400">
                                <div class="text-center">
                                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <p class="text-sm">Select a version to preview</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast notification --}}
<div
    x-data="{ show: false, message: '' }"
    @version-restored.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    x-cloak
    class="fixed bottom-4 right-4 flex items-center gap-2 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg z-[60]"
>
    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    <p class="text-sm font-medium" x-text="message"></p>
</div>
