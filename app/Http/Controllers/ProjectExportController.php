<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Exports\ProjectKitExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExportController extends Controller
{
    public function projectKit(Project $project, ProjectKitExporter $exporter): StreamedResponse
    {
        // Authorization disabled for now - add back when auth is set up
        // $this->authorize('view', $project);

        $export = $exporter->export(
            $project,
            1 // Would be auth()->id() with real auth
        );

        return Storage::disk($export->disk)->download($export->path, $export->filename);
    }

    public function tasksJson(Project $project): JsonResponse
    {
        // Authorization disabled for now - add back when auth is set up
        // $this->authorize('view', $project);

        $project->load('tasks');

        $tasks = $project->tasks->map(fn ($task) => [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'position' => $task->position,
            'estimate' => $task->estimate,
            'labels' => $task->labels ?? [],
            'acceptance_criteria' => $task->acceptance_criteria ?? [],
            'depends_on' => $task->depends_on ?? [],
            'created_at' => $task->created_at->toIso8601String(),
            'updated_at' => $task->updated_at->toIso8601String(),
        ])->values();

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'exported_at' => now()->toIso8601String(),
            'tasks' => $tasks,
        ]);
    }
}
