<?php

namespace App\Services\GitHub;

use App\Models\Integration;
use App\Models\Task;

class TaskToIssueMapper
{
    /**
     * Map a PlanForge task to GitHub issue format.
     *
     * @return array<string, mixed>
     */
    public function toIssue(Task $task, Integration $integration): array
    {
        $settings = $integration->settings;

        return [
            'title' => $this->formatTitle($task),
            'body' => $this->formatBody($task),
            'labels' => $this->mapLabels($task, $settings),
        ];
    }

    /**
     * Generate content hash for change detection.
     */
    public function generateHash(Task $task): string
    {
        $content = json_encode([
            'title' => $task->title,
            'description' => $task->description,
            'acceptance_criteria' => $task->acceptance_criteria,
            'status' => $task->status->value,
            'category' => $task->category?->value,
        ]);

        return hash('sha256', $content);
    }

    private function formatTitle(Task $task): string
    {
        $prefix = '';

        // Add category prefix if exists
        if ($task->category) {
            $prefix = "[{$task->category->value}] ";
        }

        return $prefix.$task->title;
    }

    private function formatBody(Task $task): string
    {
        $body = [];

        // Description
        if ($task->description) {
            $body[] = "## Description\n\n{$task->description}";
        }

        // Acceptance Criteria (array field)
        if (! empty($task->acceptance_criteria)) {
            $criteria = $this->formatAcceptanceCriteria($task->acceptance_criteria);
            $body[] = "## Acceptance Criteria\n\n{$criteria}";
        }

        // Estimate
        if ($task->estimate) {
            $body[] = "**Estimate:** {$task->estimate}";
        }

        // Metadata footer
        $appUrl = config('app.url');
        $body[] = '---';
        $body[] = "_Synced from [PlanForge]({$appUrl}/projects/{$task->project_id})_";

        return implode("\n\n", $body);
    }

    /**
     * @param  array<int, string>  $criteria
     */
    private function formatAcceptanceCriteria(array $criteria): string
    {
        return collect($criteria)
            ->map(fn ($item) => "- [ ] {$item}")
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<int, string>
     */
    private function mapLabels(Task $task, array $settings): array
    {
        $labels = $settings['default_labels'] ?? ['planforge'];

        // Map task category to label
        if ($task->category) {
            $labels[] = $task->category->value;
        }

        // Map priority to label
        if ($task->priority?->value === 'high') {
            $labels[] = 'priority:high';
        }

        return array_values(array_unique($labels));
    }
}
