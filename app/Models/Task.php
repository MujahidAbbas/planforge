<?php

namespace App\Models;

use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TasksChanged;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * Fields that trigger GitHub sync when changed.
     */
    private const SYNC_TRIGGER_FIELDS = [
        'title',
        'description',
        'acceptance_criteria',
        'status',
        'category',
        'priority',
    ];

    protected static function booted(): void
    {
        static::created(function (Task $task) {
            // New task created - always dispatch
            if ($task->project) {
                TasksChanged::dispatch($task->project, 'created');
            }
        });

        static::updated(function (Task $task) {
            // Only trigger sync if sync-relevant fields changed
            // Use getDirty() because getChanges() is not populated until after syncChanges()
            $changedFields = array_keys($task->getDirty());
            $syncTriggerChanged = array_intersect($changedFields, self::SYNC_TRIGGER_FIELDS);

            if (! empty($syncTriggerChanged) && $task->project) {
                TasksChanged::dispatch($task->project, 'updated');
            }
        });

        static::deleted(function (Task $task) {
            if ($task->project) {
                TasksChanged::dispatch($task->project, 'deleted');
            }
        });
    }

    protected $fillable = [
        'project_id',
        'epic_id',
        'story_id',
        'plan_run_id',
        'plan_run_step_id',
        'task_set_id',
        'title',
        'description',
        'acceptance_criteria',
        'estimate',
        'labels',
        'depends_on',
        'source_refs',
        'status',
        'category',
        'priority',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'acceptance_criteria' => 'array',
            'labels' => 'array',
            'depends_on' => 'array',
            'source_refs' => 'array',
            'status' => TaskStatus::class,
            'category' => TaskCategory::class,
            'priority' => TaskPriority::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function planRun(): BelongsTo
    {
        return $this->belongsTo(PlanRun::class);
    }

    public function planRunStep(): BelongsTo
    {
        return $this->belongsTo(PlanRunStep::class);
    }

    public function taskSet(): BelongsTo
    {
        return $this->belongsTo(TaskSet::class);
    }

    public function externalLinks(): HasMany
    {
        return $this->hasMany(ExternalLink::class);
    }

    public function getGitHubIssueUrl(): ?string
    {
        return $this->externalLinks()
            ->where('provider', 'github')
            ->value('external_url');
    }
}
