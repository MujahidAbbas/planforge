<?php

use App\Http\Controllers\ProjectExportController;
use App\Livewire\Actions\Logout;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Workspace;
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
});

require __DIR__.'/auth.php';
