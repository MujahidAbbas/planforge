<div @if($this->isRunning) wire:poll.2s="poll" @endif>
    @if($this->latestRun)
        @php
            $run = $this->latestRun;
            $statusColors = [
                'queued' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'running' => 'bg-blue-100 text-blue-800 border-blue-200',
                'partial' => 'bg-orange-100 text-orange-800 border-orange-200',
                'failed' => 'bg-red-100 text-red-800 border-red-200',
                'succeeded' => 'bg-green-100 text-green-800 border-green-200',
            ];
            $color = $statusColors[$run->status->value] ?? 'bg-gray-100 text-gray-800 border-gray-200';

            $stepLabels = [
                'prd' => 'PRD',
                'tech' => 'Tech Spec',
                'tasks' => 'Tasks',
            ];
        @endphp

        @if($this->isRunning)
            <div class="flex items-center gap-4 px-4 py-2 {{ $color }} border rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="font-medium text-sm">Generating</span>
                </div>

                @if($run->steps->isNotEmpty())
                    <div class="flex items-center gap-2">
                        @foreach($run->steps as $index => $step)
                            @php
                                $stepStatus = $step->status->value;
                                $stepColor = match($stepStatus) {
                                    'succeeded' => 'text-green-600',
                                    'running' => 'text-blue-600',
                                    'failed' => 'text-red-600',
                                    default => 'text-gray-400',
                                };
                                $dotColor = match($stepStatus) {
                                    'succeeded' => 'bg-green-500',
                                    'running' => 'bg-blue-500 animate-pulse',
                                    'failed' => 'bg-red-500',
                                    default => 'bg-gray-300',
                                };
                                $label = $stepLabels[$step->step->value] ?? ucfirst($step->step->value);
                            @endphp

                            @if($index > 0)
                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            @endif

                            <div class="flex items-center gap-1.5 {{ $stepColor }}">
                                <div class="w-2 h-2 rounded-full {{ $dotColor }}"></div>
                                <span class="text-xs font-medium">{{ $label }}</span>
                                @if($stepStatus === 'running')
                                    <span class="text-xs">...</span>
                                @elseif($stepStatus === 'succeeded')
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @elseif($stepStatus === 'failed')
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            {{-- Completed/Failed state - compact display --}}
            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 {{ $color }} rounded-full text-xs font-medium">
                    @if($run->status === \App\Enums\PlanRunStatus::Succeeded)
                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @elseif($run->status === \App\Enums\PlanRunStatus::Failed)
                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    {{ ucfirst($run->status->value) }}
                </span>
                @if($run->finished_at)
                    <span class="text-xs text-gray-500">
                        {{ $run->finished_at->diffForHumans() }}
                    </span>
                @endif
            </div>
        @endif
    @endif
</div>
