import './bootstrap';
import Sortable from 'sortablejs';

// Register sortable functionality with Alpine (loaded by Livewire)
document.addEventListener('alpine:init', () => {
    Alpine.directive('sortable', (el, { expression }, { evaluate }) => {
        const wireMethod = expression;

        Sortable.create(el, {
            animation: 150,
            ghostClass: 'opacity-50',
            handle: '[data-sortable-handle]',
            onEnd: function (evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;

                if (oldIndex !== newIndex) {
                    // Find the Livewire component and call the method
                    const component = Livewire.find(
                        el.closest('[wire\\:id]').getAttribute('wire:id')
                    );

                    if (component) {
                        component.call(wireMethod, oldIndex, newIndex);
                    }
                }
            }
        });
    });
});
