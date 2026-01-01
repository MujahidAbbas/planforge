<div>
    @if($show)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="close">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Project Settings</h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="save">
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

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Note:</span> Changes will apply to future AI generations.
                            Previously generated content will not be affected.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="close"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
