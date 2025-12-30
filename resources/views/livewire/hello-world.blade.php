<div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <h1 class="text-4xl font-bold text-gray-800 mb-8">Hello World from Livewire!</h1>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <p class="text-6xl font-bold text-center text-indigo-600 mb-6">{{ $count }}</p>

        <div class="flex gap-4">
            <button
                wire:click="decrement"
                class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-semibold"
            >
                - Decrease
            </button>
            <button
                wire:click="increment"
                class="px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-semibold"
            >
                + Increase
            </button>
        </div>

        <p class="text-gray-500 text-center mt-6 text-sm">
            Click the buttons — no page reload!
        </p>
    </div>
</div>
