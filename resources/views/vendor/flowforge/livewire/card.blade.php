@props(['columnId', 'record'])

@php
    $processedRecordActions = $this->getBoard()->getBoardRecordActions($record);
    $hasActions = !empty($processedRecordActions);
    $cardAction = $this->getBoard()->getCardAction();
    $hasCardAction = $cardAction !== null;
    $hasPositionIdentifier = $this->getBoard()->getPositionIdentifierAttribute() !== null;

    // Get priority for border color
    $model = $record['model'] ?? null;
    $priority = $model?->priority?->value ?? $model?->priority ?? null;
    $priorityBorderClass = match($priority) {
        'high' => 'border-l-4 border-l-red-500',
        'med' => 'border-l-4 border-l-amber-400',
        'low' => 'border-l-4 border-l-gray-300',
        default => '',
    };
@endphp

<div
    @class([
        'flowforge-card mb-3 bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-200 hover:shadow-md',
        $priorityBorderClass,
        'cursor-pointer' => $hasActions || $hasCardAction,
        'cursor-pointer transition-all duration-100 ease-in-out hover:shadow-lg hover:border-gray-400 active:shadow-md' => $hasCardAction,
        'cursor-grab hover:cursor-grabbing' => $hasPositionIdentifier,
        'cursor-default' => !$hasActions && !$hasCardAction && !$hasPositionIdentifier,
    ])
    @if($hasPositionIdentifier)
        x-sortable-handle
    x-sortable-item="{{ $record['id'] }}"
    @endif
    data-card-id="{{ $record['id'] }}"
    data-position="{{ $record['position'] ?? '' }}"
>
    <div class="flowforge-card-content">
        <div class="flex items-start justify-between">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white px-3 pt-3 pb-1 leading-tight"
                @if($hasCardAction && $cardAction)
                    wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
                style="cursor: pointer;"
                @endif
            >
                {{ $record['title'] }}
            </h4>

            @if($hasActions)
                <div class="pt-2 pr-2">
                    <x-filament-actions::group :actions="$processedRecordActions"/>
                </div>
            @endif
        </div>

        <div class="px-3 pb-3"
             @if($hasCardAction && $cardAction)
                 wire:click="mountAction('{{ $cardAction }}', [], @js(['recordKey' => $record['id']]))"
             style="cursor: pointer;"
            @endif
        >
            {{-- Render card schema with compact spacing --}}
            @if(filled($record['schema']))
                {{ $record['schema'] }}
            @endif
        </div>
    </div>
</div>
