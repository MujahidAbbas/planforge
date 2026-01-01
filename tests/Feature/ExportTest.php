<?php

use App\Enums\TaskStatus;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Storage::fake('local');
});

it('downloads a project kit zip export', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'name' => 'Test Project',
        'idea' => 'A test project idea',
    ]);

    // Create PRD document with version
    $prdDoc = Document::factory()->prd()->for($project)->create();
    $prdVersion = DocumentVersion::factory()
        ->for($prdDoc, 'document')
        ->withContent('# PRD Content\n\nThis is the PRD.')
        ->create();
    $prdDoc->update(['current_version_id' => $prdVersion->id]);

    // Create Tech document with version
    $techDoc = Document::factory()->tech()->for($project)->create();
    $techVersion = DocumentVersion::factory()
        ->for($techDoc, 'document')
        ->withContent('# Tech Spec\n\nThis is the tech spec.')
        ->create();
    $techDoc->update(['current_version_id' => $techVersion->id]);

    // Create tasks
    Task::factory()->for($project)->create([
        'title' => 'Task 1',
        'status' => TaskStatus::Todo,
    ]);
    Task::factory()->for($project)->create([
        'title' => 'Task 2',
        'status' => TaskStatus::Doing,
    ]);

    $response = actingAs($user)->get(route('projects.exports.projectKit', $project));

    $response->assertOk();
    $response->assertDownload();

    // Verify ZIP was created in storage
    $zipPath = "exports/{$project->id}";
    expect(Storage::disk('local')->directories('exports'))->toContain($zipPath);

    // Find the ZIP file
    $files = Storage::disk('local')->files($zipPath);
    expect($files)->toHaveCount(1);

    $zipAbsPath = Storage::disk('local')->path($files[0]);

    // Verify ZIP contents
    $zip = new ZipArchive;
    expect($zip->open($zipAbsPath))->toBeTrue();

    expect($zip->locateName('README.md'))->not->toBeFalse();
    expect($zip->locateName('docs/PRD.md'))->not->toBeFalse();
    expect($zip->locateName('docs/TECH.md'))->not->toBeFalse();
    expect($zip->locateName('kanban/tasks.json'))->not->toBeFalse();
    expect($zip->locateName('kanban/tasks.csv'))->not->toBeFalse();
    expect($zip->locateName('meta.json'))->not->toBeFalse();

    // Verify README contains project name
    $readmeContent = $zip->getFromName('README.md');
    expect($readmeContent)->toContain('Test Project');

    // Verify tasks.json structure
    $tasksJson = json_decode($zip->getFromName('kanban/tasks.json'), true);
    expect($tasksJson)->toHaveKey('project');
    expect($tasksJson)->toHaveKey('tasks');
    expect($tasksJson['tasks'])->toHaveCount(2);

    $zip->close();
});

it('exports tasks as json with stable structure', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'name' => 'JSON Export Test',
    ]);

    Task::factory()->for($project)->count(3)->create(['status' => TaskStatus::Todo]);

    $response = actingAs($user)->get(route('projects.exports.tasksJson', $project));

    $response->assertOk();
    $response->assertJsonStructure([
        'project' => ['id', 'name'],
        'exported_at',
        'tasks' => [
            '*' => [
                'id',
                'title',
                'description',
                'status',
                'position',
                'estimate',
                'labels',
                'acceptance_criteria',
                'depends_on',
                'created_at',
                'updated_at',
            ],
        ],
    ]);

    $data = $response->json();
    expect($data['project']['name'])->toBe('JSON Export Test');
    expect($data['tasks'])->toHaveCount(3);
});

it('returns empty tasks array when project has no tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = actingAs($user)->get(route('projects.exports.tasksJson', $project));

    $response->assertOk();
    $response->assertJson([
        'project' => [
            'id' => $project->id,
            'name' => $project->name,
        ],
        'tasks' => [],
    ]);
});

it('includes task status values correctly', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Task::factory()->for($project)->todo()->create(['title' => 'Todo Task']);
    Task::factory()->for($project)->doing()->create(['title' => 'Doing Task']);
    Task::factory()->for($project)->done()->create(['title' => 'Done Task']);

    $response = actingAs($user)->get(route('projects.exports.tasksJson', $project));

    $response->assertOk();
    $data = $response->json();

    $statuses = collect($data['tasks'])->pluck('status')->unique()->sort()->values();
    expect($statuses->toArray())->toBe(['doing', 'done', 'todo']);
});

it('creates export record when downloading project kit', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    actingAs($user)->get(route('projects.exports.projectKit', $project));

    $this->assertDatabaseHas('exports', [
        'project_id' => $project->id,
        'type' => 'project_kit',
        'status' => 'ready',
    ]);
});
