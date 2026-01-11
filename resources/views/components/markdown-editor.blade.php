@props([
    'content' => '',
    'placeholder' => '# Document\n\n## Overview\nStart writing...',
    'editorMode' => 'write',
])

<div
    x-data="{
        mode: $wire.editorMode,
        init() {
            // Sync with localStorage on init
            const stored = localStorage.getItem('markdown-editor-mode');
            if (stored && ['write', 'preview', 'split'].includes(stored)) {
                if (stored !== this.mode) {
                    this.mode = stored;
                    $wire.setEditorMode(stored);
                }
            }

            // Handle window resize - switch to write mode on mobile if in split
            const handleResize = () => {
                if (window.innerWidth < 768 && this.mode === 'split') {
                    this.setMode('write');
                }
            };
            window.addEventListener('resize', handleResize);
        },
        setMode(newMode) {
            this.mode = newMode;
            localStorage.setItem('markdown-editor-mode', newMode);
            $wire.setEditorMode(newMode);
        }
    }"
    x-on:resize.window="if (window.innerWidth < 768 && mode === 'split') setMode('write')"
    class="flex flex-col h-full"
>
    {{-- Mode Toggle Toolbar --}}
    <div class="flex items-center gap-1 p-2 border-b border-gray-200 bg-gray-50 rounded-t-xl">
        <button
            type="button"
            @click="setMode('write')"
            :class="mode === 'write' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition"
        >
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Write
            </span>
        </button>
        <button
            type="button"
            @click="setMode('preview')"
            :class="mode === 'preview' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition"
        >
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Preview
            </span>
        </button>
        <button
            type="button"
            @click="setMode('split')"
            :class="mode === 'split' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
            class="hidden md:flex px-3 py-1.5 text-sm font-medium rounded-md transition"
        >
            <span class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                Split
            </span>
        </button>
    </div>

    {{-- Editor Content --}}
    <div class="flex-1 flex overflow-hidden">
        {{-- Write Panel --}}
        <div
            x-show="mode === 'write' || mode === 'split'"
            :class="mode === 'split' ? 'w-1/2 border-r border-gray-200' : 'w-full'"
            class="h-full"
        >
            <textarea
                wire:model.live.debounce.500ms="content"
                class="w-full h-full p-4 resize-none border-0 focus:ring-0 font-mono text-sm bg-white"
                placeholder="{{ $placeholder }}"
            ></textarea>
        </div>

        {{-- Preview Panel --}}
        <div
            x-show="mode === 'preview' || mode === 'split'"
            :class="mode === 'split' ? 'w-1/2' : 'w-full'"
            class="h-full overflow-auto bg-white"
        >
            @if(empty(trim($content)))
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm">Nothing to preview</p>
                        <p class="text-xs text-gray-300 mt-1">Start writing to see the preview</p>
                    </div>
                </div>
            @else
                <div
                    x-data="codeBlockEnhancer()"
                    x-init="$nextTick(() => enhance())"
                    class="p-6 prose prose-sm max-w-none prose-headings:font-semibold prose-a:text-indigo-600 markdown-preview"
                    wire:key="preview-{{ md5($content) }}"
                    wire:ignore.self
                >
                    {!! $this->previewHtml !!}
                </div>
            @endif
        </div>
    </div>
</div>
