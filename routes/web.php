<?php

use App\Http\Controllers\GitHubIntegrationController;
use App\Http\Controllers\GitHubWebhookController;
use App\Http\Controllers\ProjectExportController;
use App\Livewire\Actions\Logout;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Workspace;
use App\Livewire\Templates\Create as TemplatesCreate;
use App\Livewire\Templates\Edit as TemplatesEdit;
use App\Livewire\Templates\Index as TemplatesIndex;
use Illuminate\Support\Facades\Route;

// Welcome page redirects based on auth status
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('projects.index');
    }

    return redirect()->route('login');
});

// Protected routes - require authentication
Route::middleware('auth')->group(function () {
    // Projects
    Route::get('/projects', ProjectsIndex::class)->name('projects.index');
    Route::get('/projects/{project}', Workspace::class)->name('projects.workspace');

    // Templates
    Route::get('/templates', TemplatesIndex::class)->name('templates.index');
    Route::get('/templates/create', TemplatesCreate::class)->name('templates.create');
    Route::get('/templates/{template}/edit', TemplatesEdit::class)->name('templates.edit');

    // Exports
    Route::get('/projects/{project}/exports/project-kit', [ProjectExportController::class, 'projectKit'])
        ->name('projects.exports.projectKit');
    Route::get('/projects/{project}/exports/tasks.json', [ProjectExportController::class, 'tasksJson'])
        ->name('projects.exports.tasksJson');

    // Profile (from Breeze)
    Route::view('profile', 'profile')->name('profile');

    // Logout
    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');

    // GitHub Integration
    Route::prefix('projects/{project}/integrations/github')->group(function () {
        Route::get('install', [GitHubIntegrationController::class, 'install'])
            ->name('integrations.github.install');
        Route::get('select-repo', [GitHubIntegrationController::class, 'selectRepo'])
            ->name('integrations.github.select-repo');
        Route::post('setup', [GitHubIntegrationController::class, 'setup'])
            ->name('integrations.github.setup');
        Route::delete('disconnect', [GitHubIntegrationController::class, 'disconnect'])
            ->name('integrations.github.disconnect');
        Route::post('sync', [GitHubIntegrationController::class, 'sync'])
            ->name('integrations.github.sync');
        Route::get('status', [GitHubIntegrationController::class, 'syncStatus'])
            ->name('integrations.github.status');
    });
});

// GitHub callback - runs without auth since it's called by GitHub
Route::get('integrations/github/callback', [GitHubIntegrationController::class, 'callback'])
    ->name('integrations.github.callback');

// GitHub webhook - no CSRF, no auth - handles incoming webhooks from GitHub
Route::post('webhooks/github', [GitHubWebhookController::class, 'handle'])
    ->name('webhooks.github')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

require __DIR__.'/auth.php';
