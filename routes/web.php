<?php

use App\Http\Controllers\ProjectExportController;
use App\Livewire\HelloWorld;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Workspace;
use Illuminate\Support\Facades\Route;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello', HelloWorld::class);

Route::get('/ai-test', function () {
    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-sonnet-4-20250514')
        ->withMaxTokens(100)
        ->withPrompt('Say "Hello from Prism!" and nothing else.')
        ->asText();

    return response()->json([
        'success' => true,
        'response' => $response->text,
    ]);
});

// Project routes (auth middleware disabled for now - add back when auth is set up)
Route::get('/projects', ProjectsIndex::class)->name('projects.index');
Route::get('/projects/{project}', Workspace::class)->name('projects.workspace');

// Export routes
Route::get('/projects/{project}/exports/project-kit', [ProjectExportController::class, 'projectKit'])
    ->name('projects.exports.projectKit');
Route::get('/projects/{project}/exports/tasks.json', [ProjectExportController::class, 'tasksJson'])
    ->name('projects.exports.tasksJson');
